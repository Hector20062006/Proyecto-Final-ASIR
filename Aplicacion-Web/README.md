# Plataforma de Gestión (Aplicación Web)

Esta carpeta del repositorio está destinada a contener la **interfaz de administración web** y la **Base de Datos** del sistema de parking, ofreciendo una forma visual y amigable de controlar los accesos y los vehículos permitidos.

## Tecnologías de la Aplicación Web

El panel de administración se basa en el stack tradicional y versátil ideal para proyectos de ASIR:

- **MySQL / MariaDB:** El pilar central del sistema. Aquí residirá la base de datos `parking_db` con una tabla principal `matriculas_autorizadas`. Pasamos del método local de guardar las matrículas en el código de Python a tenerlas en una base de datos relacional potente, escalable y persistente.
- **PHP:** El lenguaje de servidor que hace de "puente". Se encarga de conectarse mediante PDO/MySQLi a la base de datos para extraer los datos de forma segura, y de inyectar esa información en la interfaz gráfica. También recibe las peticiones (formularios) cuando queremos dar de alta o eliminar un vehículo.
- **HTML5:** Define toda la estructura de nuestro panel de administración web, los formularios interactivos, los botones y las tablas donde se visualizará la lista de vehículos permitidos.
- **CSS3:** Le proporciona el acabado visual. Gracias a sus hojas de estilo, el sistema de información pasará a verse atractivo, organizado y responsivo para el usuario administrador.

## Integración con el Sistema de Visión Artificial

La pieza fundamental que une esta carpeta web con la aplicación principal en Python (`camara.py`) es la Base de Datos. 

En lugar de que `camara.py` consulte un listado de matrículas en su propio código, ahora se le integrará el driver/conector nativo de `mysql-connector-python`. De este modo:
1. El script de la cámara lee una matrícula del vídeo de entrada.
2. Hace una petición tipo `SELECT` directamente a la base de datos MySQL por esa matrícula capturada.
3. Si el registro existe porque previamente alguien lo dio de alta usando esta Aplicación Web, el Arduino recibirá la orden de subir la barrera. En caso contrario, se le denegará el paso al coche.

## Instalación y Configuración

Para levantar el entorno completo se requiere de un stack del tipo XAMPP o LAMP:
1. Iniciar un servicio de servidor local `Apache` y `MySQL`.
2. Crear la base de datos dentro del phpMyAdmin para empezar a almacenar matrículas.
3. Situar todos los ficheros `.php`, `.html` y `.css` en la carpeta expuesta del servidor (`/htdocs` o `/var/www/html`).
4. Modificar el fichero de conexión de la BBDD del proyecto para asignar el usuario y la contraseña local del servidor de base de datos.
