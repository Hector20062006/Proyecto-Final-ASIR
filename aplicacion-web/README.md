# Aplicación Web - Parking Iliberis (Proyecto ASIR)

## 📌 Descripción General
Este módulo es la **capa de presentación y gestión (Frontend/Backend)** del sistema de control de acceso al Parking Iliberis. Proporciona una interfaz web intuitiva para que los usuarios (administradores, profesores y alumnos) puedan interactuar con el sistema, así como un punto de enlace (API) para que el módulo de visión artificial (cámaras) registre las entradas y salidas de vehículos.

Está desarrollado en **PHP puro** y se ejecuta sobre un servidor web Apache.

---

## ⚙️ Funcionalidad y Módulos Principales

El flujo de trabajo y la estructura del proyecto se dividen en los siguientes componentes clave:

### 1. Sistema de Autenticación y Sesiones (`login.php` / `logout.php`)
*   **Inicio de Sesión:** Permite a los usuarios acceder al sistema utilizando su DNI o correo electrónico junto con su contraseña cifrada.
*   **Control de Acceso basado en Roles (RBAC):** Una vez validadas las credenciales contra la base de datos (tabla `usuarios`), el sistema comprueba el rol del usuario (tabla `roles`) y lo redirige automáticamente a su panel de control específico (admin, profesor o alumno).
*   **Seguridad:** Las contraseñas se comprueban usando la función `password_verify()` de PHP, garantizando que el sistema es seguro y que no se manejan contraseñas en texto plano. La gestión de estado se realiza con variables de sesión `$_SESSION`.

### 2. Paneles por Rol (Directorios Aislados)
La aplicación cuenta con carpetas independientes para segmentar la lógica y el acceso según el rol:
*   📁 **`admin/`**: Panel de control total. Permite la administración integral del sistema, gestión de usuarios, auditoría completa de los registros de entrada y salida, etc.
*   📁 **`profesor/`**: Panel para personal docente. Permite gestionar vehículos, ver historial de accesos y consultar la **disponibilidad de plazas** (ahora conectadas a sensores físicos reales).
*   📁 **`alumno/`**: Panel básico para el alumnado. Similar al del profesor, restringido a visualizar sus datos, estado de acceso y **aforo del parking**.

### 3. Sistema de Reporte de Mal Aparcado (`profesor/reportar_mal_aparcado.php`)
*   **Finalidad:** Permite a los profesores notificar vehículos que estén obstaculizando el parking de forma manual.
*   **Integración con Telegram:** El sistema identifica al dueño del vehículo por su matrícula y envía un aviso instantáneo al canal común de profesores a través de un Bot de Telegram. El mensaje incluye el propietario y un motivo opcional (ej: "bloqueando mi salida").
*   **Gestión Administrativa:** El administrador cuenta con una vista específica (`admin/ver_incidencias.php`) para auditar todos estos reportes, pudiendo filtrar por matrícula, fecha o propietario.
*   **Monitoreo de Intrusos (`admin/ver_intentos.php`):** Nueva sección dedicada a visualizar matrículas detectadas que no están en la base de datos, permitiendo identificar lecturas erróneas o intentos de acceso no permitidos.

### 4. API de Recepción de Hardware (`api_camara.php`)
Es un archivo fundamental que actúa como puente de integración entre el **Contenedor de Visión Artificial (Python)** y la base de datos.
*   **Funcionamiento:** Escucha peticiones HTTP `POST` enviadas por el programa de Python cada vez que se detecta una matrícula de forma física en el parking.
*   **Registro de Datos:** Recibe los parámetros `matricula` y `tipo_movimiento` (ENTRADA o SALIDA) y los inserta inmediatamente en la tabla `accesos` junto con la marca de tiempo exacta del servidor.
*   **Respuesta:** Devuelve un simple `OK` al proceso de Python para confirmar que el registro se guardó correctamente en MySQL, o un mensaje de error si hubo algún problema de conexión.

### 5. Estructura y Vistas (`header.php` / `footer.php`)
Para evitar repetir código y mantener un diseño uniforme, la interfaz gráfica está modularizada. Los archivos de cabecera (donde se cargan CSS, logos y menús) y los pies de página se incluyen dinámicamente en todas las vistas mediante sentencias `require` o `include`.

---

## 🐳 Despliegue y Entorno Seguro (Docker & HTTPS)
El proyecto está completamente orquestado para desplegarse de manera automática e idéntica en cualquier máquina, incluyendo entornos seguros en la Raspberry Pi.

1. **Ejecución y Orquestación:** Desde la raíz del repositorio, se arranca todo el conjunto con:
   ```bash
   docker compose up -d
   ```
2. **Entorno HTTPS Cero-Configuración:** El servidor Apache está securizado con SSL/TLS por defecto (Puerto 443). Gracias al orquestador, el contenedor web **espera automáticamente en segundo plano** a que el contenedor de Certbot descargue y valide los certificados TLS de Let's Encrypt mediante la API de Cloudflare antes de iniciar el servidor Apache. No requiere manipulación manual de claves.
3. **Redirección Raíz Inteligente:** Se ha añadido un archivo `index.php` en la raíz que intercepta cualquier petición a la raíz del dominio (`https://tu-dominio.com/`) y la redirige con un HTTP 302 instantáneo hacia `login.php`, evitando errores 403 de directorios vacíos y ofreciendo una experiencia más profesional.
4. **Volúmenes en Tiempo Real:** El directorio de la aplicación (`aplicacion-web/`) está mapeado directamente al directorio público de Apache (`/var/www/html`). Esto permite realizar modificaciones en caliente sobre el código PHP/CSS y ver los resultados en el navegador al instante sin tener que reiniciar ni reconstruir los contenedores.

---

## 🚀 Arquitectura y Evolución Futura (Microservicios)
Aunque actualmente este módulo realiza las conexiones a la base de datos de manera directa (vía `conexion.php`), la arquitectura del proyecto está orientada a evolucionar:

*   **Delegación de Lógica (Refactorización):** El objetivo es que la aplicación web no realice lógica de negocio compleja ni consultas de escritura directas (salvo la API de cámara actualmente). Todo se delegará a un servidor central en Python al cual la web consultará mediante llamadas HTTP.
*   **Front Controller:** En el futuro se buscará centralizar todas las peticiones a través de un único `index.php` (Routing) para tener un control más estricto sobre las peticiones y mejorar la seguridad global de la aplicación.

---
*Autores: Imad y Hector*
