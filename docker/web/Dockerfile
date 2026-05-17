FROM php:8.2-apache

# Instalar dependencias del sistema y la extensión mysqli para PHP
RUN apt-get update && apt-get install -y \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && docker-php-ext-enable mysqli

# Habilitar mod_rewrite de Apache si se necesita
RUN a2enmod rewrite ssl
RUN a2ensite default-ssl

# Instalar openssl para el entrypoint
RUN apt-get install -y openssl

COPY docker/web/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/web/ssl.conf /etc/apache2/sites-available/default-ssl.conf
COPY docker/web/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# El código se montará usando volúmenes desde docker-compose
# pero copiamos por defecto por si se quiere construir la imagen final
COPY aplicacion-web/ /var/www/html/

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
