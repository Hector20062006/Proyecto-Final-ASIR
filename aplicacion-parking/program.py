import cv2
import easyocr
import serial
import time
import re
import mysql.connector
import datetime

def log_mensaje(origen, mensaje):
    ahora = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    print(f"[{ahora}] [{origen}] {mensaje}", flush=True)

PUERTO = "/dev/ttyACM0"
BAUDIOS = 9600

CERRADA = 105
ABIERTA = 20

# Inicializar EasyOCR (usará CPU en la Raspberry Pi)
# Esto carga el modelo en memoria, por lo que tardará unos segundos al arrancar
log_mensaje("Sistema", "Inicializando Motor de IA (EasyOCR)...")
lector = easyocr.Reader(['es'], gpu=False)
log_mensaje("Sistema", "Motor de IA listo.")

def es_matricula_autorizada(matricula):
    try:
        conn = mysql.connector.connect(host="db", user="root", password="root", database="parking_ASIR")
        cursor = conn.cursor()
        cursor.execute("SELECT matricula FROM vehiculos WHERE matricula = %s", (matricula,))
        valida = cursor.fetchone() is not None
        conn.close()
        return valida
    except mysql.connector.Error as err:
        log_mensaje("Base de Datos", f"Error al validar matrícula: {err}")
        return False

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


def enviar(ser, angulo, linea1, linea2=""):
    comando = f"{angulo}|{linea1}|{linea2}\n"
    ser.write(comando.encode())
    ser.flush()


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


def abrir_barrera(ser):
    enviar(ser, ABIERTA, "Acceso", "autorizado")
    time.sleep(1.5)

    enviar(ser, ABIERTA, "Bienvenido", "Puede pasar")
    time.sleep(4)

    enviar(ser, CERRADA, "Cerrando", "Espere")
    time.sleep(2)

    enviar(ser, CERRADA, "Esperando", "vehiculo")
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
    # Permitir cualquier letra de la A a la Z (incluyendo vocales para las pruebas del usuario)
    return re.fullmatch(r"\d{4}[A-Z]{3}", texto) is not None


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


def preparar_imagen_para_ocr(roi):
    # EasyOCR usa Deep Learning, por lo que funciona mucho MEJOR con la imagen a color 
    # o en escala de grises suave. Binarizarla (blanco/negro puro) como hacíamos con 
    # Tesseract en realidad destruye detalles importantes que la IA necesita.
    gris = cv2.cvtColor(roi, cv2.COLOR_BGR2GRAY)
    
    # Solo mejoramos un poco el contraste y la ampliamos, sin binarizar
    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8,8))
    gris = clahe.apply(gris)
    
    # Ampliar para que las letras sean más grandes
    procesada = cv2.resize(gris, None, fx=2, fy=2, interpolation=cv2.INTER_CUBIC)
    
    return procesada


def leer_matricula_desde_roi(frame, roi):
    recorte = recortar_roi(frame, roi)
    procesada = preparar_imagen_para_ocr(recorte)

    # EasyOCR devuelve una lista de tuplas: (caja, texto, confianza)
    # Ejemplo: [([[10, 10], [100, 10], [100, 40], [10, 40]], '1234 BCD', 0.89)]
    # Pasamos una lista de caracteres permitidos para que la IA no se invente símbolos
    resultados = lector.readtext(procesada, allowlist='0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ')
    
    texto_detectado = ""
    for (bbox, texto, confianza) in resultados:
        # Solo procesamos si la IA está bastante segura de lo que lee (confianza > 25%)
        if confianza > 0.25:
            texto_limpio = limpiar_texto(texto)
            if len(texto_limpio) > 1:
                texto_detectado += texto_limpio

    return texto_detectado, recorte, procesada


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

    if es_matricula_autorizada(matricula):
        log_mensaje("Autorización", f"OK - La matrícula {matricula} está AUTORIZADA.")
        enviar(ser, CERRADA, "Matricula OK", matricula[:16])
        registrar_acceso(matricula, origen)
        time.sleep(2)
        abrir_barrera(ser)
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

        time.sleep(1)


if __name__ == "__main__":
    main()