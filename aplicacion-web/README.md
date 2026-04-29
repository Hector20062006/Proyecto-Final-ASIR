# Proyecto Web de Administración - ASIR (Módulo PHP)

## Descripción y Objetivo
Este módulo es la capa de presentación (*frontend/backend*) de la aplicación ASIR. Se encarga de la interfaz de usuario, la gestión de sesiones y el enrutamiento del flujo de trabajo en el lado del cliente.

## Estructura
Los archivos están organizados por módulos funcionales (ej. `admin/`, `alumno/`, `profesor/`).

## 🛠️ Requisitos de Integración (Microservicios)
*   **Servicio Principal:** El servicio `web` debe ser el *consumidor* de los servicios de lógica de negocio.
*   **Comunicación:** No debe conectarse directamente a MySQL para lógica compleja. Debe realizar llamadas HTTP a `http://api-python:8000/api/v1/...` para procesar datos.
*   **Autenticación:** La conexión inicial con `aplicacion-web/conexion.php` sigue siendo válida para la autenticación simple, pero la lógica de negocio compleja debe pasar por la API Python.

## 🐳 Despliegue y Ejecución (Docker)
Este módulo web está configurado para ejecutarse en un entorno Dockerizado:
1. Desde la raíz del proyecto, ejecuta el comando: `docker-compose up -d`.
2. Accede a la interfaz web abriendo [http://localhost](http://localhost) en tu navegador web.
El código de la aplicación web está mapeado al contenedor (`/var/www/html`), por lo que cualquier cambio en el código PHP se verá reflejado inmediatamente en el navegador sin necesidad de reconstruir la imagen.

## 🚨 Tareas de Refactorización Necesarias
1.  **Ajuste de Conexión:** Modificar `aplicacion-web/conexion.php` para que la conexión a la DB sea solo para lectura de datos de sesión, y que cualquier escritura o proceso de negocio se delegue a la API Python.
2.  **Routing:** Implementar lógica de *front controller* en `index.php` que determine qué controlador se debe cargar basándose en la solicitud HTTP.
