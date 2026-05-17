#!/bin/bash
set -e

DOMAIN="${DOMAIN_NAME:-parking-iliberis.run.place}"
# Si el dominio es un wildcard (*.dominio.com), Certbot guarda los certificados en la carpeta base (dominio.com)
CERT_DOMAIN="${DOMAIN#\*.}"
CERT_DIR="/etc/letsencrypt/live/$CERT_DOMAIN"

# Primero, configuramos la ruta de los certificados en la configuración SSL usando CERT_DOMAIN (sin asteriscos)
sed -i "s/live\/__DOMAIN_NAME__\//live\/${CERT_DOMAIN}\//g" /etc/apache2/sites-available/default-ssl.conf

# Segundo, reemplazamos ServerName con el CERT_DOMAIN limpio y añadimos ServerAlias con el DOMAIN original (que puede ser wildcard)
sed -i "s/ServerName __DOMAIN_NAME__/ServerName ${CERT_DOMAIN}\n    ServerAlias ${DOMAIN}/g" /etc/apache2/sites-available/000-default.conf
sed -i "s/ServerName __DOMAIN_NAME__/ServerName ${CERT_DOMAIN}\n    ServerAlias ${DOMAIN}/g" /etc/apache2/sites-available/default-ssl.conf

# Esperamos a que Certbot genere los certificados DNS-01
if [ ! -f "$CERT_DIR/fullchain.pem" ]; then
    echo "================================================="
    echo "Esperando a que Certbot genere los certificados SSL..."
    echo "================================================="
    while [ ! -f "$CERT_DIR/fullchain.pem" ]; do
        sleep 5
    done
    echo "¡Certificados encontrados!"
fi

echo "Iniciando Apache en background..."
apache2-foreground &
APACHE_PID=$!

echo "Monitorizando cambios en los certificados SSL..."
# Calculamos el hash inicial
CERT_HASH=$(md5sum $CERT_DIR/fullchain.pem 2>/dev/null | awk '{print $1}')

while true; do
    sleep 60
    NEW_CERT_HASH=$(md5sum $CERT_DIR/fullchain.pem 2>/dev/null | awk '{print $1}')
    
    # Si el hash cambia, recargamos Apache
    if [ "$CERT_HASH" != "$NEW_CERT_HASH" ]; then
        echo "================================================="
        echo "Nuevos certificados detectados. Recargando Apache..."
        echo "================================================="
        apache2ctl graceful
        CERT_HASH=$NEW_CERT_HASH
    fi
done

wait $APACHE_PID
