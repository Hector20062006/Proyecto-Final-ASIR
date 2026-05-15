# Documentación Técnica: Aplicación de Parking (Reconocimiento de Matrículas)

Esta carpeta contiene el cerebro del sistema de control de barreras. Aquí se aloja el software escrito en Python (`program.py`) que gestiona las cámaras, el OCR y la comunicación con el hardware Arduino.

## 🧠 El Archivo Principal: `program.py`

El archivo `program.py` es el núcleo central del proyecto. Se ejecuta de forma continua analizando vídeo en tiempo real desde dos cámaras simultáneas procesadas en **hilos paralelos independientes**.

---

### Funcionalidades Clave

#### 1. 🚗 Detección de Matrículas (OCR Mejorado)

El sistema utiliza una pipeline de varias etapas para maximizar la precisión de lectura:

- **Localización por Contornos:** Antes del OCR, se busca el rectángulo de la matrícula dentro del ROI usando detección de bordes (Canny) y análisis de contornos. Esto elimina el ruido de fondo del vehículo.
- **Pre-procesado de Imagen:**
  - **CLAHE:** Ecualización de iluminación adaptativa para manejar sombras y reflejos.
  - **Gaussian Blur (3×3):** Elimina ruido de sal y pimienta de forma rápida.
  - **Umbral Adaptativo / Otsu:** Binarización robusta ante cambios de luz.
  - **Reescalado inteligente:** Solo se amplía la imagen si es demasiado pequeña para Tesseract.
- **Sistema de Doble Intento:** Si el método de pre-procesado principal falla, se prueba automáticamente una segunda variante (Otsu vs. Adaptativo).
- **Validación de Formato:** Se acepta el formato estándar español: 4 dígitos (0-9) + 3 letras (`[0-9]{4}[A-Z]{3}`).

#### 2. ⚡ Procesamiento Paralelo (Threading)

Las dos cámaras funcionan en **hilos independientes** (`threading.Thread`):
- **Cámara 0:** Cámara de **ENTRADA** → ~3 lecturas/segundo.
- **Cámara 2:** Cámara de **SALIDA** → ~3 lecturas/segundo.
- El acceso al puerto serie (Arduino) está protegido con `threading.Lock()` para evitar conflictos entre hilos.

#### 3. 🗳️ Sistema de Votos (Anti-Falsos Positivos)

Para evitar que un frame borroso o texto similar a una matrícula dispare la barrera incorrectamente:
- Una matrícula debe ser detectada **2 veces seguidas** (`VOTOS_NECESARIOS = 2`) por la misma cámara antes de procesarse.
- Los votos de otras matrículas candidatas se resetean automáticamente.

#### 4. 🔒 Control de Estado del Vehículo

El sistema consulta la tabla `accesos` en la base de datos para conocer el **último movimiento** de cada vehículo:

| Situación | Resultado | Mensaje en LCD | Registro en BD |
|---|---|---|---|
| Matrícula autorizada y estado OK | **Acceso permitido** | Secuencia bienvenida/despedida | Tabla `accesos` |
| Matrícula autorizada pero estado mal | **Bloqueado** | `Ya en parking` / `No esta dentro` | Solo Log |
| **Matrícula NO autorizada** | **DENEGADO** | `Matricula NO` / `Acceso denegado` | Tabla `intentos_denegados` |

#### 5. 🛡️ Robustez y Auto-inicialización
El script incluye una capa de seguridad para la base de datos: antes de registrar cualquier intento denegado, verifica la existencia de la tabla necesaria y la crea automáticamente si no existe. Esto evita fallos críticos ante una base de datos recién instalada.

#### 6. 💬 Mensajes Personalizados en LCD

El sistema diferencia la secuencia de mensajes según la dirección del movimiento:

| Paso | **Entrada** (Cámara 0) | **Salida** (Cámara 2) |
|---|---|---|
| 1 (detección) | `Bienvenido` → `Comprobando matrícula...` | `Adios` → `Comprobando matrícula...` |
| 2 (validado) | `Matricula OK` | `Matricula OK` |
| 3 (barrera) | `Acceso / autorizado` | `Acceso / autorizado` |
| 4 (mensaje) | `Bienvenido / [Nombre]` | `Adios / [Nombre]` |
| 5 (paso) | `Puede pasar` | `Buen viaje!` |
| 6 (cierre) | `Cerrando / Espere` | `Cerrando / Espere` |

#### 6. 🌐 Secuencia de Inicio y Red

Al arrancar el contenedor, el sistema detecta automáticamente la IP física de la Raspberry Pi y la muestra en la pantalla LCD durante **60 segundos**, facilitando el acceso remoto sin periféricos externos.

#### 7. 📋 Sistema de Logs

Todo se registra con fecha y hora exactas mediante `log_mensaje()`, con `flush=True` para visualización en tiempo real:
```bash
docker logs -f python_app
```

---

## 📡 Protocolo Serial (Comunicación con Arduino)

El formato de comando enviado al Arduino es:
```
ángulo|línea_lcd_1|línea_lcd_2|estado_led\n
```

| Parámetro | Valores |
|---|---|
| `ángulo` | `20` (abierta) / `100` (cerrada) |
| `línea_lcd_1` | Texto línea superior del LCD (máx. 16 chars) |
| `línea_lcd_2` | Texto línea inferior del LCD (máx. 16 chars) |
| `estado_led` | `0` Reposo (rojos), `1` Verde Entrada, `2` Verde Salida, `3` Test (todos) |

---

## Componentes Secundarios

- **Semáforos LED:** 4 LEDs (2 verdes + 2 rojos) que indican el estado de entrada/salida visualmente.
- **`arduino.sh`:** Script para instalar y configurar `arduino-cli` en entornos Linux.
- **Firmware Arduino (`servo_and_lcd_serial.ino`):** Firmware en C++ que mueve el servomotor suavemente, gestiona la pantalla LCD I2C y controla los 4 LEDs. Incluye modo test (estado `3`) y calibración de ángulo cerrado a `100º`.

---

## 🐳 Instrucciones de Despliegue

### CI/CD Automático (GitHub Actions)
Cualquier push a `main` disparará el flujo de trabajo que compilará y flasheará el Arduino en la Raspberry Pi automáticamente.

### Despliegue Docker (Recomendado)
```bash
docker compose up -d
```
Requiere modo privilegiado (`privileged: true`) y mapeo de dispositivos de hardware en `docker-compose.yml`.

### Pruebas Locales
```bash
pip install opencv-python pytesseract pyserial mysql-connector-python numpy
python program.py
```
> **Nota:** En Windows, cambiar `PUERTO = "/dev/ttyACM0"` por el puerto COM correspondiente (ej. `COM3`).