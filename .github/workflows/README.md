# 🛠️ Documentación Técnica: Integración y Despliegue Continuo (CI/CD)

Este directorio aloja la configuración de **Integración y Despliegue Continuo (CI/CD)** para el Sistema de Automatización de Parking. Mediante GitHub Actions y un ejecutor local (**self-hosted runner**) instalado en la Raspberry Pi, el sistema se despliega y actualiza de manera completamente autónoma ante cada cambio confirmado en la rama `main`.

> **Actualización:** La canalización de despliegue respalda la reciente corrección que asegura que los logs y las marcas de tiempo se registren usando la zona horaria `Europe/Madrid`.

---

## 🔄 Flujo de Trabajo del Despliegue (`deploy.yml`)

El pipeline de despliegue automatizado realiza la siguiente secuencia de pasos críticos para garantizar la robustez física y lógica del hardware y software:

1. 📂 **Actualización de Código**: Realiza un `git pull` en caliente en el directorio local de la Raspberry Pi para obtener la versión más reciente.
2. 🔌 **Liberación del Puerto Serie**: Detiene temporalmente los contenedores de Docker (`docker compose down`) y destruye procesos huérfanos que usen los puertos `/dev/ttyACM*` o `/dev/ttyUSB*` mediante `fuser`, liberando de manera limpia el bus serie para la placa Arduino.
3. 🛠️ **Configuración de Arduino CLI**: Prepara el compilador `arduino-cli` en la Raspberry Pi e instala las librerías necesarias (`LiquidCrystal I2C`, `Servo` y el núcleo `arduino:avr`).
4. 🧼 **Flasheo de Limpieza**: Compila y sube un sketch de Arduino en blanco (`vacio.ino`) para resetear la memoria de la placa y vaciar buffers de comunicación serie.
5. ⚡ **Flasheo de Producción**: Compila y sube el firmware principal (`servo_and_lcd_serial.ino`) en caliente al puerto serie autodetectado.
6. 🐳 **Reconstrucción Docker**: Levanta de nuevo todos los microservicios con `docker compose up --build -d`, reiniciando los bucles de visión artificial con el nuevo software compilado.

---

## ⚙️ Requisitos del Runner

Para que este pipeline funcione correctamente, la Raspberry Pi debe estar configurada como un runner de GitHub autohospedado con los siguientes privilegios y utilidades del sistema:
- Acceso a `docker` y privilegios en el socket de docker sin sudo.
- La herramienta `arduino-cli` instalada y accesible en el PATH del runner.
- Permisos del sistema para operar los puertos serie `/dev/ttyACM0` y `/dev/ttyUSB0` (generalmente agregando el usuario del runner al grupo `dialout`).
