# Dockerfile Certbot (Let's Encrypt + Cloudflare DNS)

Este directorio contiene el `Dockerfile` y el script de arranque (`entrypoint.sh`) utilizados para construir la imagen del gestor de certificados SSL de la aplicación.

## ¿Qué hace este contenedor?
1. **Imagen Base:** Utiliza la imagen oficial `certbot/dns-cloudflare:latest`, que incluye Certbot y el plugin necesario para realizar la validación de propiedad del dominio mediante la API de Cloudflare.
2. **Configuración Dinámica:** Recibe las variables de entorno (`DOMAIN_NAME`, `CERTBOT_EMAIL`, `CLOUDFLARE_API_TOKEN`) desde tu archivo `.env`.
3. **Escritura Segura de Credenciales:** El script de entrada (`entrypoint.sh`) crea dinámicamente un archivo `.ini` temporal en `/secrets/cloudflare.ini` conteniendo tu token de la API de Cloudflare, y le asigna los permisos estrictos (`chmod 600`) obligatorios para que Certbot opere de forma segura.
4. **Renovación Automatizada:** Ejecuta Certbot con el parámetro `--keep-until-expiring` para verificar y renovar los certificados solo cuando estén próximos a vencer.
5. **Ejecución Continua (Demonio):** Se mantiene corriendo en segundo plano a través de un bucle infinito que comprueba el estado de tus certificados cada 12 horas, garantizando que nunca expiren.
6. **Apagado Limpio (Graceful Shutdown):** Maneja correctamente las señales `SIGTERM` enviadas por Docker al detener los contenedores (mediante un trap en Bash y `wait`), apagándose instantáneamente.

*Nota: Los certificados generados se comparten con el contenedor de Apache (`web`) a través del volumen persistente `certbot_conf`, garantizando una sincronización segura y automática del tráfico HTTPS.*
