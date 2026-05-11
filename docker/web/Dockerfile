FROM php:8.2-apache

# Instalar dependencias del sistema y extensiones PHP necesarias
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql curl \
    && docker-php-ext-enable mysqli curl

# Habilitar mod_rewrite de Apache si se necesita
RUN a2enmod rewrite

# El código se montará usando volúmenes desde docker-compose
# pero copiamos por defecto por si se quiere construir la imagen final
COPY . /var/www/html/
