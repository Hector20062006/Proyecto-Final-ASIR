import cv2
import pytesseract
import serial
import time
import re
import mysql.connector
import datetime
import socket
import numpy as np

def log_mensaje(origen, mensaje):
    ahora = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    print(f"[{ahora}] [{origen}] {mensaje}", flush=True)

PUERTO = "/dev/ttyACM0"
BAUDIOS = 9600

CERRADA = 100
ABIERTA = 20

def es_matricula_autorizada(matricula):
    try:
        conn = mysql.connector.connect(host="db", user="root", password="root", database="parking_ASIR")
        cursor = conn.cursor()
        # Buscamos el nombre del usuario asociado a esa matrícula
        query = """
            SELECT u.nombre 
            FROM usuarios u 
            JOIN vehiculos v ON u.dni = v.dni_usuario 
            WHERE v.matricula = %s
        """
        cursor.execute(query, (matricula,))
        resultado = cursor.fetchone()
        conn.close()
        
        if resultado:
            return resultado[0] # Retornamos el nombre del usuario
        return None
    except mysql.connector.Error as err:
        log_mensaje("Base de Datos", f"Error al validar matrícula: {err}")
        return None

def registrar_acceso(matricula, origen):
    try:
        conn = mysql.connector.connect(host="db", user="root", password="root", database="parking_ASIR")
        cursor = conn.cursor()
        # Si viene de la Cámara 0 es ENTRADA, si es la 2 es SALIDA
        movimiento = "ENTRADA" if "0" in origen else "SALIDA"
        cursor.execute("INSERT INTO accesos (matricula, tipo_movimiento) VALUES (%s, %s)", (matricula, movimiento))
        conn.commit()
        conn.close()
        log_mensaje("Base de Datos", f"Acceso registrado: {matricula} - {movimiento}")
    except mysql.connector.Error as err:
        log_mensaje("Base de Datos", f"Error al registrar acceso: {err}")

# Zona de lectura de cada cámara: (x, y, ancho, alto)
# Valores ajustados para mayor rango en resolución 1080p
ROI_CAM0 = (300, 300, 1320, 600)
ROI_CAM2 = (300, 300, 1320, 600)

ULTIMA_MATRICULA = None
ULTIMO_TIEMPO = 0
BLOQUEO_SEGUNDOS = 10


def enviar(ser, angulo, linea1, linea2="", estado_led="0"):
    comando = f"{angulo}|{linea1}|{linea2}|{estado_led}\n"
    ser.write(comando.encode())
    ser.flush()


def get_ip_address():
    # Intentamos obtener la IP de la interfaz activa usando sockets
    for _ in range(15):  # Reintentar durante ~30 segundos si la red tarda en subir
        try:
            s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
            s.settimeout(0)
            # Intentamos conectar a una IP externa (Google DNS) para ver qué interfaz local se usaría
            s.connect(('8.8.8.8', 1))
            ip = s.getsockname()[0]
            s.close()
            return ip
        except Exception:
            time.sleep(2)
    return "Sin IP"


def mostrar_ip_al_arranque(ser):
    log_mensaje("Sistema", "Iniciando secuencia de visualización de IP...")
    ip = get_ip_address()
    log_mensaje("Sistema", f"IP Detectada: {ip}")
    
    # 100 es CERRADA, "3" es modo Test (todos los LEDs encendidos)
    enviar(ser, CERRADA, "IP de la RPi:", ip, "3")
    
    # Esperamos 60 segundos como solicitó el usuario
    time.sleep(60)
    
    enviar(ser, CERRADA, "Iniciando...", "Sistema OK", "0")
    time.sleep(2)


def normalizar_matricula(m):
    return m.strip().upper().replace(" ", "").replace("-", "")


def mostrar_espera(ser):
    enviar(ser, CERRADA, "Bienvenido", "")
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
    # Estado 1: Entrada Verde / Salida Rojo (Para Cámara 0)
    # Estado 2: Entrada Rojo / Salida Verde (Para Cámara 2)
    estado_led = "1" if "0" in origen else "2"
    
    # 1. Acceso autorizado
    enviar(ser, ABIERTA, "Acceso", "autorizado", estado_led)
    time.sleep(1.5)

    # 2. Bienvenido + Nombre
    linea_nombre = nombre_usuario[:16] if nombre_usuario else ""
    enviar(ser, ABIERTA, "Bienvenido", linea_nombre, estado_led)
    time.sleep(2)

    # 3. Puede pasar
    enviar(ser, ABIERTA, "Puede pasar", "", estado_led)
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


def limpiar_texto(texto):
    texto = texto.upper()
    texto = texto.replace(" ", "")
    texto = texto.replace("\n", "")
    texto = texto.replace("\f", "")
    texto = re.sub(r"[^A-Z0-9]", "", texto)
    return texto


def es_matricula_valida(texto):
    # Formato: 4 números (del 0 al 9) y 3 letras (A-Z)
    return re.fullmatch(r"[0-9]{4}[A-Z]{3}", texto) is not None


def configurar_camara(indice):
    cap = cv2.VideoCapture(indice, cv2.CAP_V4L2)

    if not cap.isOpened():
        return None

    cap.set(cv2.CAP_PROP_FRAME_WIDTH, 1920)
    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 1080)
    cap.set(cv2.CAP_PROP_FPS, 5)

    try:
        cap.set(cv2.CAP_PROP_FOURCC, cv2.VideoWriter_fourcc(*"MJPG"))
    except:
        pass

    try:
        cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
    except:
        pass

    return cap


def recortar_roi(frame, roi):
    x, y, w, h = roi
    return frame[y:y+h, x:x+w]


def localizar_matricula(roi):
    # Intentamos encontrar el rectángulo de la matrícula para recortar el ruido
    gris = cv2.cvtColor(roi, cv2.COLOR_BGR2GRAY)
    # Filtro bilateral para reducir ruido manteniendo bordes
    filtrada = cv2.bilateralFilter(gris, 11, 17, 17)
    bordes = cv2.Canny(filtrada, 30, 200)
    
    contornos, _ = cv2.findContours(bordes.copy(), cv2.RETR_TREE, cv2.CHAIN_APPROX_SIMPLE)
    contornos = sorted(contornos, key=cv2.contourArea, reverse=True)[:10]
    
    for c in contornos:
        perimetro = cv2.arcLength(c, True)
        aproximacion = cv2.approxPolyDP(c, 0.02 * perimetro, True)
        if len(aproximacion) == 4: # Si tiene 4 esquinas, es un candidato
            x, y, w, h = cv2.boundingRect(aproximacion)
            # Verificamos una relación de aspecto razonable para una matrícula
            aspect_ratio = w / float(h)
            if 2.0 < aspect_ratio < 5.0:
                return roi[y:y+h, x:x+w], True
    
    return roi, False # Si no encuentra nada claro, devuelve el ROI original


def preparar_imagen_para_ocr(imagen, metodo=1):
    # 1. Escala de grises si no lo está
    if len(imagen.shape) == 3:
        gris = cv2.cvtColor(imagen, cv2.COLOR_BGR2GRAY)
    else:
        gris = imagen

    # 2. Filtro bilateral para preservar bordes de las letras
    suave = cv2.bilateralFilter(gris, 9, 75, 75)
    
    # 3. Afilado (Sharpening) para definir mejor los caracteres
    kernel = np.array([[0, -1, 0], [-1, 5, -1], [0, -1, 0]])
    afilada = cv2.filter2D(suave, -1, kernel)

    # 4. Binarización
    if metodo == 1:
        # Umbral adaptativo (mejor para iluminación variable)
        binaria = cv2.adaptiveThreshold(afilada, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 11, 2)
    else:
        # Método Otsu como alternativa
        _, binaria = cv2.threshold(afilada, 0, 255, cv2.THRESH_BINARY | cv2.THRESH_OTSU)
    
    # 5. Redimensionar para que Tesseract lea mejor (mínimo 300dpi equivalentes)
    procesada = cv2.resize(binaria, None, fx=2, fy=2, interpolation=cv2.INTER_CUBIC)
    
    return procesada


def leer_matricula_desde_roi(frame, roi):
    recorte_original = recortar_roi(frame, roi)
    
    # Intentamos localizar la placa exacta
    placa_detectada, encontrada = localizar_matricula(recorte_original)
    
    # Estrategia: Probar hasta 2 métodos de pre-procesamiento
    for metodo in [1, 2]:
        procesada = preparar_imagen_para_ocr(placa_detectada, metodo=metodo)
        
        # Si hemos encontrado la placa exacta, PSM 8 (Single word) suele ser mejor.
        # Si no, PSM 7 (Single line) es más robusto para el ROI completo.
        psm = "--psm 8" if encontrada else "--psm 7"
        config = f"{psm} -c tessedit_char_whitelist=0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"
        
        texto = pytesseract.image_to_string(procesada, config=config)
        texto_detectado = limpiar_texto(texto)
        
        if es_matricula_valida(texto_detectado):
            return texto_detectado, placa_detectada, procesada
            
    # Si falla con el recorte, intentamos una última vez con el ROI original completo
    if encontrada:
        procesada_roi = preparar_imagen_para_ocr(recorte_original, metodo=1)
        texto = pytesseract.image_to_string(procesada_roi, config="--psm 7 -c tessedit_char_whitelist=0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ")
        texto_detectado = limpiar_texto(texto)
        return texto_detectado, recorte_original, procesada_roi

    return texto_detectado, placa_detectada, procesada


def debe_ignorar_matricula(matricula):
    global ULTIMA_MATRICULA, ULTIMO_TIEMPO

    ahora = time.time()

    if ULTIMA_MATRICULA == matricula and (ahora - ULTIMO_TIEMPO) < BLOQUEO_SEGUNDOS:
        return True

    ULTIMA_MATRICULA = matricula
    ULTIMO_TIEMPO = ahora
    return False


def procesar_matricula(ser, matricula, origen):
    if debe_ignorar_matricula(matricula):
        return

    log_mensaje(origen, f"¡MATRÍCULA DETECTADA!: {matricula}")

    mostrar_espera(ser)

    nombre = es_matricula_autorizada(matricula)
    
    if nombre:
        log_mensaje("Autorización", f"OK - La matrícula {matricula} ({nombre}) está AUTORIZADA.")
        enviar(ser, CERRADA, "Matricula OK", matricula[:16])
        registrar_acceso(matricula, origen)
        time.sleep(2)
        abrir_barrera(ser, origen, nombre)
    else:
        log_mensaje("Autorización", f"DENEGADO - La matrícula {matricula} NO está autorizada.")
        enviar(ser, CERRADA, "Matricula NO", matricula[:16])
        time.sleep(2)
        denegar_paso(ser)


def guardar_debug(nombre, frame, recorte, procesada):
    cv2.imwrite(f"./{nombre}_frame.jpg", frame)
    cv2.imwrite(f"./{nombre}_roi.jpg", recorte)
    cv2.imwrite(f"./{nombre}_ocr.jpg", procesada)


def main():
    ser = serial.Serial(PUERTO, BAUDIOS, timeout=1)
    time.sleep(2.5)

    # Mostramos la IP durante 1 minuto antes de empezar
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

    while True:
        ok0, frame0 = cam0.read()
        if ok0:
            texto0, recorte0, proc0 = leer_matricula_desde_roi(frame0, ROI_CAM0)
            if es_matricula_valida(texto0):
                guardar_debug("cam0", frame0, recorte0, proc0)
                procesar_matricula(ser, normalizar_matricula(texto0), "Cámara 0")
            elif texto0:
                log_mensaje("Cámara 0", f"Texto ilegible o no es matrícula: '{texto0}'")
        else:
            log_mensaje("Cámara 0", "Error obteniendo imagen (Frame vacío)")

        ok2, frame2 = cam2.read()
        if ok2:
            texto2, recorte2, proc2 = leer_matricula_desde_roi(frame2, ROI_CAM2)
            if es_matricula_valida(texto2):
                guardar_debug("cam2", frame2, recorte2, proc2)
                procesar_matricula(ser, normalizar_matricula(texto2), "Cámara 2")
            elif texto2:
                log_mensaje("Cámara 2", f"Texto ilegible o no es matrícula: '{texto2}'")
        else:
            log_mensaje("Cámara 2", "Error obteniendo imagen (Frame vacío)")

        time.sleep(0.5)


if __name__ == "__main__":
    main()