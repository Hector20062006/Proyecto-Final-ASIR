# Sistema de Automatización de Parking (Proyecto Final ASIR)

Bienvenido al repositorio de mi Proyecto Final para el ciclo superior de Administración de Sistemas Informáticos en Red (ASIR). Este proyecto nace con la idea de automatizar el control de acceso de vehículos a un recinto cerrado (parking), mejorando la seguridad, rapidez e integridad informática frente a los sistemas tradicionales de tickets o llaveros mecánicos.

## Sobre el Proyecto

El objetivo principal de este proyecto es integrar conocimientos de administración de sistemas, programación y hardware libre para crear una solución integral. El sistema es capaz de:
1. **Identificar Vehículos:** Usando reconocimiento óptico de caracteres (OCR) a través de cámaras estándar.
2. **Validar Permisos:** Comprobar si el vehículo tiene autorización para entrar, basándose en registros centralizados.
3. **Accionar Hardware:** Controlar mecánicamente una barrera de acceso e interactuar con el usuario a través de una pantalla.

## Estructura del Repositorio

El repositorio se divide para separar la funcionalidad técnica de la documentación general:

- 📂 **[`Aplicacion-Parking/`](./Aplicacion-Parking/)**: Esta carpeta contiene el **núcleo de la aplicación**. Aquí encontrarás todos los scripts en Python (OpenCV, Tesseract), los ficheros para la placa Arduino y utilidades en consola.
- 📂 **[`Aplicacion-Web/`](./Aplicacion-Web/)**: Contiene la **interfaz de administración y la base de datos**. Almacena el servidor web (PHP, HTML, CSS) y el esquema relacional MySQL usado para gestionar eficientemente qué vehículos están autorizados.

*(Nota: Tienes un `README.md` técnico específico dentro de cada una de estas carpetas detallando su instalación, cableado y requisitos.)*

## Tecnologías Principales y Disciplinas Aplicadas

En el desarrollo y conceptualización de este proyecto de ASIR se han tocado las siguientes ramas:
* **Sistemas Operativos:** Uso de automatismos en Linux para la descarga y ejecución de dependencias (Arduino CLI).
* **Fundamentos de Programación:** Desarrollo de la lógica central en Python y programación de hardware mediante código C++.
* **Hardware y Redes:** Intercomunicación del puerto Serial, control de voltajes y señales I2C para el display lcd y modulación de ancho de pulsos en el servomotor.
* **Bases de Datos:** Almacenamiento y persistencia en un SGBD relacional (MySQL/MariaDB).
* **Implantación de Aplicaciones Web:** Programación de interfaces y backend con PHP, HTML5 y CSS3.

---
*Autor: Estudiante de ASIR.*
