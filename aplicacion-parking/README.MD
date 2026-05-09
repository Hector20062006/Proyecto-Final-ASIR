# Documentación Técnica: Aplicación de Parking (Reconocimiento de Matrículas)

Esta carpeta contiene el cerebro del sistema de control de barreras. Aquí se aloja el software escrito en Python (`program.py`) que gestiona las cámaras, la Inteligencia Artificial (OCR) y la comunicación con el hardware.

## 🧠 El Archivo Principal: `program.py`

El archivo `program.py` es el núcleo central del proyecto. Se ejecuta de forma continua (bucle infinito) analizando vídeo en tiempo real. Recientemente ha sido mejorado para ser altamente resistente a fallos en entornos Docker y Raspberry Pi.

### Funcionalidades Clave de `program.py`:

1. **Gestión de Hardware Seguro:**
   - Se conecta al Arduino por puerto Serie (`/dev/ttyACM0` a 9600 baudios).
   - Inicia la captura de dos cámaras simultáneas de forma dinámica. Si una cámara falla o se desconecta, el sistema genera un log crítico sin romper el resto de los servicios.

2. **Preprocesado de Imagen Avanzado (OpenCV):**
   - Para que la lectura de matrículas no falle por culpa del sol o las sombras, el código recorta un área de interés (`ROI`) e invoca la función `preparar_imagen_para_ocr()`.
   - **CLAHE:** Ecualiza la iluminación de la imagen en tiempo real.
   - **Filtro de Otsu:** Calcula matemáticamente el punto perfecto para convertir la foto a blanco y negro puro, independientemente de la luz exterior.
   - **Operaciones Morfológicas:** Cierra pequeños "agujeros" en las letras y elimina puntitos de ruido para que la imagen quede perfecta.

3. **Inteligencia Artificial (Tesseract OCR):**
   - Utiliza `pytesseract` para extraer el texto.
   - **Whitelist estricta:** Se le ha configurado una lista blanca (`0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ`) para evitar que lea símbolos raros en la carretera o en la chapa del coche.

4. **Conexión Directa a Base de Datos MySQL:**
   - **Validación:** Consulta si la matrícula detectada existe en la tabla `vehiculos` (`es_matricula_autorizada()`). Está protegido con un bloque `try/except` para no colgarse si la base de datos pierde conexión.
   - **Historial (NUEVO):** Automáticamente registra el acceso en la tabla `accesos`, guardando la matrícula, la hora exacta y si fue una "ENTRADA" (Cámara 0) o "SALIDA" (Cámara 2) mediante la función `registrar_acceso()`.

5. **Sistema de Logs para Docker:**
   - Todo se registra mediante una función propia `log_mensaje()`. 
   - Esta función añade la fecha y hora exactas y fuerza el volcado en pantalla (`flush=True`), lo que permite monitorear todo en tiempo real usando `docker logs -f python_app`.

## Componentes Secundarios

- **Semáforos LED:** El sistema cuenta con 4 LEDs (2 verdes y 2 rojos) que actúan como semáforos independientes para indicar la entrada (Cámara 0) o salida (Cámara 2) visualmente.
- **`arduino.sh`:** Script para instalar y configurar `arduino-cli` en entornos Linux.
- **Códigos de Arduino (`servo_and_lcd_serial.ino`):** Contiene el firmware en C++ que se sube al microcontrolador. Mueve el servomotor de forma suave y progresiva, apaga la señal PWM cuando está en reposo para eliminar el ruido eléctrico (jitter), actualiza la pantalla LCD y controla el estado de los semáforos LED según los comandos del puerto serie.

## Instrucciones de Instalación y Despliegue

### 🤖 CI/CD Automático (GitHub Actions)
La subida de código al Arduino está totalmente automatizada. Cualquier cambio en la rama `main` disparará un flujo de trabajo (`deploy.yml`) que usará `arduino-cli` para compilar y flashear el Arduino conectado a `/dev/ttyACM0` en la Raspberry Pi.

### 🐳 Despliegue en Raspberry Pi (Docker)
Este es el método recomendado para los contenedores de software. El código está preparado para ejecutarse dentro del servicio `python_app` del archivo `docker-compose.yml`.
- Requiere ejecutar el contenedor en modo privilegiado (`privileged: true`) y mapear correctamente los dispositivos de hardware (`/dev/videoX`).
- Si modificas el área de captura (ROI) en el código, simplemente reinicia el contenedor con `docker-compose restart python_app`.

### 💻 Pruebas Locales (Windows/Linux)
Si quieres probar el script `program.py` en tu ordenador sin usar Docker:
1. Instala Python 3 y Tesseract OCR en tu sistema.
2. Instala las librerías necesarias:
   ```bash
   pip install opencv-python pytesseract pyserial mysql-connector-python
   ```
3. Ejecuta el script: `python program.py`. *(Nota: En Windows, deberás cambiar la variable PUERTO de `/dev/ttyACM0` a tu puerto correspondiente, ej. `COM3`).*