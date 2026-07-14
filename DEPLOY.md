# Guía de Despliegue — Portal Distribuidores

**Plataforma:** Hostinger VPS · Ubuntu 24.04 LTS  
**Stack:** Nginx · PHP 8.3-FPM · PostgreSQL 16 · Node.js 20 · Laravel 12

> Para la separacion completa entre `staging` y `production`, revisa tambien [`STAGING.md`](./STAGING.md).

---

## Tabla de contenidos

1. [Requisitos del servidor](#1-requisitos-del-servidor)
2. [Preparación del servidor](#2-preparación-del-servidor)
3. [Configurar PostgreSQL](#3-configurar-postgresql)
4. [Clonar el repositorio](#4-clonar-el-repositorio)
5. [Configurar el entorno (.env)](#5-configurar-el-entorno-env)
6. [Instalar dependencias y build inicial](#6-instalar-dependencias-y-build-inicial)
7. [Permisos de directorios](#7-permisos-de-directorios)
8. [Configurar Nginx](#8-configurar-nginx)
9. [SSL con Let's Encrypt](#9-ssl-con-lets-encrypt)
10. [Configurar el worker de colas](#10-configurar-el-worker-de-colas)
11. [Validaciones finales](#11-validaciones-finales)
12. [Actualizaciones futuras con deploy.sh](#12-actualizaciones-futuras-con-deploysh)
13. [Cloudflare R2 — discos público y privado](#13-cloudflare-r2--discos-público-y-privado)
14. [Correo SMTP (Hostinger)](#14-correo-smtp-hostinger)
15. [Notas de seguridad y optimización](#15-notas-de-seguridad-y-optimización)

---

## 1. Requisitos del servidor

| Componente      | Versión mínima | Notas                              |
|-----------------|----------------|------------------------------------|
| PHP             | 8.3            | Con extensiones requeridas         |
| PostgreSQL      | 15 / 16        | Local en el VPS                    |
| Nginx           | 1.24+          | Como servidor web                  |
| Node.js         | 20 LTS         | Para compilar assets con Vite      |
| Composer        | 2.7+           | Gestor de dependencias PHP         |
| Git             | cualquiera     | Para clonar y actualizar el repo   |

**Extensiones PHP requeridas:**  
`pgsql`, `pdo_pgsql`, `mbstring`, `xml`, `curl`, `zip`, `gd` (o `imagick`), `intl`, `bcmath`, `fileinfo`, `tokenizer`, `openssl`, `dom`

> **DomPDF (generación de PDFs)** requiere `gd` o `imagick` y `dom`/`xml`.

---

## 2. Preparación del servidor

Conéctate como `root` o usuario con `sudo`:

```bash
# Actualizar sistema
apt update && apt upgrade -y

# Instalar Nginx
apt install -y nginx

# Instalar PHP 8.3 y extensiones
apt install -y software-properties-common
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y \
  php8.3 php8.3-fpm php8.3-cli \
  php8.3-pgsql php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath \
  php8.3-fileinfo php8.3-tokenizer php8.3-dom \
  php8.3-opcache php8.3-readline

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Instalar Node.js 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# Instalar PostgreSQL 16
apt install -y postgresql postgresql-contrib

# Instalar Certbot (SSL)
apt install -y certbot python3-certbot-nginx

# Iniciar servicios
systemctl enable --now nginx
systemctl enable --now php8.3-fpm
systemctl enable --now postgresql
```

### Configurar PHP-FPM para producción

```bash
# Ajustar parámetros en /etc/php/8.3/fpm/php.ini
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 6M/' /etc/php/8.3/fpm/php.ini
sed -i 's/post_max_size = .*/post_max_size = 45M/' /etc/php/8.3/fpm/php.ini
sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.3/fpm/php.ini
sed -i 's/memory_limit = .*/memory_limit = 256M/' /etc/php/8.3/fpm/php.ini
sed -i 's/;opcache.enable=.*/opcache.enable=1/' /etc/php/8.3/fpm/php.ini
sed -i 's/;opcache.memory_consumption=.*/opcache.memory_consumption=128/' /etc/php/8.3/fpm/php.ini

systemctl restart php8.3-fpm
```

> La aplicacion valida hasta **3 MB por foto**, **5 MB por PDF** y **40 MB** de carga total.
> Deja `upload_max_filesize`, `post_max_size` y `client_max_body_size` por encima de esos limites
> para evitar `500` o `413` inconsistentes.

---

## 3. Configurar PostgreSQL

```bash
# Acceder a la consola de PostgreSQL
sudo -u postgres psql

-- Dentro de psql:
CREATE USER portal_user WITH PASSWORD 'CAMBIA_ESTA_CONTRASENA_SEGURA';
CREATE DATABASE portal_distribuidores OWNER portal_user;
GRANT ALL PRIVILEGES ON DATABASE portal_distribuidores TO portal_user;
\q
```

> Guarda el usuario y contraseña; los necesitarás en el `.env`.

---

## 4. Clonar el repositorio

```bash
# Crear directorio del proyecto
mkdir -p /var/www
cd /var/www

# Clonar el repositorio (ajusta la URL a tu repo)
git clone https://github.com/TU_ORG/portal-distribuidores.git portal-distribuidores
cd portal-distribuidores

# Asignar propietario al usuario de Nginx
chown -R www-data:www-data /var/www/portal-distribuidores
```

---

## 5. Configurar el entorno (.env)

```bash
cd /var/www/portalDistribuidores

# Copiar la plantilla
cp .env.example .env
```

Edita `/var/www/portal-distribuidores/.env` con tus valores reales:

```dotenv
# ── APLICACIÓN ────────────────────────────────────────────────────────────────
APP_NAME="Portal de Distribuidores"
APP_ENV=production
APP_KEY=                            # Se genera en el paso siguiente
APP_DEBUG=false
APP_URL=https://tu-dominio.com      # Sin barra final

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_CO

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

# ── LOGS ──────────────────────────────────────────────────────────────────────
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning                   # En producción: warning o error

# ── BASE DE DATOS (PostgreSQL local) ──────────────────────────────────────────
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=portal_distribuidores
DB_USERNAME=portal_user
DB_PASSWORD=CAMBIA_ESTA_CONTRASENA_SEGURA

# ── SESIONES, COLAS Y CACHÉ ───────────────────────────────────────────────────
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
CACHE_STORE=database
AUTH_ALLOW_PUBLIC_REGISTRATION=false

# ── ALMACENAMIENTO ────────────────────────────────────────────────────────────
FILESYSTEM_DISK=local
PUBLIC_DISK_DRIVER=s3               # Fotos y assets realmente públicos
PRIVATE_DISK_DRIVER=s3              # PDFs de pedidos y fichas técnicas
PUBLIC_MEDIA_SIGNED_URL_TTL=20
ORDER_PDFS_DISK=private
TECH_SHEETS_DISK=private

# ── CLOUDFLARE R2: DISCO PÚBLICO ──────────────────────────────────────────────
AWS_ACCESS_KEY_ID=TU_R2_ACCESS_KEY_ID
AWS_SECRET_ACCESS_KEY=TU_R2_SECRET_ACCESS_KEY
AWS_DEFAULT_REGION=auto
AWS_BUCKET=portal-distribuidores-public
AWS_URL=https://pub-XXXX.r2.dev             # URL pública del bucket R2
AWS_ENDPOINT=https://ACCOUNT_ID.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true

# ── CLOUDFLARE R2: DISCO PRIVADO ──────────────────────────────────────────────
PRIVATE_ACCESS_KEY_ID=TU_R2_PRIVATE_ACCESS_KEY_ID
PRIVATE_SECRET_ACCESS_KEY=TU_R2_PRIVATE_SECRET_ACCESS_KEY
PRIVATE_DEFAULT_REGION=auto
PRIVATE_BUCKET=portal-distribuidores-private
PRIVATE_ENDPOINT=https://ACCOUNT_ID.r2.cloudflarestorage.com
PRIVATE_USE_PATH_STYLE_ENDPOINT=true

# ── BD SEGURA ─────────────────────────────────────────────────────────────────
DB_SSLMODE=require

# ── CORREO SMTP (Hostinger) ───────────────────────────────────────────────────
MAIL_MAILER=smtp
MAIL_SCHEME=tls                     # tls para puerto 587; smtps para puerto 465
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=notificaciones@tu-dominio.com
MAIL_PASSWORD=TU_PASSWORD_SMTP
MAIL_TIMEOUT=15
MAIL_FROM_ADDRESS="notificaciones@tu-dominio.com"
MAIL_FROM_NAME="${APP_NAME}"

ORDER_NOTIFICATION_EMAIL_TO=ventas@tu-dominio.com
ORDER_NOTIFICATION_EMAIL_DISPATCH=queue  # 'queue' para envío asíncrono (recomendado en prod)

# ── MOTOR DE BÚSQUEDA ─────────────────────────────────────────────────────────
SEARCH_ENGINE=postgres

# ── VITE ──────────────────────────────────────────────────────────────────────
VITE_APP_NAME="${APP_NAME}"
```

> **Nota sobre `ORDER_NOTIFICATION_EMAIL_DISPATCH`:**  
> En local está en `sync`. En producción con queue worker activo, cámbialo a `queue`  
> para que el envío de correos no bloquee la respuesta HTTP del pedido.

Genera la clave de aplicación:

```bash
sudo -u www-data php artisan key:generate
```

---

## 6. Instalar dependencias y build inicial

```bash
cd /var/www/portalDistribuidores

# Dependencias PHP (sin paquetes de desarrollo)
sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction

# Migraciones (crea todas las tablas, incluyendo jobs y sessions)
sudo -u www-data php artisan migrate --force

# Migrar PDFs de pedidos y fichas técnicas legacy desde public hacia private
sudo -u www-data php artisan protected-media:migrate --no-interaction

# Enlace simbólico de storage (solo para disco local; con R2 no es estrictamente necesario)
sudo -u www-data php artisan storage:link

# Cachear configuración, rutas y vistas
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan event:cache

# Dependencias Node.js y compilación de assets
npm ci --omit=dev
npm run build
```

> `npm ci` es más rápido y determinista que `npm install` en producción porque usa `package-lock.json`.

---

## 7. Permisos de directorios

```bash
cd /var/www/portalDistribuidores

# Propietario correcto para todos los archivos
chown -R www-data:www-data .

# Permisos de escritura solo donde Laravel los necesita
chmod -R 755 .
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Verificar que PHP-FPM puede escribir
ls -la storage/
ls -la bootstrap/cache/
```

---

## 8. Configurar Nginx

Copia el archivo de configuración incluido en el repositorio:

```bash
cp /var/www/portal-distribuidores/deploy/nginx.conf \
   /etc/nginx/sites-available/portal-distribuidores

# Edita el archivo y reemplaza 'tu-dominio.com' con tu dominio real
nano /etc/nginx/sites-available/portal-distribuidores

# Activar el sitio
ln -s /etc/nginx/sites-available/portal-distribuidores \
      /etc/nginx/sites-enabled/portal-distribuidores

# Desactivar el sitio default si está activo
rm -f /etc/nginx/sites-enabled/default

# Probar configuración
nginx -t

# Recargar Nginx
systemctl reload nginx
```

El archivo `deploy/nginx.conf` ya incluye:

- `client_max_body_size 45M` para dejar margen sobre el limite funcional de 40 MB
- `error_page 413 /413.html` para mostrar una pagina amigable cuando Nginx rechace la carga antes de Laravel

---

## 9. SSL con Let's Encrypt

> Asegúrate de que tu dominio ya apunta al IP del VPS antes de ejecutar esto.

```bash
# Obtener certificado (reemplaza con tu dominio real)
certbot --nginx -d tu-dominio.com -d www.tu-dominio.com \
  --non-interactive --agree-tos --email admin@tu-dominio.com

# Verificar renovación automática
systemctl status certbot.timer
certbot renew --dry-run
```

---

## 10. Configurar el worker de colas

### Opción A: systemd (recomendada para Ubuntu 24.04)

```bash
# Crear directorio de logs
mkdir -p /var/log/laravel
chown www-data:www-data /var/log/laravel

# Copiar el unit file incluido en el repositorio
cp /var/www/portal-distribuidores/deploy/laravel-queue.service \
   /etc/systemd/system/laravel-queue.service

# Recargar systemd y activar el servicio
systemctl daemon-reload
systemctl enable --now laravel-queue

# Verificar que está corriendo
systemctl status laravel-queue
journalctl -u laravel-queue -f  # Ver logs en tiempo real
```

### Opción B: Supervisor (alternativa)

```bash
apt install -y supervisor

# Crear directorio de logs
mkdir -p /var/log/laravel
chown www-data:www-data /var/log/laravel

# Copiar la configuración incluida en el repositorio
cp /var/www/portal-distribuidores/deploy/laravel-queue-supervisor.conf \
   /etc/supervisor/conf.d/laravel-queue.conf

supervisorctl reread
supervisorctl update
supervisorctl start laravel-queue:*
supervisorctl status
```

---

## 11. Validaciones finales

```bash
# 1. Verificar que la app responde
curl -I https://tu-dominio.com

# 2. Verificar logs de Laravel
tail -f /var/www/portal-distribuidores/storage/logs/laravel.log

# 3. Verificar que las colas funcionan
sudo -u www-data php artisan queue:monitor

# 4. Verificar conexión a la base de datos
sudo -u www-data php artisan db:show

# 5. Verificar que el storage de R2 funciona (ejecutar en tinker)
sudo -u www-data php artisan tinker
>>> Storage::disk('public')->exists('test.txt') ? 'R2 OK' : 'Verificar credenciales R2';

# 5.1 Verificar storage privado y migración de media protegida
sudo -u www-data php artisan tinker
>>> Storage::disk('private')->exists('products/documents') ? 'Private OK' : 'Verificar PRIVATE_*';

# 6. Verificar que el correo SMTP funciona
sudo -u www-data php artisan tinker
>>> Mail::raw('Test desde producción', fn($m) => $m->to('tu@email.com')->subject('Test'));

# 7. Verificar worker de colas activo
systemctl status laravel-queue

# 8. Verificar permisos
ls -la /var/www/portal-distribuidores/storage/
ls -la /var/www/portal-distribuidores/bootstrap/cache/
```

### Checklist de validación

- [ ] La app carga en `https://tu-dominio.com` sin errores
- [ ] El login funciona correctamente
- [ ] Se puede subir una imagen/documento (va a R2)
- [ ] Las fotos públicas siguen cargando desde el disco/bucket público
- [ ] Los PDFs de pedidos y fichas técnicas ya no quedan expuestos en el bucket público
- [ ] Una foto > 3 MB muestra un mensaje claro en el formulario
- [ ] Un PDF > 5 MB muestra un mensaje claro en el formulario
- [ ] Una carga total excesiva muestra un mensaje claro o la pagina 413 amigable
- [ ] Se puede generar un PDF de pedido (DomPDF)
- [ ] Se envía correo de notificación (SMTP Hostinger)
- [ ] `APP_DEBUG=false` está confirmado en `.env`
- [ ] Los logs de errores van a `storage/logs/` (no en pantalla)
- [ ] El worker de colas está activo y procesando jobs
- [ ] SSL está activo y redirige HTTP → HTTPS

---

## 12. Actualizaciones futuras con deploy.sh

Para actualizaciones del código, usa el script incluido:

```bash
# Dar permiso de ejecución (solo la primera vez)
chmod +x /var/www/portal-distribuidores/deploy.sh

# Ejecutar despliegue
cd /var/www/portalDistribuidores
sudo bash deploy.sh
```

El script hace automáticamente: `git pull` → `composer install` → `migrate` →  
`config/route/view:cache` → `npm ci` → `npm run build` → reinicio del worker.

### Smoke check después del deploy

Los workflows de GitHub Actions para `staging` y `production` hacen un smoke check HTTP
al terminar el SSH deploy. La validación consulta la URL canónica del entorno y acepta
`200`, `301` o `302`.

Si el smoke check falla:

1. Revisar el log del job de deploy en GitHub Actions
2. Revisar logs del VPS:
   - `tail -f storage/logs/laravel.log`
   - `systemctl status laravel-queue-staging` o `systemctl status laravel-queue-prod`
   - `systemctl status php8.3-fpm`
3. Confirmar respuesta manual:
   - `curl -I https://staging-pedidos.importcorporalmedical.com`
   - `curl -I https://pedidos.importcorporalmedical.com`

### Rollback operativo recomendado

Rollback normal de aplicación:

1. Identificar el commit o merge problemático en GitHub
2. Hacer `git revert` del cambio en la rama objetivo
3. Hacer merge del revert
4. Dejar que el mismo pipeline vuelva a desplegar

Limitación importante:

- El `deploy.sh` siempre sincroniza contra `origin/<branch>`, así que hoy no existe un rollback seguro de "un clic" a un commit viejo directamente desde el VPS.
- Si una migración en producción deja el sistema inconsistente, la ruta segura es:
  - revertir el código en GitHub,
  - desplegar el revert,
  - y evaluar restaurar el backup de PostgreSQL creado por `deploy.sh` en `/var/backups/portal-distribuidores/production` solo como operación manual de incidente.

---

## 13. Cloudflare R2 — discos público y privado

Este proyecto usa dos discos separados en `config/filesystems.php`:

- `public`: fotos y assets realmente públicos del catálogo
- `private`: PDFs de pedidos y fichas técnicas, servidos solo por Laravel

En producción VPS con R2:

- `PUBLIC_DISK_DRIVER=s3` publica solo media no sensible
- `PRIVATE_DISK_DRIVER=s3` mantiene media protegida fuera de acceso directo
- `ORDER_PDFS_DISK=private` y `TECH_SHEETS_DISK=private` son obligatorios
- `storage:link` solo aplica si mantienes un disco local para desarrollo o fallback

### Crear los buckets en Cloudflare R2

1. Ir a [Cloudflare Dashboard](https://dash.cloudflare.com) → R2 → Create Bucket
2. Crear un bucket público, por ejemplo `portal-distribuidores-public`
3. En **Settings** del bucket público → **Public access**: habilitar "Allow Public Access"
4. Copiar la **Public bucket URL** (`https://pub-XXXX.r2.dev`) → `AWS_URL`
5. Crear un bucket privado, por ejemplo `portal-distribuidores-private`
6. En el bucket privado, **no** habilitar acceso público
7. Ir a **R2 Overview** → **Manage R2 API Tokens** → Create Token
8. Permisos mínimos: `Object Read & Write` solo sobre los buckets usados por la app
9. Copiar credenciales del bucket público → `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY`
10. Copiar credenciales del bucket privado → `PRIVATE_ACCESS_KEY_ID` / `PRIVATE_SECRET_ACCESS_KEY`
11. Copiar Account ID endpoint → `AWS_ENDPOINT` y `PRIVATE_ENDPOINT`

### Variables R2 en .env de producción

```dotenv
PUBLIC_DISK_DRIVER=s3
PRIVATE_DISK_DRIVER=s3
ORDER_PDFS_DISK=private
TECH_SHEETS_DISK=private

AWS_ACCESS_KEY_ID=<tu-access-key-id>
AWS_SECRET_ACCESS_KEY=<tu-secret-access-key>
AWS_DEFAULT_REGION=auto
AWS_BUCKET=portal-distribuidores-public
AWS_URL=https://pub-XXXX.r2.dev
AWS_ENDPOINT=https://ACCOUNT_ID.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true

PRIVATE_ACCESS_KEY_ID=<tu-private-access-key-id>
PRIVATE_SECRET_ACCESS_KEY=<tu-private-secret-access-key>
PRIVATE_DEFAULT_REGION=auto
PRIVATE_BUCKET=portal-distribuidores-private
PRIVATE_ENDPOINT=https://ACCOUNT_ID.r2.cloudflarestorage.com
PRIVATE_USE_PATH_STYLE_ENDPOINT=true
```

> No uses el bucket/disco `public` para PDFs de pedidos ni fichas técnicas. El deploy aborta si detecta esa configuración.

---

## 14. Correo SMTP (Hostinger)

El proyecto usa el mailer `smtp` nativo de Laravel. La configuración en `config/mail.php`  
ya soporta las variables estándar de Laravel. **No se necesitan cambios de código.**

### Configuración SMTP de Hostinger

| Variable          | Valor                             |
|-------------------|-----------------------------------|
| `MAIL_MAILER`     | `smtp`                            |
| `MAIL_SCHEME`     | `tls`                             |
| `MAIL_HOST`       | `smtp.hostinger.com`              |
| `MAIL_PORT`       | `587`                             |
| `MAIL_USERNAME`   | tu correo en Hostinger            |
| `MAIL_PASSWORD`   | contraseña del correo             |
| `MAIL_FROM_ADDRESS` | mismo correo que `MAIL_USERNAME` |

> Si usas puerto 465 (SSL), cambia `MAIL_SCHEME=smtps` y `MAIL_PORT=465`.

### Variables de notificación de pedidos

```dotenv
ORDER_NOTIFICATION_EMAIL_TO=ventas@tu-dominio.com   # Destinatario de alertas de pedidos
ORDER_NOTIFICATION_EMAIL_DISPATCH=queue              # Envío asíncrono via cola
```

El flujo `ORDER_NOTIFICATION_EMAIL_DISPATCH` controla si el correo de notificación  
de pedido se despacha `sync` (inmediato, bloquea la respuesta) o `queue` (asíncrono,  
requiere worker activo). En producción se recomienda `queue`.

---

## 15. Notas de seguridad y optimización

### Seguridad

- Confirmar `APP_DEBUG=false` en producción — evita exponer stack traces
- Confirmar `SESSION_SECURE_COOKIE=true` y `DB_SSLMODE=require`
- Confirmar `AUTH_ALLOW_PUBLIC_REGISTRATION=false` salvo que negocio pida auto-registro explícito
- `APP_KEY` nunca debe compartirse ni subirse a Git
- El archivo `.env` tiene permisos `640`: `chmod 640 .env && chown www-data:www-data .env`
- Configurar firewall: `ufw allow 22 && ufw allow 80 && ufw allow 443 && ufw enable`
- Deshabilitar acceso directo a PostgreSQL desde fuera: el puerto 5432 solo debe escuchar en `localhost`

### OPcache

PHP-FPM con OPcache habilitado es fundamental para el rendimiento. Verificar:

```bash
php -r "echo opcache_get_status()['opcache_enabled'] ? 'OPcache ON' : 'OPcache OFF';"
```

### Rotación de logs

```bash
# Laravel ya usa 'daily' para logs — verificar que la rotación está activa
ls /var/www/portal-distribuidores/storage/logs/
```

### Health check

Para verificar que la app está saludable desde el exterior:

```bash
curl -s -o /dev/null -w "%{http_code}" https://tu-dominio.com/
# Debe retornar 200 o 302
```

`/up` no está expuesto públicamente. Si se necesita un health check dedicado para balanceador o monitoreo, publícalo solo detrás de red interna o restricción por IP.

### Tareas programadas

El despliegue administra el scheduler de Laravel porque la sincronización de
stock ContaPyme depende de él. `deploy.sh` crea una entrada separada por entorno
en `/etc/cron.d`, activa `cron.service`, prepara el log y valida que
`contapyme-stock-sync` aparezca en `schedule:list`:

```bash
cat /etc/cron.d/portal-distribuidores-production
cat /etc/cron.d/portal-distribuidores-staging
systemctl status cron
sudo -u www-data php artisan schedule:list
tail -f /var/log/laravel/scheduler-production.log
tail -f /var/log/laravel/scheduler-staging.log
```

Cada archivo ejecuta `php artisan schedule:run` una vez por minuto como
`www-data`; Laravel decide en los minutos `00, 05, 10...` si debe encolar la
sincronización. Si el job anterior sigue activo, ese turno se omite sin crear
una ejecución concurrente.

---

## Orden de ejecución recomendado

```
1.  Preparar el servidor (apt install, PHP, Nginx, Node, PostgreSQL)
2.  Crear base de datos y usuario PostgreSQL
3.  Clonar el repositorio
4.  Configurar .env de producción
5.  php artisan key:generate
6.  composer install --no-dev --optimize-autoloader
7.  php artisan migrate --force
8.  php artisan storage:link  (si usas disco local además de R2)
9.  php artisan config:cache && route:cache && view:cache && event:cache
10. npm ci --omit=dev && npm run build
11. chown/chmod storage y bootstrap/cache
12. Configurar Nginx (copiar deploy/nginx.conf)
13. Obtener SSL con Certbot
14. Configurar worker de colas (systemd o Supervisor)
15. Validar con checklist de la sección 11
```

---

## Recursos relacionados

- [`STAGING.md`](./STAGING.md) - flujo de ramas `develop`/`master`, hosts reales, bucket `portal-distribuidores-staging` y refresco seguro de datos.
- [`deploy/nginx.production.conf`](./deploy/nginx.production.conf) - plantilla Nginx para `pedidos.importcorporalmedical.com`.
- [`deploy/nginx.staging.conf`](./deploy/nginx.staging.conf) - plantilla Nginx para `staging-pedidos.importcorporalmedical.com`.
- [`deploy/laravel-queue-prod.service`](./deploy/laravel-queue-prod.service) - worker systemd para produccion.
- [`deploy/laravel-queue-staging.service`](./deploy/laravel-queue-staging.service) - worker systemd para staging.
- [`deploy/refresh-staging.sh`](./deploy/refresh-staging.sh) - script de copia y sanitizacion de datos hacia staging.

*Generado para el despliegue de Portal Distribuidores — Import Corporal Medical SAS*
