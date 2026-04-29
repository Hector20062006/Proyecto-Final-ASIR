# Documentación Técnica: Aplicación de Parking

Esta carpeta contiene el software y código necesario para que el sistema de control de barreras y reconocimiento de matrículas funcione físicamente.

## Componentes del Software

1. **Software Central (Python):** 
   - **`program.py`:** Es el archivo principal. Se encarga de capturar el vídeo simultáneo de 2 cámaras a través de OpenCV, detectar y recortar la región de las matrículas y preparar las imágenes para leer el texto usando `pytesseract`. Las matrículas se consultan en tiempo real contra la base de datos MySQL. Si la matrícula existe, da la orden serial al Arduino para levantar la barrera.
   - **`envia_hola.py`:** Es un script de simulación/test que permite introducir una matrícula desde la terminal en lugar de usar cámaras, ideal para hacer pruebas de hardware sin todo el sistema montado.

2. **Código de Hardware (Arduino):**
   - **`servo_and_lcd_serial/servo_and_lcd_serial.ino`:** Es el sketch C++ que se sube al microcontrolador. Escucha el puerto Serial ('/dev/ttyACM0') esperando órdenes de Python con un protocolo estricto: `angulo|linea1|linea2`.
   - Controla el **Servomotor (PIN 9)** para abrir la barrera (ángulo 20º) o cerrarla (ángulo 105º).
   - Muestra mensajes en la **Pantalla LCD I2C** ("Acceso autorizado", "Acceso denegado", etc).

3. **Scripts de Instalación:** 
   - **`arduino.sh`:** Automatiza la descarga y configuración de \`arduino-cli\` (la versión bash para entorno Linux), lo cual facilita compilar el código desde ordenadores sin entorno gráfico (servidores o placas embebidas).

## Flujo de Trabajo (Lógica del Programa)

El archivo principal `program.py` sigue una secuencia lógica clara de 5 pasos para garantizar el control de acceso:

1. **Conexión de Hardware**: Al arrancar, el script inicializa la comunicación por puerto serie (USB) con el Arduino y activa la captura de vídeo simultánea de las dos cámaras conectadas (`cam0` y `cam2`).
2. **Procesamiento de Imagen**: Mantiene un bucle infinito que escanea los fotogramas. Cuando detecta movimiento, aplica filtros de contraste y recorta específicamente las zonas de interés (ROI) para aislar la placa de la matrícula.
3. **Reconocimiento (OCR)**: La imagen recortada y filtrada se procesa con el motor `pytesseract`, que se encarga de extraer el texto limpio (letras y números) de la matrícula detectada.
4. **Validación en Base de Datos**: La matrícula leída se consulta inmediatamente contra la base de datos MySQL del proyecto.
5. **Acción Mecánica (Arduino)**: 
   - **Paso Autorizado**: Si la matrícula está registrada en la tabla de vehículos, Python manda la instrucción `20|Acceso autorizado|Pase` por puerto serie. El Arduino mostrará los textos en el display LCD y pondrá el servomotor a 20 grados, levantando la barrera.
   - **Paso Denegado**: Si la matrícula no existe en la base de datos, envía la orden para mantener el servo a 105 grados (posición cerrada) y muestra un mensaje de denegación en la pantalla.

*(El sistema también guarda automáticamente capturas de imagen de todos los intentos mediante `guardar_debug()`, lo cual es muy útil para ajustar el enfoque de las cámaras).*

## Requisitos e Instalación

### 🐳 Despliegue en Contenedores (Docker)
Este módulo se encuentra integrado en la arquitectura general del proyecto mediante Docker Compose. Al ejecutar `docker compose up -d` desde la raíz del proyecto, el entorno de esta carpeta se despliega en el contenedor `python_app`.

### 💻 Instalación Local (Para uso de Hardware y Cámaras)

Para usar las cámaras físicas y la comunicación por puerto serie directamente desde tu PC, es necesario configurar el entorno local:

#### Sistema Base
- Python 3 instalado.
- Motor Tesseract-OCR nativo instalado en el S.O.
- Placa Arduino conectada por USB (o a nivel lógico) compatible operando a 9600 baudios.

### Librerías Python a Instalar
Debes instalar por pip los siguientes paquetes:
```bash
pip install opencv-python pytesseract pyserial mysql-connector-python
```

### Arduino y Wiring
- Conecta el protocolo de datos I2C (SDA/SCL) del LCD de forma estándar.
- Conecta la señal (amarillo/naranja) del Servomotor al **Pin 9** del Arduino.
- Si subes el script a un Linux (`/dev/ttyACM0`) u otro SO asegúrate de que el puerto que usa `program.py` coincide. En plataformas Windows deberás editar este dato a formato `COMX`.