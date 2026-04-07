# Sistema de Control de Acceso con Reconocimiento de Matrículas (Proyecto Final ASIR)

## Descripción del Proyecto
Este proyecto es un sistema de control de acceso automatizado para vehículos, desarrollado como Proyecto Final para el grado de Administración de Sistemas Informáticos en Red (ASIR). Combina visión por computadora (OpenCV y Tesseract OCR) con hardware libre (placa Arduino, servomotor y pantalla LCD I2C) para la gestión automática de un parking según las matrículas autorizadas detectadas.

## Componentes y Arquitectura

El proyecto gira en torno a varias piezas principales de hardware y software interconectadas:

1. **Software Central (Python):** 
   - **`camara.py`:** Es el núcleo del proyecto. Se encarga de procesar la señal de vídeo simultánea de 2 cámaras a través de OpenCV, recortando la región de interés (ROI) y aislando la matrícula mediante procesamiento de imagen (escalado en escala de grises, desenfoque gaussiano, y binarización).
   - A través de la librería `pytesseract` (Tesseract OCR), el software lee los caracteres.
   - Las matrículas detectadas se comparan contra un diccionario en memoria de matrículas autorizadas (ej. *1234ABC*, *5678DEF*).
   - Una vez comprobada la validez, da la orden mediante protocolo Serial al Arduino informando si se debe abrir o no la barrera.
   - **`envia_hola.py`:** Es un script de control manual y testeo que permite introducir una matrícula por la terminal en lugar de usar la visión por ordenador, sirviendo para verificar la respuesta mecánica y electrónica del sistema.

2. **Electrónica (Arduino):**
   - **`servo_and_lcd_serial.ino`:** Código fuente para la placa del microcontrolador.
   - **Comunicador Serial:** Se queda en bucle de escucha del puerto serie ('/dev/ttyACM0') esperando órdenes de Python con el formato `angulo|linea1|linea2`.
   - **Pantalla LCD 16x2 I2C (`LiquidCrystal_I2C`):** Informa al usuario visualmente del estado en dos líneas ("Bienvenido", "Comprobando", "Acceso Autorizado", "Acceso Denegado", etc).
   - **Servomotor:** Conectado al PIN 9 del Arduino. Controla el acceso físico pivotando desde los grados de bloqueo (105º) hasta la apertura total (20º).

3. **Utilidades de Sistema (`arduino.sh`):** Un script bash preparado para automatizar la descarga e instalación de la versión de línea de comandos del entorno Arduino (`arduino-cli`), facilitando el despliegue del proyecto y la compilación/subida del código en infraestructuras headless (ej: Raspberry Pi / Linux).

## Requisitos y Configuración del Entorno

### Dependencias Software
- **Python 3:**
  - `opencv-python` (`cv2`) para el manejo de cámaras e imágenes.
  - `pytesseract` para extraer el texto de las matrículas leídas.
  - `pyserial` para la comunicación del dispositivo con Python.
- **Tesseract OCR:** Ha de estar instalado en el sistema local a nivel de binarios.
- **Arduino IDE / Arduino CLI:** Necesario para flashear la placa. Las librerías de Arduino requeridas son `Wire.h`, `Servo` y `LiquidCrystal_I2C` (dirección habitual 0x27).

### Funcionamiento Paso a Paso

1. Se ejecuta el software principal (`camara.py`) y este establece comunicación con el Arduino y comienza a leer las dos fuentes de vídeo.
2. Tras aproximarse un coche, la cámara extrae el `frame` e intenta una validación del texto para asegurar que es un formato válido de matrícula (ej: 4 números y 3 letras).
3. Si la matrícula es conocida, el Arduino recibe la petición de colocar el servomotor en posición 20 y muestra en la pantalla `Acceso autorizado` y `Puede pasar`.
4. Si la matrícula no tiene permisos, se muestra en el LCD `Acceso denegado`, `Matrícula no válida` y el cierre lógico del servomotor se mantiene inviolable a 105º.
5. Existe un guardado automático (`guardar_debug()`) en el interior de `camara.py` de imágenes y recortes cada vez que se detecta un coche, muy útil para fines de diagnóstico y auditoría.