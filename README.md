<div align="center">
    <h1>【 Sistema de Automatización de Parking 】</h1>
    <h3>Proyecto Final ASIR</h3>
</div>

<div align="center"> 

![](https://img.shields.io/github/last-commit/Hector20062006/Proyecto-Final-ASIR?&style=for-the-badge&color=8ad7eb&logo=git&logoColor=D9E0EE&labelColor=1E202B)
![](https://img.shields.io/github/stars/Hector20062006/Proyecto-Final-ASIR?style=for-the-badge&logo=andela&color=86dbd7&logoColor=D9E0EE&labelColor=1E202B)
![](https://img.shields.io/github/repo-size/Hector20062006/Proyecto-Final-ASIR?color=86dbce&label=SIZE&logo=protondrive&style=for-the-badge&logoColor=D9E0EE&labelColor=1E202B)
</div>

Este sistema registra todos los logs y los accesos usando la zona horaria `Europe/Madrid`, de modo que las marcas de tiempo reflejen la hora y el día reales de Madrid.

<div align="center">
    <h2> descripción general </h2>
    <h3></h3>
</div>

Bienvenido al repositorio de mi Proyecto Final para el ciclo superior de Administración de Sistemas Informáticos en Red (ASIR). Este proyecto nace con la idea de automatizar el control de acceso de vehículos a un recinto cerrado (parking), mejorando la seguridad, rapidez e integridad informática frente a los sistemas tradicionales de tickets o llaveros mecánicos.

## Sobre el Proyecto

El objetivo principal de este proyecto es integrar conocimientos de administración de sistemas, programación y hardware libre para crear una solución integral. El sistema es capaz de:

1. **Identificar Vehículos:** Usando reconocimiento óptico de caracteres (OCR) a través de dos cámaras simultáneas procesadas en paralelo.
2. **Validar Permisos:** Comprobar si el vehículo tiene autorización para entrar, basándose en registros centralizados en MySQL.
3. **Control de Estado del Vehículo:** El sistema impide que un coche entre si ya está dentro del parking, y que salga si no está registrado como dentro.
4. **Accionar Hardware:** Controlar mecánicamente una barrera de acceso e interactuar con el usuario a través de una pantalla LCD y semáforos LED.
5. **Gestión de Red Automática:** Al arrancar, la Raspberry Pi detecta su IP física y la muestra en la pantalla del Arduino para facilitar la conexión.
6. **Experiencia Personalizada:** El sistema saluda por su nombre a los conductores en la entrada (`Bienvenido, [Nombre]`) y les despide en la salida (`Adios, [Nombre]` / `Buen viaje!`).
7. **Reporte de Mal Aparcado Seguro:** Sistema para que los profesores notifiquen vehículos mal estacionados mediante la matrícula, bloqueando el reporte si el coche no se encuentra físicamente registrado dentro del parking.
8. **Notificaciones en Tiempo Real:** Integración bidireccional con la API de Telegram para enviar alertas automáticas y reportes de incidencias en tiempo real.
9. **Gestión Administrativa:** Panel de control para que el administrador audite y filtre todas las incidencias reportadas.
10. **Auditoría Completa de Intentos Denegados:** Registro automático de tres tipos de incidencias en la tabla `intentos_denegados` con un campo `motivo` diferenciado: matrículas no registradas en el sistema (`NO_AUTORIZADO`), vehículos autorizados que ya están dentro e intentan volver a entrar (`ENTRADA_DUPLICADA`), y vehículos que intentan salir sin tener una entrada registrada (`SALIDA_SIN_ENTRADA`). Visualizable en el panel de administración con tarjetas de resumen, badges de color y filtros avanzados.
11. **Gestión de Ocupación en Tiempo Real:** Cálculo automático de plazas libres y ocupadas basado en el flujo de entradas y salidas registrado en la base de datos.
12. **Sensores Físicos de Aparcamiento:** Integración de microinterruptores que detectan en tiempo real si un vehículo está bien o mal aparcado en su plaza.
13. **Monitorización de Tiempos y Alertas:** Un temporizador interno vigila que el vehículo aparque correctamente en los 5 minutos posteriores a su entrada. Asimismo, si un vehículo aparcado deja su plaza (el sensor se pone verde) pero no registra su salida del recinto en un tiempo de gracia de 1 minuto, genera una alerta autónoma por Telegram.

## 📊 Presentación del Proyecto

A continuación, se expone la presentación oficial utilizada para defender este proyecto final. Puedes ver la portada directamente y desplegar el acordeón para visualizar todas las diapositivas en alta resolución, o bien descargar el archivo original.

<p align="center">
  <a href="./Parking_ASIR_Imad_Hector.pptx" target="_blank">
    <img src="presentacion/Diapositiva1.PNG" alt="Portada de la Presentación" width="85%" style="border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.15);">
  </a>
</p>

<details>
  <summary><b>📖 Desplegar y ver las 9 diapositivas de la presentación</b></summary>
  <br>
  <p align="center">
    <img src="presentacion/Diapositiva2.PNG" alt="Diapositiva 2" width="85%" style="border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.15);"><br><br>
    <img src="presentacion/Diapositiva3.PNG" alt="Diapositiva 3" width="85%" style="border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.15);"><br><br>
    <img src="presentacion/Diapositiva4.PNG" alt="Diapositiva 4" width="85%" style="border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.15);"><br><br>
    <img src="presentacion/Diapositiva5.PNG" alt="Diapositiva 5" width="85%" style="border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.15);"><br><br>
    <img src="presentacion/Diapositiva6.PNG" alt="Diapositiva 6" width="85%" style="border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.15);"><br><br>
    <img src="presentacion/Diapositiva7.PNG" alt="Diapositiva 7" width="85%" style="border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.15);"><br><br>
    <img src="presentacion/Diapositiva8.PNG" alt="Diapositiva 8" width="85%" style="border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.15);"><br><br>
    <img src="presentacion/Diapositiva9.PNG" alt="Diapositiva 9" width="85%" style="border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.15);">
  </p>
</details>

<p align="center">
  <b>📥 <a href="./Parking_ASIR_Imad_Hector.pptx">Descargar Presentación (.pptx)</a></b>
</p>

---

## 📁 Estructura del Proyecto

A continuación se muestra una vista detallada de la arquitectura de archivos del repositorio, organizada de forma modular para separar el software de control de hardware, el portal web y los servicios de orquestación en contenedores:

```text
Proyecto-Final-ASIR/
├── .github/                        # Integración y despliegue continuo automatizado (GitHub Actions)
│   └── workflows/                  # Flujos de trabajo (CI/CD) para el despliegue automático del proyecto
│       └── deploy.yml              # Pipeline de despliegue en Raspberry Pi (actualización de Git, compilación/subida de Arduino y Docker)
├── aplicacion-parking/             # Lógica de hardware, procesamiento de imágenes y OCR (Python/Arduino)
│   ├── lcd_serial/                 # Código de Arduino para el control de la pantalla LCD I2C
│   ├── servo_and_lcd_serial/       # Integración en Arduino de servo, pantalla LCD y semáforo LED
│   ├── servo_serial/               # Código de Arduino para el control del servomotor de la barrera
│   └── vacio/                      # Directorio de plantilla reservada para desarrollo
├── aplicacion-web/                 # Interfaz de administración y portal web de usuarios (PHP/CSS)
│   ├── admin/                      # Panel de administración (control barrera, auditoría, incidencias, etc.)
│   ├── alumno/                     # Panel del Alumno (plazas, estado)
│   ├── css/                        # Hojas de estilo Vanilla CSS para todo el sitio web
│   ├── img/                        # Recursos de imágenes, logos e iconos del portal
│   └── profesor/                   # Panel de Profesores (reportar mal aparcado, perfil, plazas)
├── docker/                         # Configuración y orquestación de contenedores Docker
│   ├── certbot/                    # Contenedor Certbot para SSL/TLS mediante Cloudflare DNS-01
│   ├── db/                         # Contenedor de base de datos MySQL (esquema y persistencia)
│   ├── python/                     # Contenedor para el núcleo de procesamiento OCR y hardware
│   └── web/                        # Contenedor para el servidor Apache y backend en PHP con SSL
├── presentacion/                   # Diapositivas del proyecto exportadas a imágenes de alta resolución
├── .env.example                    # Plantilla de configuración de variables de entorno (Telegram, MySQL, SSL)
└── docker-compose.yml              # Orquestación de toda la arquitectura del parking
```

*(Nota: Tienes un `README.md` técnico específico dentro de cada una de las carpetas principales detallando su respectiva instalación, diagramas de cableado y dependencias).*


## Tecnologías Principales y Disciplinas Aplicadas

En el desarrollo y conceptualización de este proyecto de ASIR se han tocado las siguientes ramas:
* **Sistemas Operativos:** Uso de automatismos en Linux y GitHub Actions para la descarga y ejecución de dependencias, y el flasheo automático del código de Arduino.
* **Fundamentos de Programación:** Desarrollo de la lógica central en Python con programación concurrente (threading) y programación de hardware en C++.
* **Hardware y Redes:** Intercomunicación del puerto Serial, control de 4 LEDs (semáforos de entrada y salida), display LCD I2C, servomotor para la barrera y microinterruptores mecánicos para las plazas.
* **Bases de Datos:** Almacenamiento, persistencia y consulta de estado en un SGBD relacional (MySQL/MariaDB).
* **Implantación de Aplicaciones Web:** Programación de interfaces y backend con PHP, HTML5 y CSS3.
* **Despliegue con Contenedores:** Orquestación de servicios (Base de datos, API Python y Web PHP) mediante Docker y Docker Compose, junto con CI/CD de despliegue continuo.

## 🚀 Despliegue Rápido con Docker

El proyecto cuenta con una configuración completa mediante **Docker Orquestado**. Puedes levantar todo el entorno de desarrollo y producción seguro con un solo comando:

```bash
docker compose up -d
```

Esto levantará los siguientes servicios en paralelo:
1. **db**: Contenedor MySQL 8.0 con la base de datos `parking_ASIR`.
2. **python_app**: Contenedor que ejecuta el procesamiento de imágenes (OpenCV + Tesseract) y la lógica de hardware, con detección dinámica de puertos USB del Arduino.
3. **web**: Contenedor Apache con PHP, configurado con soporte nativo de SSL/TLS (Puerto 443).
4. **certbot**: Contenedor de renovación de Let's Encrypt integrado con la API de Cloudflare para validación DNS-01 automática.

*(Puedes acceder a la aplicación web de forma segura en `https://tu-dominio.com` o a través del subdominio configurado).*

## 🔐 Configuración y Seguridad (HTTPS y APIs)

El sistema está completamente securizado y utiliza variables de entorno centralizadas. Sigue estos pasos para configurarlo:

1. Copia el archivo de ejemplo: `cp .env.example .env`
2. Edita el archivo `.env` e introduce las credenciales correspondientes:
   * **Telegram:** `TELEGRAM_BOT_TOKEN` y `TELEGRAM_CHAT_ID` para alertas automáticas.
   * **Base de Datos:** Credenciales seguras para MySQL (`DB_PASSWORD`, `DB_NAME`, etc.).
   * **SSL/TLS (Cloudflare):**
     * `DOMAIN_NAME`: Tu dominio o subdominio apuntando al servidor (soporta wildcards como `*.dominio.com`).
     * `CERTBOT_EMAIL`: Tu correo de registro para las alertas de Let's Encrypt.
     * `CLOUDFLARE_API_TOKEN`: Tu token de API con permisos de edición DNS en la zona de tu dominio.
3. El archivo `.env` está protegido por el `.gitignore` para asegurar la total privacidad de tus claves.

## 🛠️ Robustez, Auto-reparación y HTTPS Inteligente

Una de las características clave de este proyecto de fin de ciclo es su resiliencia y diseño auto-reparable:
*   **Arranque Seguro Sincronizado:** El contenedor web de Apache detecta si es la primera vez que se monta y espera automáticamente en bucle a que Certbot valide el dominio y descargue las firmas antes de iniciar la interfaz web. Cero fallos de arranque SSL.
*   **Validación DNS-01 (Sin Puertos Abiertos):** Al usar la API de Cloudflare para validar la propiedad del dominio, **no es necesario abrir el puerto 80 en tu router**, superando cualquier restricción de red de grado escolar o IPs locales de la Raspberry Pi.
*   **Gestión Dinámica de Hardware:** El script Python detecta dinámicamente el puerto USB asignado al Arduino. Ya no se rompe el despliegue al cambiar el hardware de puerto USB.
*   **Alertas Autónomas Inteligentes (Telegram):** El sistema notifica de forma autónoma al canal de Telegram si un vehículo excede los 5 minutos sin aparcar tras ingresar, o si abandona una plaza física sin registrar su salida del recinto en una ventana de gracia de 1 minuto, previniendo falsas alertas.
*   **Auto-inicialización de Base de Datos:** Tanto el backend web como el módulo Python crean las tablas necesarias al vuelo si no existen, incluyendo migraciones automáticas de columnas (compatibles con MariaDB 10.4) para no romper instalaciones existentes.
*   **CSS Centralizado:** Todos los estilos de la aplicación web se gestionan desde un único archivo `parking.css`. Ninguna página contiene bloques `<style>` inline, garantizando coherencia visual y mantenimiento sencillo.
*   **Despliegue Continuo (CI/CD):** Actualizaciones integradas mediante GitHub Actions para una entrega de software robusta.

---
*Autores: Imad Chakkour y Hector Ramírez*