# 🛠️ Documentación Técnica: Docker y Orquestación

El despliegue sincroniza los servicios de Python y web para usar la hora de Madrid en los registros y las marcas de tiempo de acceso.

Este directorio centraliza la infraestructura de contenedores y la configuración de despliegue modular del **Sistema de Automatización de Parking**. A través de **Docker** y **Docker Compose**, el proyecto se divide en 4 microservicios especializados que colaboran para ofrecer un entorno seguro, aislado y altamente disponible.

---

## 🛠️ Servicios Orquestados

La infraestructura se compone de los siguientes contenedores, cada uno con su correspondiente entorno aislado y automatización propia:

1. 🌐 **[`web/`](./web/) (PHP & Apache)**: Contiene el portal web de administración, los módulos de usuario y profesor, y la API receptora para el procesamiento de matrículas.
2. 🧠 **[`python/`](./python/) (Detección OCR & Hardware)**: Ejecuta el software concurrente de visión artificial con OpenCV/Tesseract y gestiona las conexiones en serie físicas con el Arduino.
3. 🗳️ **[`db/`](./db/) (Base de Datos MySQL)**: Aloja el motor de persistencia relacional MySQL 8.0 e inicializa automáticamente el esquema de tablas mediante scripts SQL integrados.
4. 🔐 **[`certbot/`](./certbot/) (Seguridad SSL Let's Encrypt)**: Realiza de forma automática y desatendida la validación DNS-01 de Cloudflare y la renovación periódica de los certificados HTTPS.

---

## 🚀 Despliegue Rápido del Entorno

Para levantar todos los servicios concurrentemente en tu servidor local o Raspberry Pi, simplemente ejecuta desde la raíz del proyecto:

```bash
docker compose up -d --build
```

Esto arrancará y coordinará la compilación de las imágenes, montando los puertos seguros y enlazando los volúmenes de desarrollo al vuelo.
