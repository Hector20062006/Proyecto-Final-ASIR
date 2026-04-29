import cv2
import pytesseract
import serial
import time
import re
import mysql.connector

PUERTO = "/dev/ttyACM0"
BAUDIOS = 9600

CERRADA = 105
ABIERTA = 20

def es_matricula_autorizada(matricula):
    conn = mysql.connector.connect(host="db", user="root", password="root", database="parking_ASIR")
    cursor = conn.cursor()
    cursor.execute("SELECT matricula FROM vehiculos WHERE matricula = %s", (matricula,))
    valida = cursor.fetchone() is not None
    conn.close()
    return valida

# Zona de lectura de cada cámara: (x, y, ancho, alto)
# AJUSTA ESTOS VALORES SEGÚN TU IMAGEN
ROI_CAM0 = (180, 180, 280, 120)
ROI_CAM2 = (180, 180, 280, 120)

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

    enviar(ser, CERRADA, "Parking", "Cerrado")
    time.sleep(1)


def denegar_paso(ser):
    enviar(ser, CERRADA, "Acceso", "denegado")
    time.sleep(2.5)

    enviar(ser, CERRADA, "Matricula", "no valida")
    time.sleep(2)

    enviar(ser, CERRADA, "Parking", "Cerrado")
    time.sleep(1)


def limpiar_texto(texto):
    texto = texto.upper()
    texto = texto.replace(" ", "")
    texto = texto.replace("\n", "")
    texto = texto.replace("\f", "")
    texto = re.sub(r"[^A-Z0-9]", "", texto)
    return texto


def es_matricula_valida(texto):
    return re.fullmatch(r"\d{4}[A-Z]{3}", texto) is not None


def configurar_camara(indice):
    cap = cv2.VideoCapture(indice, cv2.CAP_V4L2)

    if not cap.isOpened():
        return None

    cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
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
    gris = cv2.cvtColor(roi, cv2.COLOR_BGR2GRAY)
    gris = cv2.GaussianBlur(gris, (3, 3), 0)
    gris = cv2.resize(gris, None, fx=2, fy=2, interpolation=cv2.INTER_CUBIC)
    _, binaria = cv2.threshold(gris, 140, 255, cv2.THRESH_BINARY)
    return binaria


def leer_matricula_desde_roi(frame, roi):
    recorte = recortar_roi(frame, roi)
    procesada = preparar_imagen_para_ocr(recorte)

    texto = pytesseract.image_to_string(
        procesada,
        config="--psm 7 -c tessedit_char_whitelist=ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
    )

    texto = limpiar_texto(texto)
    return texto, recorte, procesada


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

    print(f"{origen} -> Matrícula detectada: {matricula}")

    mostrar_espera(ser)

    if es_matricula_autorizada(matricula):
        print("OK:", matricula, "autorizada")
        enviar(ser, CERRADA, "Matricula OK", matricula[:16])
        time.sleep(2)
        abrir_barrera(ser)
    else:
        print("NO:", matricula, "no autorizada")
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

    enviar(ser, CERRADA, "Parking", "Cerrado")
    time.sleep(1)

    cam0 = configurar_camara(0)
    cam2 = configurar_camara(2)

    if cam0 is None:
        print("No se pudo abrir la cámara 0")
        ser.close()
        return

    if cam2 is None:
        print("No se pudo abrir la cámara 2")
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
                print(f"Cámara 0 -> Texto detectado: {texto0}")

        else:
            print("Cámara 0 -> Error Comprobando frame")

        ok2, frame2 = cam2.read()
        if ok2:
            texto2, recorte2, proc2 = leer_matricula_desde_roi(frame2, ROI_CAM2)
            if es_matricula_valida(texto2):
                guardar_debug("cam2", frame2, recorte2, proc2)
                procesar_matricula(ser, normalizar_matricula(texto2), "Cámara 2")
            elif texto2:
                print(f"Cámara 2 -> Texto detectado: {texto2}")

        else:
            print("Cámara 2 -> Error Comprobando frame")

        time.sleep(1)


if __name__ == "__main__":
    main()