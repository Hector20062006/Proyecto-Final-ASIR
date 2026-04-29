FROM php:8.2-apache

# Instalar dependencias del sistema y la extensión mysqli para PHP
RUN apt-get update && apt-get install -y \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && docker-php-ext-enable mysqli

# Habilitar mod_rewrite de Apache si se necesita
RUN a2enmod rewrite

# El código se montará usando volúmenes desde docker-compose
# pero copiamos por defecto por si se quiere construir la imagen final
COPY . /var/www/html/
