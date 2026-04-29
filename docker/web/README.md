# Dockerfile Web (PHP + Apache)

Este directorio contiene el `Dockerfile` utilizado para construir la imagen del servidor web de la aplicación.

## ¿Qué hace este contenedor?
1. **Imagen Base:** Utiliza `php:8.2-apache`, que es un servidor web Apache preconfigurado con PHP 8.2.
2. **Extensiones:** Habilita e instala automáticamente `mysqli` y `pdo_mysql` a través de herramientas propias de la imagen Docker de PHP. Estas extensiones son necesarias para que el código PHP se comunique con la base de datos MySQL.
3. **Módulos Apache:** Habilita `mod_rewrite` para permitir reescritura de URLs en caso de que el enrutamiento lo necesite.
4. **Código Fuente:** El contexto de compilación (desde `docker-compose.yml`) se establece en `aplicacion-web`, por lo que el comando `COPY . /var/www/html/` copia el código fuente al directorio público de Apache.

*Nota: Durante el desarrollo en local, el código fuente también se monta como un volumen en el archivo `docker-compose.yml`, permitiendo editar el código en tiempo real.*
