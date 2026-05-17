#!/bin/bash
set -e

DOMAIN="${DOMAIN_NAME:-parking-iliberis.run.place}"
CERT_DIR="/etc/letsencrypt/live/$DOMAIN"

# Reemplazamos el nombre de dominio en los VirtualHosts usando el entorno
sed -i "s/__DOMAIN_NAME__/${DOMAIN}/g" /etc/apache2/sites-available/000-default.conf
sed -i "s/__DOMAIN_NAME__/${DOMAIN}/g" /etc/apache2/sites-available/default-ssl.conf

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
