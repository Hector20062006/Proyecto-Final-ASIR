# Configuración de Base de Datos (MySQL)

> **Actualización:** Las tablas de acceso e intentos ahora reciben marcas de tiempo explicitamente basadas en la hora de Madrid, y la sesión MySQL se puede ajustar a esa zona horaria.

Este directorio contiene el script de inicialización SQL (`parking_asir.sql`) utilizado para estructurar y rellenar la base de datos MySQL al desplegar el entorno Docker.

## ¿Cómo funciona la inicialización?
El contenedor oficial de MySQL (`mysql:8.0`) en el archivo `docker-compose.yml` está configurado para montar este script en la ruta interna `/docker-entrypoint-initdb.d/init.sql`.

Al arrancar el contenedor por primera vez (cuando el volumen persistente `db_data` está vacío), el motor de base de datos ejecuta automáticamente este script SQL, inicializando la base de datos `parking_ASIR` con todas sus tablas, restricciones y datos semilla.

---

## 📊 Esquema Relacional de Datos

La base de datos se compone de las siguientes tablas estructuradas para gestionar accesos, usuarios, plazas de aparcamiento y reportes de incidencias:

### 1. `roles`
Define los tipos de usuarios y sus niveles de acceso dentro del sistema.
*   `id_rol` (INT, PK, AUTO_INCREMENT): Identificador del rol.
*   `nombre_rol` (VARCHAR): Nombre del rol (`administrador`, `profesores`, `alumnos`).

### 2. `usuarios`
Almacena el registro de usuarios que tienen relación con el centro.
*   `dni` (VARCHAR, PK): Documento Nacional de Identidad.
*   `nombre` (VARCHAR): Nombre de pila del usuario.
*   `apellidos` (VARCHAR): Apellidos del usuario.
*   `email` (VARCHAR, UNIQUE): Correo electrónico del usuario (clave para login).
*   `telefono` (VARCHAR): Teléfono de contacto.
*   `password` (VARCHAR): Contraseña cifrada de forma segura en PHP con `password_hash()`.
*   `id_rol` (INT, FK): Relacionado con `roles(id_rol)` para asignación de permisos.

### 3. `vehiculos`
Contiene los coches registrados y autorizados para ingresar al parking.
*   `matricula` (VARCHAR, PK): Identificador único del coche (clave para el OCR).
*   `marca_modelo` (VARCHAR): Información descriptiva del vehículo.
*   `dni_usuario` (VARCHAR, FK): Relacionado con `usuarios(dni)` (propietario del coche).

### 4. `accesos`
Registra el historial completo de flujos de vehículos (entradas y salidas).
*   `id_acceso` (INT, PK, AUTO_INCREMENT): Identificador de registro.
*   `matricula` (VARCHAR, FK): Relacionado con `vehiculos(matricula)`.
*   `fecha_hora` (DATETIME): Marca de tiempo del movimiento.
*   `tipo_movimiento` (ENUM): Define la dirección del flujo (`ENTRADA` o `SALIDA`).

### 5. `plazas`
Mantiene el estado físico en tiempo real de cada plaza del parking.
*   `id_plaza` (INT, PK): Número identificador de la plaza física.
*   `estado` (ENUM): Estado actual de ocupación (`libre` u `ocupada`).
*   `ultima_actualizacion` (DATETIME): Marca de tiempo del último cambio detectado por los sensores.

### 6. `reportes_mal_aparcado`
Almacena las alertas enviadas por los profesores sobre coches mal estacionados.
*   `id_reporte` (INT, PK, AUTO_INCREMENT): Identificador de reporte.
*   `matricula` (VARCHAR, FK): Relacionado con `vehiculos(matricula)`.
*   `dni_reportador` (VARCHAR, FK): Relacionado con `usuarios(dni)` (profesor que reporta).
*   `motivo` (TEXT): Descripción del incidente.
*   `fecha_hora` (DATETIME): Fecha y hora del reporte.

### 7. `intentos_denegados`
Audita los intentos de acceso de vehículos no autorizados (matrículas no registradas).
*   `id_intento` (INT, PK, AUTO_INCREMENT): Identificador único del intento.
*   `matricula` (VARCHAR): Matrícula capturada por el OCR.
*   `fecha_hora` (DATETIME): Fecha y hora de captura.
*   `camara` (VARCHAR): Identificador de la cámara que capturó el intento.

---

## 🔒 Integridad de Datos (Restricciones y Cascadas)
El esquema cuenta con claves foráneas configuradas con `ON DELETE CASCADE` y `ON UPDATE CASCADE` para garantizar que:
*   Si se elimina un usuario, se borran automáticamente sus vehículos y reportes asociados.
*   Si se da de baja un vehículo, se eliminan en cascada sus registros de acceso e incidencias.
*   Los cambios de matrículas o DNI se propagan automáticamente por todas las tablas relacionadas para evitar inconsistencias de datos.
