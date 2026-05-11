# Sistema de Automatización de Parking (Proyecto Final ASIR)

Bienvenido al repositorio de mi Proyecto Final para el ciclo superior de Administración de Sistemas Informáticos en Red (ASIR). Este proyecto nace con la idea de automatizar el control de acceso de vehículos a un recinto cerrado (parking), mejorando la seguridad, rapidez e integridad informática frente a los sistemas tradicionales de tickets o llaveros mecánicos.

## Sobre el Proyecto

El objetivo principal de este proyecto es integrar conocimientos de administración de sistemas, programación y hardware libre para crear una solución integral. El sistema es capaz de:

1. **Identificar Vehículos:** Usando reconocimiento óptico de caracteres (OCR) a través de dos cámaras simultáneas procesadas en paralelo.
2. **Validar Permisos:** Comprobar si el vehículo tiene autorización para entrar, basándose en registros centralizados en MySQL.
3. **Control de Estado del Vehículo:** El sistema impide que un coche entre si ya está dentro del parking, y que salga si no está registrado como dentro.
4. **Accionar Hardware:** Controlar mecánicamente una barrera de acceso e interactuar con el usuario a través de una pantalla LCD y semáforos LED.
5. **Gestión de Red Automática:** Al arrancar, la Raspberry Pi detecta su IP física y la muestra en la pantalla del Arduino para facilitar la conexión.
6. **Experiencia Personalizada:** El sistema saluda por su nombre a los conductores en la entrada (`Bienvenido, [Nombre]`) y les despide en la salida (`Adios, [Nombre]` / `Buen viaje!`).
7. **Reporte de Mal Aparcado:** Sistema para que los profesores notifiquen vehículos mal estacionados mediante la matrícula.
8. **Notificaciones en Tiempo Real:** Integración con la API de Telegram para enviar alertas automáticas al canal común del parking.
9. **Gestión Administrativa:** Panel de control para que el administrador audite y filtre todas las incidencias reportadas.

## Estructura del Repositorio

El repositorio se divide para separar la funcionalidad técnica de la documentación general:

- 📂 **[`aplicacion-parking/`](./aplicacion-parking/)**: Esta carpeta contiene el **núcleo de la aplicación**. Aquí encontrarás todos los scripts en Python (OpenCV, Tesseract), los ficheros para la placa Arduino y utilidades en consola.
- 📂 **[`aplicacion-web/`](./aplicacion-web/)**: Contiene la **interfaz de administración y la base de datos**. Almacena el servidor web (PHP, HTML, CSS) y el esquema relacional MySQL usado para gestionar eficientemente qué vehículos están autorizados.

*(Nota: Tienes un `README.md` técnico específico dentro de cada una de estas carpetas detallando su instalación, cableado y requisitos.)*

## Tecnologías Principales y Disciplinas Aplicadas

En el desarrollo y conceptualización de este proyecto de ASIR se han tocado las siguientes ramas:
* **Sistemas Operativos:** Uso de automatismos en Linux y GitHub Actions para la descarga y ejecución de dependencias, y el flasheo automático del código de Arduino.
* **Fundamentos de Programación:** Desarrollo de la lógica central en Python con programación concurrente (threading) y programación de hardware en C++.
* **Hardware y Redes:** Intercomunicación del puerto Serial, control de 4 LEDs (semáforos de entrada y salida), display LCD I2C y servomotor para la barrera.
* **Bases de Datos:** Almacenamiento, persistencia y consulta de estado en un SGBD relacional (MySQL/MariaDB).
* **Implantación de Aplicaciones Web:** Programación de interfaces y backend con PHP, HTML5 y CSS3.
* **Despliegue con Contenedores:** Orquestación de servicios (Base de datos, API Python y Web PHP) mediante Docker y Docker Compose, junto con CI/CD de despliegue continuo.

## 🚀 Despliegue Rápido con Docker

El proyecto cuenta con una configuración completa mediante **Docker Compose**. Puedes levantar todo el entorno de desarrollo y pruebas con un solo comando:

```bash
docker compose up -d
```

Esto levantará los siguientes servicios:
1. **db**: Contenedor MySQL 8.0 con la base de datos `parking_ASIR`.
2. **python_app**: Contenedor que ejecuta el procesamiento de imágenes (OpenCV + Tesseract) en paralelo para ambas cámaras, y se conecta directamente a la base de datos para la validación de matrículas.
3. **web**: Contenedor con el servidor web para la interfaz gráfica de administración en PHP (Puerto 80).

*(Puedes acceder a la aplicación web navegando a `http://localhost` en tu navegador).*

## 🔐 Configuración y Seguridad

Para el correcto funcionamiento de las notificaciones de Telegram, el sistema utiliza variables de entorno. Sigue estos pasos para configurarlo:

1. Copia el archivo de ejemplo: `cp .env.example .env`
2. Edita el archivo `.env` e introduce tu `TELEGRAM_BOT_TOKEN` y tu `TELEGRAM_CHAT_ID`.
3. El archivo `.env` está protegido por el `.gitignore` para asegurar que tus tokens nunca se suban al repositorio público.

---
*Autores: Imad y Hector*