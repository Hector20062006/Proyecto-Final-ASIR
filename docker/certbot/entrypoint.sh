#!/bin/sh
set -e

# 1. Crear la carpeta y el archivo INI con los permisos que exige Certbot
mkdir -p /secrets
echo "dns_cloudflare_api_token = ${CLOUDFLARE_API_TOKEN}" > /secrets/cloudflare.ini
chmod 600 /secrets/cloudflare.ini

# 2. Configurar la salida limpia del contenedor ante SIGTERM
trap exit TERM;

# 3. Bucle infinito para renovación automática cada 12 horas
while :; do
  certbot certonly --dns-cloudflare --dns-cloudflare-credentials /secrets/cloudflare.ini \
    -d ${DOMAIN_NAME} \
    --email ${CERTBOT_EMAIL} \
    --agree-tos \
    --no-eff-email \
    --keep-until-expiring
  
  sleep 12h & wait ${!}
done
