#!/bin/bash
set -e

DOMAIN="${DOMAIN_NAME:-parking-iliberis.run.place}"
CERT_DIR="/etc/letsencrypt/live/$DOMAIN"

# Reemplazamos el nombre de dominio en los VirtualHosts usando el entorno
sed -i "s/__DOMAIN_NAME__/${DOMAIN}/g" /etc/apache2/sites-available/000-default.conf
sed -i "s/__DOMAIN_NAME__/${DOMAIN}/g" /etc/apache2/sites-available/default-ssl.conf

# Si no existe el certificado de Let's Encrypt, generamos uno temporal autofirmado
if [ ! -f "$CERT_DIR/fullchain.pem" ]; then
    echo "Certificados de Let's Encrypt no encontrados para $DOMAIN."
    echo "Generando certificados autofirmados temporales para iniciar Apache..."
    mkdir -p "$CERT_DIR"
    openssl req -x509 -nodes -newkey rsa:2048 -days 1 \
        -keyout "$CERT_DIR/privkey.pem" \
        -out "$CERT_DIR/fullchain.pem" \
        -subj "/CN=localhost"
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
