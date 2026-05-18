import cv2
import pytesseract
import serial
import serial.tools.list_ports
import time
import re
import mysql.connector
import datetime
import socket
import numpy as np
import threading
import os

# --- Funciones de Utilidad Iniciales ---

def log_mensaje(origen, mensaje):
    ahora = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    print(f"[{ahora}] [{origen}] {mensaje}", flush=True)

def auto_detectar_puerto_arduino():
    # Detecta el puerto de Arduino buscando palabras clave en los dispositivos serie activos
    if "ARDUINO_PORT" in os.environ and os.environ["ARDUINO_PORT"].strip() != "":
        return os.environ["ARDUINO_PORT"]
    
    puertos = serial.tools.list_ports.comports()
    for p in puertos:
        if "Arduino" in p.description or "ACM" in p.device or "USB" in p.device:
            log_mensaje("Sistema", f"Arduino detectado en: {p.device}")
            return p.device
            
    return "/dev/ttyACM0"

# --- Parametros y Configuracion General del Sistema ---

# Base de datos
DB_HOST = os.environ.get("DB_HOST", "db")
DB_USER = os.environ.get("DB_USER", "root")
DB_PASSWORD = os.environ.get("DB_PASSWORD", "root")
DB_NAME = os.environ.get("DB_NAME", "parking_ASIR")

# Comunicacion Serie con Arduino
PUERTO = auto_detectar_puerto_arduino()
BAUDIOS = 9600
CERRADA = 200
ABIERTA = 85

# Regiones de Interes (ROIs) para Camaras
ROI_CAM0 = (300, 300, 1320, 600)
ROI_CAM2 = (300, 300, 1320, 600)

# Control de lectura y debounce de matriculas
BLOQUEO_SEGUNDOS = 10
VOTOS_NECESARIOS = 2

# Candados (Locks) de sincronizacion multihilo
_serial_lock = threading.Lock()
_parking_lock = threading.Lock()

# Variables globales para control de plazas y aforo
_coche_pendiente = {"matricula": None, "tiempo_entrada": 0}
_plazas_vehiculos = {1: None, 2: None, 3: None}
_plazas_pendientes_salida = {}

_estado_camaras = {
    "Cámara 0": {"ultima_matricula": None, "ultimo_tiempo": 0, "votos": {}, "lock": threading.Lock()},
    "Cámara 2": {"ultima_matricula": None, "ultimo_tiempo": 0, "votos": {}, "lock": threading.Lock()},
}

# --- Alertas y Notificaciones ---

def enviar_mensaje_telegram(mensaje):
    token = os.environ.get("TELEGRAM_BOT_TOKEN")
    chat_id = os.environ.get("TELEGRAM_CHAT_ID")
    
    if not token or not chat_id:
        log_mensaje("Telegram", "ADVERTENCIA: Telegram no configurado.")
        return
        
    import urllib.request
    import urllib.parse
    
    url = f"https://api.telegram.org/bot{token}/sendMessage"
    data = urllib.parse.urlencode({
        "chat_id": chat_id,
        "text": mensaje,
        "parse_mode": "HTML"
    }).encode("utf-8")
    
    try:
        # Petición HTTP nativa para evitar dependencias de librerías externas
        req = urllib.request.Request(url, data=data, method="POST")
        with urllib.request.urlopen(req, timeout=10) as response:
            response.read()
        log_mensaje("Telegram", "Mensaje enviado a Telegram correctamente.")
    except Exception as e:
        log_mensaje("Telegram", f"Error al enviar mensaje: {e}")

# --- Consultas y Operaciones SQL ---

def actualizar_plaza(id_plaza, estado):
    try:
        conn = mysql.connector.connect(host=DB_HOST, user=DB_USER, password=DB_PASSWORD, database=DB_NAME)
        cursor = conn.cursor()
        cursor.execute("""
            INSERT INTO plazas (id_plaza, estado) 
            VALUES (%s, %s) 
            ON DUPLICATE KEY UPDATE estado=%s, ultima_actualizacion=NOW()
        """, (id_plaza, estado, estado))
        conn.commit()
        conn.close()
    except mysql.connector.Error as err:
        log_mensaje("Base de Datos", f"Error al actualizar plaza: {err}")

def es_matricula_autorizada(matricula):
    try:
        conn = mysql.connector.connect(host=DB_HOST, user=DB_USER, password=DB_PASSWORD, database=DB_NAME)
        cursor = conn.cursor()
        # Relaciona el vehículo con su dueño para verificar autorización
        query = """
            SELECT u.nombre 
            FROM usuarios u 
            JOIN vehiculos v ON u.dni = v.dni_usuario 
            WHERE v.matricula = %s
        """
        cursor.execute(query, (matricula,))
        resultado = cursor.fetchone()
        conn.close()
        return resultado[0] if resultado else None
    except mysql.connector.Error as err:
        log_mensaje("Base de Datos", f"Error al validar matrícula: {err}")
        return None

def registrar_acceso(matricula, origen):
    try:
        conn = mysql.connector.connect(host=DB_HOST, user=DB_USER, password=DB_PASSWORD, database=DB_NAME)
        cursor = conn.cursor()
        movimiento = "ENTRADA" if "0" in origen else "SALIDA"
        cursor.execute("INSERT INTO accesos (matricula, tipo_movimiento) VALUES (%s, %s)", (matricula, movimiento))
        conn.commit()
        conn.close()
    except mysql.connector.Error as err:
        log_mensaje("Base de Datos", f"Error al registrar acceso: {err}")

def registrar_intento_denegado(matricula, origen):
    try:
        conn = mysql.connector.connect(host=DB_HOST, user=DB_USER, password=DB_PASSWORD, database=DB_NAME)
        cursor = conn.cursor()
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS intentos_denegados (
                id_intento INT AUTO_INCREMENT PRIMARY KEY,
                matricula VARCHAR(10) NOT NULL,
                fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
                camara VARCHAR(50)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        """)
        cursor.execute("INSERT INTO intentos_denegados (matricula, camara) VALUES (%s, %s)", (matricula, origen))
        conn.commit()
        conn.close()
    except mysql.connector.Error as err:
        log_mensaje("Base de Datos", f"Error al registrar intento: {err}")

def obtener_ultimo_movimiento(matricula):
    try:
        conn = mysql.connector.connect(host=DB_HOST, user=DB_USER, password=DB_PASSWORD, database=DB_NAME)
        cursor = conn.cursor()
        cursor.execute(
            "SELECT tipo_movimiento FROM accesos WHERE matricula = %s ORDER BY fecha_hora DESC LIMIT 1",
            (matricula,)
        )
        resultado = cursor.fetchone()
        conn.close()
        return resultado[0] if resultado else None
    except mysql.connector.Error as err:
        log_mensaje("Base de Datos", f"Error al consultar último movimiento: {err}")
        return None

# --- Comunicacion con Arduino e Interfaces ---

def enviar(ser, angulo, linea1, linea2="", estado_led="0"):
    # Protocolo de comunicación con Arduino: AnguloBarrera|TextoL1|TextoL2|Semáforo
    with _serial_lock:
        comando = f"{angulo}|{linea1}|{linea2}|{estado_led}\n"
        ser.write(comando.encode('utf-8'))
        ser.flush()

def get_ip_address():
    # Obtiene la IP de la interfaz activa abriendo una conexión UDP de prueba
    for _ in range(15):
        try:
            s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
            s.settimeout(0)
            s.connect(('8.8.8.8', 1))
            ip = s.getsockname()[0]
            s.close()
            return ip
        except Exception:
            time.sleep(2)
    return "Sin IP"

def mostrar_ip_al_arranque(ser):
    ip = get_ip_address()
    log_mensaje("Sistema", f"IP Detectada: {ip}")
    enviar(ser, CERRADA, "IP de la RPi:", ip, "3")
    time.sleep(60)
    enviar(ser, CERRADA, "Iniciando...", "Sistema OK", "0")
    time.sleep(2)

def normalizar_matricula(m):
    return m.strip().upper().replace(" ", "").replace("-", "")

def limpiar_texto(texto):
    texto = texto.upper().replace(" ", "").replace("\n", "").replace("\f", "")
    return re.sub(r"[^A-Z0-9]", "", texto)

def es_matricula_valida(texto):
    # Formato oficial español: 4 números y 3 letras
    return re.fullmatch(r"[0-9]{4}[A-Z]{3}", texto) is not None

# --- Control de Elementos Físicos (Barrera, Leds y LCD) ---

def mostrar_espera(ser, origen):
    saludo = "Bienvenido" if "0" in origen else "Espere por favor..."
    enviar(ser, CERRADA, saludo, "")
    time.sleep(1.5)
    
    cuadros = [
        ("Comprobando su", "matricula   "),
        ("Comprobando su", "matricula.  "),
        ("Comprobando su", "matricula.. "),
        ("Comprobando su", "matricula..."),
    ]
    for _ in range(2):
        for l1, l2 in cuadros:
            enviar(ser, CERRADA, l1, l2)
            time.sleep(0.4)

def abrir_barrera(ser, origen, nombre_usuario=""):
    estado_led = "1" if "0" in origen else "2"
    es_entrada = "0" in origen
    linea_nombre = nombre_usuario[:16] if nombre_usuario else ""

    enviar(ser, ABIERTA, "Acceso", "autorizado", estado_led)
    time.sleep(1.5)

    if es_entrada:
        enviar(ser, ABIERTA, "Bienvenido", linea_nombre, estado_led)
        time.sleep(2)
        enviar(ser, ABIERTA, "Puede pasar", "", estado_led)
        time.sleep(3)
    else:
        enviar(ser, ABIERTA, "Hasta pronto", linea_nombre, estado_led)
        time.sleep(2)
        enviar(ser, ABIERTA, "Buen viaje!", "", estado_led)
        time.sleep(3)

    enviar(ser, CERRADA, "Cerrando", "Espere", "0")
    time.sleep(2)
    enviar(ser, CERRADA, "Esperando", "vehiculo", "0")
    time.sleep(1)

def denegar_paso(ser):
    enviar(ser, CERRADA, "Acceso", "denegado")
    time.sleep(2.5)
    enviar(ser, CERRADA, "Matricula", "no valida")
    time.sleep(2)
    enviar(ser, CERRADA, "Esperando", "vehiculo")
    time.sleep(1)

def denegar_estado(ser, linea1, linea2):
    enviar(ser, CERRADA, "Acceso", "denegado")
    time.sleep(1.5)
    enviar(ser, CERRADA, linea1, linea2)
    time.sleep(2.5)
    enviar(ser, CERRADA, "Esperando", "vehiculo")
    time.sleep(1)

# --- Algoritmos de Visión Artificial (OpenCV) ---

def configurar_camara(indice):
    cap = cv2.VideoCapture(indice, cv2.CAP_V4L2)
    if not cap.isOpened():
        return None
    cap.set(cv2.CAP_PROP_FRAME_WIDTH, 1280)
    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 720)
    cap.set(cv2.CAP_PROP_FPS, 10)
    try:
        cap.set(cv2.CAP_PROP_FOURCC, cv2.VideoWriter_fourcc(*"MJPG"))
        cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
    except:
        pass
    return cap

def recortar_roi(frame, roi):
    x, y, w, h = roi
    return frame[y:y+h, x:x+w]

def localizar_matricula(roi):
    # Proceso de detección de bordes para delimitar el rectángulo de la matrícula
    gris = cv2.cvtColor(roi, cv2.COLOR_BGR2GRAY)
    filtrada = cv2.bilateralFilter(gris, 11, 17, 17)  # Suaviza manteniendo bordes definidos
    bordes = cv2.Canny(filtrada, 30, 200)
    
    contornos, _ = cv2.findContours(bordes.copy(), cv2.RETR_TREE, cv2.CHAIN_APPROX_SIMPLE)
    contornos = sorted(contornos, key=cv2.contourArea, reverse=True)[:10]
    
    for c in contornos:
        perimetro = cv2.arcLength(c, True)
        aproximacion = cv2.approxPolyDP(c, 0.02 * perimetro, True)
        
        if len(aproximacion) == 4:  # Verifica si el contorno es cuadrilátero
            x, y, w, h = cv2.boundingRect(aproximacion)
            aspect_ratio = w / float(h)
            if 2.0 < aspect_ratio < 5.0:  # Relación de aspecto típica de matrículas
                return roi[y:y+h, x:x+w], True
                
    return roi, False

def preparar_imagen_para_ocr(imagen, metodo=1):
    # Optimiza la calidad del recorte para facilitar la lectura del OCR
    if len(imagen.shape) == 3:
        gris = cv2.cvtColor(imagen, cv2.COLOR_BGR2GRAY)
    else:
        gris = imagen.copy()

    # Filtro CLAHE para homogeneizar contrastes y sombras locales
    clahe = cv2.createCLAHE(clipLimit=3.0, tileGridSize=(8, 8))
    gris = clahe.apply(gris)
    gris = cv2.GaussianBlur(gris, (3, 3), 0)

    if metodo == 1:
        binaria = cv2.adaptiveThreshold(gris, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 11, 2)
    else:
        _, binaria = cv2.threshold(gris, 0, 255, cv2.THRESH_BINARY | cv2.THRESH_OTSU)

    h, w = binaria.shape
    if h < 80:
        factor = 80.0 / h
        binaria = cv2.resize(binaria, None, fx=factor, fy=factor, interpolation=cv2.INTER_CUBIC)
    elif h < 150:
        binaria = cv2.resize(binaria, None, fx=2, fy=2, interpolation=cv2.INTER_CUBIC)

    return binaria

def leer_matricula_desde_roi(frame, roi):
    recorte_original = recortar_roi(frame, roi)
    placa_detectada, encontrada = localizar_matricula(recorte_original)
    
    for metodo in [1, 2]:
        procesada = preparar_imagen_para_ocr(placa_detectada, metodo=metodo)
        
        # Ajusta el PSM de Tesseract según se haya aislado o no el rectángulo
        psm = "--psm 8" if encontrada else "--psm 7"
        config = f"{psm} -c tessedit_char_whitelist=0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"
        
        texto = pytesseract.image_to_string(procesada, config=config)
        texto_detectado = limpiar_texto(texto)
        
        if es_matricula_valida(texto_detectado):
            return texto_detectado, placa_detectada, procesada
            
    if encontrada:
        procesada_roi = preparar_imagen_para_ocr(recorte_original, metodo=1)
        texto = pytesseract.image_to_string(procesada_roi, config="--psm 7 -c tessedit_char_whitelist=0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ")
        texto_detectado = limpiar_texto(texto)
        return texto_detectado, recorte_original, procesada_roi

    return texto_detectado, placa_detectada, procesada

# --- Validación Temporal y Lógica de Negocio ---

def debe_ignorar_matricula(matricula, origen):
    # Evita lecturas continuas e instantáneas del mismo coche parado
    estado = _estado_camaras[origen]
    ahora = time.time()
    with estado["lock"]:
        if estado["ultima_matricula"] == matricula and (ahora - estado["ultimo_tiempo"]) < BLOQUEO_SEGUNDOS:
            return True
    return False

def registrar_voto(matricula, origen):
    # Exige confirmación múltiple para descartar lecturas erróneas fugaces
    estado = _estado_camaras[origen]
    with estado["lock"]:
        votos = estado["votos"]
        votos[matricula] = votos.get(matricula, 0) + 1
        
        for m in list(votos.keys()):
            if m != matricula:
                del votos[m]
                
        if votos[matricula] >= VOTOS_NECESARIOS:
            votos[matricula] = 0
            estado["ultima_matricula"] = matricula
            estado["ultimo_tiempo"] = time.time()
            return True
        return False

def procesar_matricula(ser, matricula, origen):
    if debe_ignorar_matricula(matricula, origen):
        return

    if not registrar_voto(matricula, origen):
        log_mensaje(origen, f"Candidata ({matricula}) - esperando confirmación...")
        return

    log_mensaje(origen, f"¡MATRÍCULA CONFIRMADA!: {matricula}")
    mostrar_espera(ser, origen)

    nombre = es_matricula_autorizada(matricula)
    if not nombre:
        log_mensaje("Autorización", f"DENEGADO - La matrícula {matricula} NO está autorizada.")
        registrar_intento_denegado(matricula, origen)
        enviar(ser, CERRADA, "Matricula NO", matricula[:16])
        time.sleep(2)
        denegar_paso(ser)
        return

    # Lógica Anti-Passback para garantizar la consistencia lógica de accesos
    es_entrada = "0" in origen
    ultimo_movimiento = obtener_ultimo_movimiento(matricula)

    if es_entrada and ultimo_movimiento == "ENTRADA":
        log_mensaje("Estado", f"BLOQUEADO - {matricula} ya está dentro.")
        denegar_estado(ser, "Ya en parking", "No puede entrar")
        return

    if not es_entrada and ultimo_movimiento != "ENTRADA":
        log_mensaje("Estado", f"BLOQUEADO - {matricula} no figura como dentro.")
        denegar_estado(ser, "No esta dentro", "No puede salir")
        return

    log_mensaje("Autorización", f"OK - La matrícula {matricula} ({nombre}) está AUTORIZADA.")
    enviar(ser, CERRADA, "Matricula OK", matricula[:16])
    registrar_acceso(matricula, origen)
    
    global _coche_pendiente
    if es_entrada:
        with _parking_lock:
            _coche_pendiente["matricula"] = matricula
            _coche_pendiente["tiempo_entrada"] = time.time()
            log_mensaje("Sensor", f"Iniciado temporizador de aparcamiento para {matricula}")

    time.sleep(2)
    abrir_barrera(ser, origen, nombre)

def guardar_debug(nombre, frame, recorte, procesada):
    cv2.imwrite(f"./{nombre}_frame.jpg", frame)
    cv2.imwrite(f"./{nombre}_roi.jpg", recorte)
    cv2.imwrite(f"./{nombre}_ocr.jpg", procesada)

# --- Bucle de Hilos Concurrentes (Background) ---

def bucle_camara(ser, cap, roi, nombre_cam):
    log_mensaje(nombre_cam, "Hilo de cámara iniciado.")
    while True:
        ok, frame = cap.read()
        if ok:
            texto, recorte, proc = leer_matricula_desde_roi(frame, roi)
            if es_matricula_valida(texto):
                guardar_debug(nombre_cam.replace(" ", "").lower(), frame, recorte, proc)
                procesar_matricula(ser, normalizar_matricula(texto), nombre_cam)
            elif texto:
                log_mensaje(nombre_cam, f"Texto ilegible detectado: '{texto}'")
        else:
            log_mensaje(nombre_cam, "Frame vacío, reintentando...")
        time.sleep(0.3)

def bucle_lectura_arduino(ser):
    # Escucha y decodifica las lecturas de los sensores físicos recibidos desde el Arduino
    log_mensaje("Sensor", "Hilo de lectura del Arduino iniciado.")
    ser.timeout = 0.5 
    while True:
        try:
            linea = ser.readline()
            if linea:
                texto = linea.decode('utf-8', errors='ignore').strip()
                if texto.startswith("SENSOR|"):
                    partes = texto.split("|")
                    plaza = partes[1]
                    estado = partes[2]
                    
                    if estado == "BIEN":
                        log_mensaje("Sensor", f"ESTADO: Coche BIEN aparcado en la Plaza {plaza}.")
                        actualizar_plaza(int(plaza), "ocupada")
                        with _parking_lock:
                            mat = _coche_pendiente["matricula"]
                            if mat:
                                log_mensaje("Sensor", f"Matrícula {mat} ha aparcado en la Plaza {plaza}.")
                                _plazas_vehiculos[int(plaza)] = mat
                                _coche_pendiente["matricula"] = None
                                _coche_pendiente["tiempo_entrada"] = 0
                                
                                if int(plaza) in _plazas_pendientes_salida:
                                    del _plazas_pendientes_salida[int(plaza)]
                    elif estado == "MAL":
                        log_mensaje("Sensor", f"ESTADO: Plaza {plaza} VACÍA o coche MAL aparcado.")
                        actualizar_plaza(int(plaza), "libre")
                        with _parking_lock:
                            mat = _plazas_vehiculos.get(int(plaza))
                            if mat:
                                # Inicia el temporizador de gracia al desocuparse la plaza física
                                _plazas_pendientes_salida[int(plaza)] = {
                                    "matricula": mat,
                                    "tiempo_sensor_verde": time.time()
                                }
                                _plazas_vehiculos[int(plaza)] = None
                                log_mensaje("Sensor", f"Coche {mat} ha dejado la Plaza {plaza}. Observando salida.")
        except Exception as e:
            log_mensaje("Sensor", f"Error leyendo del puerto serie: {e}")
            time.sleep(1)

def monitor_aparcamiento():
    # Hilo autónomo de auditoría de tiempos (Aparcamiento fallido y doble fila/obstrucción)
    log_mensaje("Monitor", "Hilo de monitorización de aparcamiento iniciado.")
    TIEMPO_LIMITE = 300       # 5 minutos para aparcar
    GRACE_PERIOD_SALIDA = 60  # 1 minuto de gracia para abandonar el recinto
    
    while True:
        # A. Vigilancia de vehículos que entraron pero no se han detectado en plazas físicas
        with _parking_lock:
            mat_p = _coche_pendiente["matricula"]
            t_entrada = _coche_pendiente["tiempo_entrada"]
            
        if mat_p is not None and t_entrada > 0:
            if (time.time() - t_entrada) > TIEMPO_LIMITE:
                log_mensaje("Alerta", f"¡ATENCIÓN! {mat_p} ha superado el tiempo límite para aparcar.")
                msg = f"⚠️ <b>ALERTA DE PARKING</b> ⚠️\n\nEl vehículo con matrícula <b>{mat_p}</b> ha accedido al recinto pero NO ha aparcado en ninguna plaza libre tras 5 minutos. ¡Podría estar mal estacionado u obstruyendo el paso!"
                enviar_mensaje_telegram(msg)
                
                with _parking_lock:
                    if _coche_pendiente["matricula"] == mat_p:
                        _coche_pendiente["matricula"] = None
                        _coche_pendiente["tiempo_entrada"] = 0
                        
        # B. Vigilancia de vehículos que salieron de su plaza pero no han cruzado la barrera de salida
        with _parking_lock:
            plazas_a_revisar = list(_plazas_pendientes_salida.items())
            
        for id_plaza, info in plazas_a_revisar:
            mat = info["matricula"]
            t_verde = info["tiempo_sensor_verde"]
            
            if (time.time() - t_verde) > GRACE_PERIOD_SALIDA:
                ultimo_mov = obtener_ultimo_movimiento(mat)
                if ultimo_mov == "ENTRADA":
                    log_mensaje("Alerta", f"¡ATENCIÓN! {mat} dejó la Plaza {id_plaza} pero no ha salido del recinto.")
                    msg = f"⚠️ <b>ALERTA DE PARKING</b> ⚠️\n\nEl vehículo con matrícula <b>{mat}</b> ha dejado la <b>Plaza {id_plaza}</b> (el sensor ha vuelto a VERDE), pero NO ha salido del parking tras el tiempo estimado. ¡Podría estar mal aparcado u obstruyendo la circulación!"
                    enviar_mensaje_telegram(msg)
                else:
                    log_mensaje("Monitor", f"Vehículo {mat} ha salido correctamente.")
                    
                with _parking_lock:
                    if id_plaza in _plazas_pendientes_salida and _plazas_pendientes_salida[id_plaza]["matricula"] == mat:
                        del _plazas_pendientes_salida[id_plaza]
                        
        time.sleep(5)

# --- Arranque del Sistema ---

def iniciar_sistema():
    ser = serial.Serial(PUERTO, BAUDIOS, timeout=1)
    time.sleep(2.5)

    mostrar_ip_al_arranque(ser)

    enviar(ser, CERRADA, "Esperando", "vehiculo")
    time.sleep(1)

    cam0 = configurar_camara(0)
    cam2 = configurar_camara(2)

    if cam0 is None:
        log_mensaje("Cámara 0", "CRÍTICO - No se pudo abrir la cámara de Entrada (0)")
        ser.close()
        return

    if cam2 is None:
        log_mensaje("Cámara 2", "CRÍTICO - No se pudo abrir la cámara de Salida (2)")
        cam0.release()
        ser.close()
        return

    time.sleep(2)

    for _ in range(5):
        cam0.read()
        cam2.read()

    # Creación y lanzamiento de los 4 hilos concurrentes
    hilo_cam0 = threading.Thread(target=bucle_camara, args=(ser, cam0, ROI_CAM0, "Cámara 0"), daemon=True)
    hilo_cam2 = threading.Thread(target=bucle_camara, args=(ser, cam2, ROI_CAM2, "Cámara 2"), daemon=True)
    hilo_sensor = threading.Thread(target=bucle_lectura_arduino, args=(ser,), daemon=True)
    hilo_monitor = threading.Thread(target=monitor_aparcamiento, daemon=True)

    hilo_cam0.start()
    hilo_cam2.start()
    hilo_sensor.start()
    hilo_monitor.start()
    
    log_mensaje("Sistema", "Cámaras, sensor y monitor activos en paralelo.")

    hilo_cam0.join()
    hilo_cam2.join()
    hilo_sensor.join()
    hilo_monitor.join()

# Ejecutamos la función para iniciar el programa
iniciar_sistema()