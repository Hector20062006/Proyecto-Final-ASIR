FROM python:3.11-slim

# Instalar dependencias del sistema requeridas para OpenCV y compilar EasyOCR
RUN apt-get update && apt-get install -y \
    libgl1 \
    libglib2.0-0 \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Instalar dependencias de Python directamente
# EasyOCR requiere PyTorch, que se instalará automáticamente como dependencia
RUN pip install --no-cache-dir "numpy<2" opencv-python-headless==4.9.0.80 easyocr==1.7.1 pyserial==3.5 mysql-connector-python==8.3.0

# Copiar el resto del código
COPY . /app

# Nota: El comando principal se ha sobrescrito en docker-compose.yml 
# con 'tail -f /dev/null' debido a las dependencias de hardware.
# Si se desea ejecutar manualmente: docker exec -it python_app python program.py
CMD ["python", "program.py"]
