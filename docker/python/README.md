# Dockerfile Python (OCR & Hardware)

Este directorio contiene el `Dockerfile` encargado de crear el entorno para ejecutar el núcleo de detección de matrículas. El contenedor Python está configurado para que los logs y los accesos se registren con la zona horaria `Europe/Madrid`.

## ¿Qué hace este contenedor?
1. **Imagen Base:** Utiliza `python:3.11-slim`, una imagen ligera de Linux con Python preinstalado.
2. **Librerías del Sistema:** Al tratar con visión artificial, instala los paquetes a nivel de sistema necesarios que no vienen con Python: `tesseract-ocr` (junto con el idioma español `tesseract-ocr-spa`), y librerías de sistema gráfico (`libgl1`, `libglib2.0-0`) esenciales para que OpenCV pueda procesar las imágenes.
3. **Dependencias de Python:** Instala las dependencias directamente mediante `pip` en la construcción de la imagen (OpenCV headless, PyTesseract, PySerial y conector de MySQL).
4. **Código Fuente:** Importa todos los scripts (como `program.py`) dentro del contenedor.
5. **Auditoría:** Gestiona el registro de accesos válidos y denegados directamente contra la base de datos MySQL.
6. **Notificaciones Autónomas:** Recibe las variables de entorno `TELEGRAM_BOT_TOKEN` y `TELEGRAM_CHAT_ID` en el archivo de orquestación, permitiendo enviar alertas directamente a Telegram de manera descentralizada y autónoma.

*Importante: Este contenedor está configurado en `docker-compose.yml` para desplegarse con privilegios elevados (`privileged: true`) y modo de red local (`network_mode: host`) en una Raspberry Pi (Linux). Gracias a esto, el script Python es capaz de autodetectar de forma dinámica el puerto USB del Arduino y comunicarse con las cámaras físicas conectadas sin necesidad de mapeos estáticos rígidos, garantizando la portabilidad del hardware.*
