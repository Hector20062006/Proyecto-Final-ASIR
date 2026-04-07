import serial
import time

PUERTO = "/dev/ttyACM0"
BAUDIOS = 9600

CERRADA = 105
ABIERTA = 20

AUTORIZADAS = {
    "1234ABC",
    "5678DEF",
    "GR1234AA",
}

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
        ("Leyendo su", "matricula   "),
        ("Leyendo su", "matricula.  "),
        ("Leyendo su", "matricula.. "),
        ("Leyendo su", "matricula..."),
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

def main():
    ser = serial.Serial(PUERTO, BAUDIOS, timeout=1)
    time.sleep(2.5)

    enviar(ser, CERRADA, "Parking", "Cerrado")
    time.sleep(1)

    while True:
        m = input("Introduce matricula (o 'salir'): ")
        if m.strip().lower() == "salir":
            break

        mat = normalizar_matricula(m)

        mostrar_espera(ser)

        if mat in AUTORIZADAS:
            print("OK:", mat, "autorizada")
            enviar(ser, CERRADA, "Matricula OK", mat[:16])
            time.sleep(2)
            abrir_barrera(ser)
        else:
            print("NO:", mat, "no autorizada")
            enviar(ser, CERRADA, "Matricula NO", mat[:16])
            time.sleep(2)
            denegar_paso(ser)

    ser.close()

if __name__ == "__main__":
    main()