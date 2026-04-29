# Dockerfile Python (OCR & Hardware)

Este directorio contiene el `Dockerfile` encargado de crear el entorno para ejecutar el núcleo de detección de matrículas.

## ¿Qué hace este contenedor?
1. **Imagen Base:** Utiliza `python:3.11-slim`, una imagen ligera de Linux con Python preinstalado.
2. **Librerías del Sistema:** Al tratar con visión artificial, instala los paquetes a nivel de sistema necesarios que no vienen con Python: `tesseract-ocr` (junto con el idioma español `tesseract-ocr-spa`), y librerías de sistema gráfico (`libgl1-mesa-glx`, `libglib2.0-0`) esenciales para que OpenCV pueda procesar las imágenes.
3. **Dependencias de Python:** Instala las dependencias directamente mediante `pip` en la construcción de la imagen (OpenCV headless, PyTesseract, PySerial y conector de MySQL).
4. **Código Fuente:** Importa todos los scripts (como `program.py`) dentro del contenedor.

*Importante: Este contenedor está configurado en `docker-compose.yml` para desplegarse directamente en una Raspberry Pi (Linux). El archivo de orquestación mapea directamente el Arduino (`/dev/ttyACM0`) y las cámaras (`/dev/video0`, `/dev/video2`) al contenedor. Si despliegas esto en Windows o en una placa sin cámaras conectadas, el contenedor dará error y se reiniciará.*
