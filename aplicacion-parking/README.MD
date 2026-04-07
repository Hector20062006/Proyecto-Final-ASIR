# Documentación Técnica: Aplicación de Parking

Esta carpeta contiene el software y código necesario para que el sistema de control de barreras y reconocimiento de matrículas funcione físicamente

## Componentes del Software

1. **Software Central (Python):** 
   - **`camara.py`:** Es el archivo principal. Se encarga de capturar el vídeo simultáneo de 2 cámaras a través de OpenCV, detectar y recortar la región de las matrículas y preparar las imágenes para leer el texto usando \`pytesseract\`. Las matrículas se contrastan con las pre-autorizadas en memoria. Si es correcta, da la orden serial al Arduino.
   - **`envia_hola.py`:** Es un script de simulación/test que permite introducir una matrícula desde la terminal en lugar de usar cámaras, ideal para hacer pruebas de hardware sin todo el sistema montado.

2. **Código de Hardware (Arduino):**
   - **`servo_and_lcd_serial/servo_and_lcd_serial.ino`:** Es el sketch C++ que se sube al microcontrolador. Escucha el puerto Serial ('/dev/ttyACM0') esperando órdenes de Python con un protocolo estricto: `angulo|linea1|linea2`.
   - Controla el **Servomotor (PIN 9)** para abrir la barrera (ángulo 20º) o cerrarla (ángulo 105º).
   - Muestra mensajes en la **Pantalla LCD I2C** ("Acceso autorizado", "Acceso denegado", etc).

3. **Scripts de Instalación:** 
   - **`arduino.sh`:** Automatiza la descarga y configuración de \`arduino-cli\` (la versión bash para entorno Linux), lo cual facilita compilar el código desde ordenadores sin entorno gráfico (servidores o placas embebidas).

## Flujo de Trabajo

1. Al arrancar `camara.py`, se conectará por puerto serie USB al Arduino y mantendrá un bucle de escaneo de los dispositivos de vídeo (cam0 y cam2).
2. Si un coche llega, la cámara analiza los fotogramas, filtra ruido y lee sus letras de la matrícula (ej. `1234ABC`).
3. **Paso Autorizado**: Si la matrícula está en la base (lista de admitidas), Python manda una cadena como `20|Acceso autorizado|Pase`. El Arduino mostrará los textos en el display y pondrá el servo en 20 grados, levantando la barrera.
4. **Paso Denegado**: Si la matrícula no tiene permisos, se manda abrir con ángulo 105 (posición cerrada) con advertencias rojas y alertas, de forma que el coche se queda sin pasar.
5. El sistema guarda *logs* de capturas debug (`guardar_debug()`) de todos los intentos, muy útil para ajustar las zonas de interés (ROI) y depurar falsas lecturas del OCR.

## Requisitos e Instalación

### Sistema Base
- Python 3 instalado.
- Motor Tesseract-OCR nativo instalado en el S.O.
- Placa Arduino conectada por USB (o a nivel lógico) compatible operando a 9600 baudios.

### Librerías Python a Instalar
Debes instalar por pip los siguientes paquetes:
```bash
pip install opencv-python pytesseract pyserial
```

### Arduino y Wiring
- Conecta el protocolo de datos I2C (SDA/SCL) del LCD de forma estándar.
- Conecta la señal (amarillo/naranja) del Servomotor al **Pin 9** del Arduino.
- Si subes el script a un Linux (`/dev/ttyACM0`) u otro SO asegúrate de que el puerto que usa `camara.py` coincide. En plataformas Windows deberás editar este dato a formato `COMX`.