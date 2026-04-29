FROM python:3.11-slim

# Instalar dependencias del sistema requeridas para OCR y OpenCV
RUN apt-get update && apt-get install -y \
    tesseract-ocr \
    tesseract-ocr-spa \
    libgl1-mesa-glx \
    libglib2.0-0 \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Instalar dependencias de Python directamente
RUN pip install --no-cache-dir opencv-python-headless==4.9.0.80 pytesseract==0.3.10 pyserial==3.5 mysql-connector-python==8.3.0

# Copiar el resto del código
COPY . /app

# Nota: El comando principal se ha sobrescrito en docker-compose.yml 
# con 'tail -f /dev/null' debido a las dependencias de hardware.
# Si se desea ejecutar manualmente: docker exec -it python_app python program.py
CMD ["python", "program.py"]
