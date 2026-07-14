# Portal de Distribuidores — Import Corporal Medical SAS

Portal web para distribuidores de Import Corporal Medical SAS. Permite gestionar catálogos de productos, realizar pedidos y obtener cotizaciones automáticas en PDF, con autenticación por roles y notificaciones de correo electrónico.

---

## Tabla de contenidos

1. [Características principales](#1-características-principales)
2. [Stack tecnológico](#2-stack-tecnológico)
3. [Arquitectura del proyecto](#3-arquitectura-del-proyecto)
4. [Requisitos](#4-requisitos)
5. [Instalación para desarrollo local](#5-instalación-para-desarrollo-local)
6. [Variables de entorno / configuración](#6-variables-de-entorno--configuración)
7. [Base de datos y migraciones](#7-base-de-datos-y-migraciones)
8. [Colas, jobs y correo](#8-colas-jobs-y-correo)
9. [Frontend / assets](#9-frontend--assets)
10. [Ejecución en entorno local](#10-ejecución-en-entorno-local)
11. [Despliegue en producción (VPS)](#11-despliegue-en-producción-vps)
12. [Operación en producción](#12-operación-en-producción)
13. [Flujo recomendado de deploy (actualizaciones)](#13-flujo-recomendado-de-deploy-actualizaciones)
14. [Seguridad y buenas prácticas](#14-seguridad-y-buenas-prácticas)
15. [Troubleshooting](#15-troubleshooting)
16. [Recursos adicionales](#16-recursos-adicionales)

---

## 1. Características principales

- Catálogo de productos con búsqueda full-text sobre PostgreSQL
- Carrito de compras y flujo de checkout
- Gestión de pedidos con generación automática de cotizaciones en PDF
- Panel de administración (productos, categorías, distribuidores, pedidos, usuarios)
- Notificaciones de pedido por correo electrónico
- Control de acceso por roles (`admin` / `distribuidor`)
- Almacenamiento de medios en Cloudflare R2 (S3-compatible)
- Procesamiento asíncrono de PDF y correos mediante colas de jobs

---

## 2. Stack tecnológico

| Componente        | Tecnología                                    |
|-------------------|-----------------------------------------------|
| Backend           | Laravel 12 / PHP 8.3+                         |
| Base de datos     | PostgreSQL 15 / 16                            |
| Frontend          | Blade + TailwindCSS + Alpine.js               |
| Build de assets   | Vite                                          |
| Autenticación     | Laravel Breeze (Blade)                        |
| PDF               | `barryvdh/laravel-dompdf`                     |
| Almacenamiento    | Cloudflare R2 (S3-compatible)                 |
| Correo            | SMTP (configurable)                           |
| Colas             | PostgreSQL mediante driver `database`         |
| Búsqueda          | PostgreSQL full-text (stub para Meilisearch)  |

---

## 3. Arquitectura del proyecto

El proyecto sigue un enfoque de **modular monolith**: toda la lógica de negocio está organizada por dominio dentro de `app/Modules/`. Los controladores actúan como orquestadores delgados y delegan la lógica a Actions, Services o Jobs.

```
app/
├── Http/             # Auth base, middleware, requests globales
├── Modules/
│   ├── Admin/        # Panel de administración
│   ├── AuthAccess/   # Roles, RoleMiddleware, modelo Distributor
│   ├── Catalog/      # Productos, variantes, fotos, búsqueda
│   ├── Categories/   # Categorías y sinónimos
│   ├── Company/      # Empresas distribuidoras, sucursales, listas
│   ├── Documents/    # Fichas técnicas y control de descargas
│   ├── Orders/       # Carrito, checkout, pedidos, PDF, correos
│   └── Shared/       # Contratos, enums, excepciones, value objects
└── Providers/        # AppServiceProvider, EventServiceProvider
```

**Patrones aplicados:**

- **Actions** — operaciones de negocio acotadas y cohesivas
- **Services** — lógica de dominio compleja (ej. `OrderPdfGenerator`, `CartService`)
- **Jobs** — operaciones lentas o externas (`GenerateOrderPdfJob`, `SendOrderNotificationEmailJob`)
- **Events / Listeners** — desacoplamiento mediante `OrderPlaced`
- **Policies** + middleware `role:*` — autorización por recurso y rol
- **FormRequests** — validación de entradas HTTP
- **Adapters / Interfaces** — integraciones externas desacopladas (`SearchEngineInterface` → `PostgresSearchEngine`)

---

## 4. Requisitos

### Desarrollo local

- PHP 8.3+
- Composer 2.7+
- PostgreSQL 15 o 16
- Node.js 20 LTS + npm

### Producción (VPS)

- Ubuntu 24.04 LTS
- PHP 8.3 con extensiones: `pgsql`, `pdo_pgsql`, `mbstring`, `xml`, `curl`, `zip`, `gd` (o `imagick`), `intl`, `bcmath`, `fileinfo`, `tokenizer`, `openssl`, `dom`, `opcache`
- Nginx 1.24+
- PHP-FPM 8.3
- PostgreSQL 15 o 16 (local en el VPS)
- Node.js 20 LTS + npm
- Composer 2.7+
- Certbot (SSL con Let's Encrypt)

> **DomPDF** requiere las extensiones `gd` (o `imagick`) junto a `dom` y `xml`.

---

## 5. Instalación para desarrollo local

### Opción A — script automatizado

```bash
composer run setup
```

Ejecuta en secuencia: `composer install` → copia `.env.example` si no existe `.env` → `php artisan key:generate` → `php artisan migrate --force` → `npm install` → `npm run build`.

### Opción B — paso a paso

```bash
# 1. Dependencias PHP
composer install

# 2. Copiar plantilla de entorno
cp .env.example .env

# 3. Generar clave de aplicación
php artisan key:generate

# 4. Ajustar credenciales de PostgreSQL en .env (ver sección 6)

# 5. Ejecutar migraciones
php artisan migrate

# 6. [Opcional] Sembrar datos de demo
SEED_DEMO_DATA=true php artisan db:seed

# 7. Crear enlace simbólico de storage
php artisan storage:link

# 8. Instalar dependencias Node.js y compilar assets
npm install && npm run build
```

> El seed sin `SEED_DEMO_DATA=true` solo crea los usuarios de acceso. El catálogo y las categorías demo se crean únicamente cuando la variable está habilitada.

### Credenciales de demo (solo desarrollo local)

> Define estos valores en `.env` antes de ejecutar `php artisan db:seed`. No los subas a Git.

| Rol          | Correo (.env)                    | Contraseña (.env)                    |
|--------------|----------------------------------|--------------------------------------|
| Admin        | `ACCESS_USERS_ADMIN_EMAIL`       | `ACCESS_USERS_ADMIN_PASSWORD`        |
| Distribuidor | `ACCESS_USERS_DISTRIBUTOR_EMAIL` | `ACCESS_USERS_DISTRIBUTOR_PASSWORD`  |

---

## 6. Variables de entorno / configuración

Copia `.env.example` a `.env` y ajusta los valores según el entorno.

### Variables esenciales en desarrollo local

```dotenv
# Aplicación
APP_NAME="Portal de Distribuidores"
APP_ENV=local
APP_KEY=                        # generado con: php artisan key:generate
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

# Base de datos
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=portal_distribuidores
DB_USERNAME=postgres
DB_PASSWORD=

# Usuarios de acceso para seed (solo desarrollo/demo)
ACCESS_USERS_ADMIN_EMAIL=admin@importcorporal.test
ACCESS_USERS_ADMIN_PASSWORD=
ACCESS_USERS_DISTRIBUTOR_EMAIL=dist@importcorporal.test
ACCESS_USERS_DISTRIBUTOR_PASSWORD=

# Colas, sesiones y caché
QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database

# Almacenamiento (disco local en desarrollo)
FILESYSTEM_DISK=local
PUBLIC_DISK_DRIVER=             # vacío = auto-detecta; local en desarrollo

# Correo (log = no envía correos reales, los escribe en storage/logs)
MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@example.com"

ORDER_NOTIFICATION_EMAIL_TO=
ORDER_NOTIFICATION_EMAIL_DISPATCH=sync

# Búsqueda
SEARCH_ENGINE=postgres
```

### Variables específicas de producción

Consultar la sección [Despliegue en producción — .env de producción](#4-env-de-producción-valores-clave) para ver el bloque completo de producción con R2, SMTP y los ajustes de seguridad necesarios.

### Comportamiento del disco público (`PUBLIC_DISK_DRIVER`)

| Valor   | Comportamiento                                               |
|---------|--------------------------------------------------------------|
| `s3`    | Fuerza Cloudflare R2 independientemente del entorno          |
| `local` | Fuerza disco local (para pruebas sin credenciales R2)        |
| vacío   | Auto-detecta: R2 si `APP_ENV=production` y `AWS_BUCKET` tiene valor; local en el resto |

---

## 7. Base de datos y migraciones

El proyecto usa **PostgreSQL** como único motor de base de datos. Las migraciones cubren todas las tablas del sistema, incluyendo sesiones, colas y caché de Laravel.

```bash
# Ejecutar migraciones pendientes
php artisan migrate

# Revertir la última migración
php artisan migrate:rollback

# Ver estado de las migraciones
php artisan migrate:status
```

> En producción, siempre usar `php artisan migrate --force` para evitar la confirmación interactiva.

---

## 8. Colas, jobs y correo

### Jobs del sistema

| Job                               | Disparado por         | Descripción                                        |
|-----------------------------------|-----------------------|----------------------------------------------------|
| `GenerateOrderPdfJob`             | Evento `OrderPlaced`  | Genera el PDF de cotización al crear un pedido     |
| `SendOrderNotificationEmailJob`   | Evento `OrderPlaced`  | Envía notificación por correo al crear un pedido   |

> **En producción, ambos jobs requieren un worker de cola activo.** Si el worker no está corriendo, los jobs quedan en la tabla `jobs` sin procesarse.

### Driver de colas

Staging y producción usan PostgreSQL tanto para la cola como para el cache de
locks. Como cada entorno tiene una base separada, sus jobs y el lock de
ContaPyme quedan aislados sin infraestructura adicional:

```dotenv
QUEUE_CONNECTION=database
DB_QUEUE=default
DB_QUEUE_RETRY_AFTER=660
CACHE_STORE=database
```

`DB_QUEUE_RETRY_AFTER` debe ser mayor que el timeout de 600 segundos del
worker. Redis no forma parte de la arquitectura operativa del portal.

### Worker en desarrollo local

```bash
# Levanta servidor PHP, worker de colas, logger Pail y Vite en paralelo
composer run dev

# O solo el worker
php artisan queue:listen --tries=1 --timeout=0
```

### Correo en desarrollo local

Configurar `MAIL_MAILER=log` para escribir los correos en `storage/logs/` sin enviarlos realmente. Para verificar la conectividad SMTP antes de usar en producción:

```bash
php artisan tinker
>>> Mail::raw('Test', fn($m) => $m->to('tu@email.com')->subject('Test'));
```

### Variable `ORDER_NOTIFICATION_EMAIL_DISPATCH`

Controla cómo se despacha el correo de notificación cuando se crea un pedido:

| Valor            | Comportamiento                                            |
|------------------|-----------------------------------------------------------|
| `sync`           | Envío inmediato; bloquea la respuesta HTTP del pedido     |
| `queue`          | Asíncrono vía worker (recomendado en producción)          |
| `after_response` | Envío tras entregar la respuesta HTTP                     |

En producción con worker activo, usar `queue`.

---

## 9. Frontend / assets

El proyecto usa **Vite** con **TailwindCSS** y **Alpine.js**.

```bash
# Compilar assets para producción (genera public/build/)
npm run build

# Modo desarrollo con HMR (hot module replacement)
npm run dev
```

Los assets compilados se generan en `public/build/`. Laravel usa `vite.manifest.json` de ese directorio para resolver las URLs de los assets en producción.

> En producción, los assets se compilan una vez durante el deploy. No ejecutar `npm run dev` en el servidor de producción.

---

## 10. Ejecución en entorno local

```bash
# Levanta servidor PHP (8000), worker de colas, logger (Pail) y Vite en paralelo
composer run dev
```

La aplicación estará disponible en `http://127.0.0.1:8000`.

### Rutas principales

**Área de distribuidor:**

| Ruta                                        | Descripción                     |
|---------------------------------------------|---------------------------------|
| `/catalog`                                  | Catálogo de productos           |
| `/cart`                                     | Carrito de compras              |
| `/checkout`                                 | Proceso de checkout             |
| `/orders/{id}`                              | Detalle del pedido y PDF        |
| `/documents/tech-sheet/{productDocument}`   | Descarga de ficha técnica       |

**Área de administración:**

| Ruta                    | Descripción                    |
|-------------------------|--------------------------------|
| `/admin`                | Panel principal                |
| `/admin/categories`     | Gestión de categorías          |
| `/admin/products`       | Gestión de productos           |
| `/admin/distributors`   | Gestión de distribuidores      |
| `/admin/orders`         | Gestión de pedidos             |
| `/admin/users`          | Gestión de usuarios            |

---

## 11. Despliegue en producción (VPS)

> El procedimiento completo, con todos los comandos, está documentado en [`DEPLOY.md`](./DEPLOY.md). Esta sección resume los pasos esenciales.

**Plataforma de referencia:** Hostinger VPS · Ubuntu 24.04 LTS · Nginx · PHP 8.3-FPM · PostgreSQL 16

### 1. Preparar el servidor

```bash
apt update && apt upgrade -y

# Nginx y Certbot
apt install -y nginx certbot python3-certbot-nginx

# PHP 8.3 y extensiones requeridas
apt install -y software-properties-common
add-apt-repository ppa:ondrej/php -y && apt update
apt install -y \
  php8.3 php8.3-fpm php8.3-cli php8.3-pgsql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-intl \
  php8.3-bcmath php8.3-fileinfo php8.3-dom php8.3-opcache php8.3-readline

# Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer && chmod +x /usr/local/bin/composer

# Node.js 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# PostgreSQL 16
apt install -y postgresql postgresql-contrib

# Habilitar e iniciar servicios
systemctl enable --now nginx php8.3-fpm postgresql
```

### 2. Crear base de datos

```bash
sudo -u postgres psql
```

```sql
CREATE USER portal_user WITH PASSWORD 'CAMBIA_ESTA_CONTRASENA';
CREATE DATABASE portal_distribuidores OWNER portal_user;
GRANT ALL PRIVILEGES ON DATABASE portal_distribuidores TO portal_user;
\q
```

### 3. Clonar el repositorio

```bash
mkdir -p /var/www && cd /var/www
git clone https://github.com/TU_ORG/portal-distribuidores.git portal-distribuidores
cd portal-distribuidores
chown -R www-data:www-data /var/www/portal-distribuidores
```

### 4. `.env` de producción (valores clave)

```bash
cp .env.example .env
nano .env  # editar con los valores reales
```

```dotenv
# Aplicación
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

# Logs
LOG_STACK=daily
LOG_LEVEL=warning

# Base de datos
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=portal_distribuidores
DB_USERNAME=portal_user
DB_PASSWORD=CAMBIA_ESTA_CONTRASENA

# Sesiones, colas y caché
SESSION_DRIVER=database
SESSION_DOMAIN=.tu-dominio.com
QUEUE_CONNECTION=database
CACHE_STORE=database

# Almacenamiento — Cloudflare R2
PUBLIC_DISK_DRIVER=s3
AWS_ACCESS_KEY_ID=<tu-access-key-id>
AWS_SECRET_ACCESS_KEY=<tu-secret-access-key>
AWS_DEFAULT_REGION=auto
AWS_BUCKET=portal-distribuidores
AWS_URL=https://pub-XXXX.r2.dev
AWS_ENDPOINT=https://ACCOUNT_ID.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true

# Correo SMTP (Hostinger)
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=notificaciones@tu-dominio.com
MAIL_PASSWORD=<tu-password-smtp>
MAIL_FROM_ADDRESS="notificaciones@tu-dominio.com"
MAIL_FROM_NAME="${APP_NAME}"

ORDER_NOTIFICATION_EMAIL_TO=ventas@tu-dominio.com
ORDER_NOTIFICATION_EMAIL_DISPATCH=queue

# Búsqueda
SEARCH_ENGINE=postgres

VITE_APP_NAME="${APP_NAME}"
```

> Si usas puerto 465 (SSL directo), cambiar `MAIL_SCHEME=smtps` y `MAIL_PORT=465`.

Generar la clave de aplicación:

```bash
sudo -u www-data php artisan key:generate
```

### 5. Instalar dependencias, migraciones y assets

```bash
cd /var/www/portalDistribuidores

# Dependencias PHP (sin paquetes de desarrollo)
sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction

# Migraciones
sudo -u www-data php artisan migrate --force

# Enlace simbólico de storage (solo si se usa disco local además de R2)
sudo -u www-data php artisan storage:link

# Cachés de Laravel
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan event:cache

# Assets frontend
npm ci --omit=dev
npm run build

# Permisos
chown -R www-data:www-data .
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
```

### 6. Nginx

El repositorio incluye la configuración de Nginx en `deploy/nginx.conf`. Cópiala y actívala:

```bash
cp /var/www/portal-distribuidores/deploy/nginx.conf \
   /etc/nginx/sites-available/portal-distribuidores

# Reemplazar todas las ocurrencias de 'tu-dominio.com' con el dominio real
nano /etc/nginx/sites-available/portal-distribuidores

# Activar el sitio
ln -s /etc/nginx/sites-available/portal-distribuidores \
      /etc/nginx/sites-enabled/portal-distribuidores

# Desactivar el sitio default si está activo
rm -f /etc/nginx/sites-enabled/default

# Verificar configuración y recargar
nginx -t && systemctl reload nginx
```

La configuración incluye: HTTP/2, redirección HTTP→HTTPS, compresión gzip, cabeceras de seguridad (`X-Frame-Options`, `X-Content-Type-Options`, etc.), caché de assets estáticos con `expires 1y` para `/build/` y socket Unix para PHP-FPM (`/var/run/php/php8.3-fpm.sock`).

### 7. SSL con Let's Encrypt

> El dominio debe apuntar a la IP del VPS antes de ejecutar este paso.

```bash
certbot --nginx -d tu-dominio.com -d www.tu-dominio.com \
  --non-interactive --agree-tos --email admin@tu-dominio.com

# Verificar renovación automática
certbot renew --dry-run
systemctl status certbot.timer
```

### 8. Worker de colas

**Los jobs de generación de PDF y envío de correos requieren un worker activo en producción.** Sin el worker, los jobs quedan en la tabla `jobs` sin procesarse.

#### Opción A — systemd (recomendada para Ubuntu 24.04)

```bash
# Crear directorio de logs
mkdir -p /var/log/laravel && chown www-data:www-data /var/log/laravel

# Copiar el unit file incluido en el repositorio
cp /var/www/portal-distribuidores/deploy/laravel-queue.service \
   /etc/systemd/system/laravel-queue.service

# Habilitar e iniciar el servicio
systemctl daemon-reload
systemctl enable --now laravel-queue

# Verificar estado
systemctl status laravel-queue
journalctl -u laravel-queue -f
```

#### Opción B — Supervisor (alternativa)

```bash
apt install -y supervisor
mkdir -p /var/log/laravel && chown www-data:www-data /var/log/laravel

cp /var/www/portal-distribuidores/deploy/laravel-queue-supervisor.conf \
   /etc/supervisor/conf.d/laravel-queue.conf

supervisorctl reread
supervisorctl update
supervisorctl start laravel-queue:*
supervisorctl status
```

El worker se configura con: `--tries=3 --max-time=3600 --sleep=3 --queue=default`.

### 9. Almacenamiento: Cloudflare R2

En producción, los archivos del disco público (fotos de productos, PDFs de cotizaciones) se almacenan en **Cloudflare R2**.

**Configuración del bucket:**

1. Ir a [Cloudflare Dashboard](https://dash.cloudflare.com) → R2 → Create Bucket
2. Nombre del bucket: `portal-distribuidores`
3. En Settings → Public Access: habilitar "Allow Public Access"
4. Copiar la **Public bucket URL** (`https://pub-XXXX.r2.dev`) → `AWS_URL`
5. Ir a R2 Overview → Manage R2 API Tokens → Create Token
6. Permisos: `Object Read & Write` sobre el bucket
7. Copiar Access Key ID → `AWS_ACCESS_KEY_ID`
8. Copiar Secret Access Key → `AWS_SECRET_ACCESS_KEY`
9. Copiar Account ID → usar en `AWS_ENDPOINT` (`https://ACCOUNT_ID.r2.cloudflarestorage.com`)

> Con `PUBLIC_DISK_DRIVER=s3`, el enlace simbólico `storage:link` no es necesario para el disco público. Todas las URLs apuntan directamente a R2.

### 10. Validaciones finales

```bash
# La app responde correctamente
curl -I https://tu-dominio.com

# Conexión a la base de datos
sudo -u www-data php artisan db:show

# Logs de Laravel sin errores
tail -f /var/www/portal-distribuidores/storage/logs/laravel.log

# Worker activo
systemctl status laravel-queue

# R2 accesible
sudo -u www-data php artisan tinker
>>> Storage::disk('public')->exists('test.txt') ? 'R2 OK' : 'Verificar credenciales R2';

# SMTP funcional
>>> Mail::raw('Test SMTP', fn($m) => $m->to('tu@email.com')->subject('Test'));
```

**Checklist de validación:**

- [ ] La app carga en `https://tu-dominio.com` sin errores
- [ ] El login y la sesión funcionan correctamente
- [ ] Se puede subir una imagen o documento (va a R2)
- [ ] Se puede generar un PDF de pedido (DomPDF)
- [ ] Se envía correo de notificación de pedido (SMTP)
- [ ] `APP_DEBUG=false` confirmado en `.env`
- [ ] Los errores van a `storage/logs/` y no a pantalla
- [ ] El worker de colas está activo y procesando jobs
- [ ] SSL activo con redirección HTTP → HTTPS

---

## 12. Operación en producción

### Reiniciar servicios

```bash
# Recargar Nginx (sin interrumpir conexiones activas)
systemctl reload nginx

# Recargar PHP-FPM
systemctl reload php8.3-fpm

# Reiniciar worker de colas (systemd)
systemctl restart laravel-queue

# Reiniciar worker de colas (Supervisor)
supervisorctl restart laravel-queue:*
```

### Monitorear colas

```bash
# Estado del servicio (systemd)
systemctl status laravel-queue

# Logs del worker en tiempo real
journalctl -u laravel-queue -f
tail -f /var/log/laravel/queue.log
tail -f /var/log/laravel/queue-error.log

# Jobs pendientes en la base de datos
sudo -u www-data php artisan queue:monitor
```

### Gestión de cachés

```bash
# Limpiar todos los cachés
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan event:clear

# Regenerar cachés (recomendado en producción)
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan event:cache
```

> Los cachés deben regenerarse después de cualquier cambio en `.env`, rutas o configuración de la aplicación.

### Logs de la aplicación

```bash
# Logs diarios de Laravel
tail -f /var/www/portal-distribuidores/storage/logs/laravel.log

# Logs de acceso y errores de Nginx
tail -f /var/log/nginx/portal-distribuidores.access.log
tail -f /var/log/nginx/portal-distribuidores.error.log

# Logs del worker de colas
tail -f /var/log/laravel/queue.log
tail -f /var/log/laravel/queue-error.log
```

### Modo mantenimiento

```bash
# Activar
sudo -u www-data php artisan down

# Desactivar
sudo -u www-data php artisan up
```

---

## 13. Flujo recomendado de deploy (actualizaciones)

Para actualizar el código en producción, usar el script incluido en el repositorio:

```bash
cd /var/www/portalDistribuidores
sudo bash deploy.sh
```

> Dar permiso de ejecución la primera vez: `chmod +x deploy.sh`

El script ejecuta automáticamente los siguientes pasos:

1. `git pull` desde la rama activa
2. `composer install --no-dev --optimize-autoloader`
3. `php artisan migrate --force`
4. Limpieza y regeneración de cachés (config, route, view, event)
5. `npm ci --omit=dev && npm run build`
6. Ajuste de permisos en `storage/` y `bootstrap/cache/`
7. Instalación/verificación de la unidad systemd y reinicio del worker correspondiente
8. `systemctl reload php8.3-fpm`

El script verifica previamente que existan PHP, Composer, npm y el archivo `.env`. Si alguna verificación falla, el proceso se detiene antes de realizar cambios.

### Tareas programadas

La sincronización automática de stock ContaPyme está definida cada cinco minutos
en `routes/console.php` y usa el mismo dispatcher/job del botón manual.
`deploy.sh` instala o actualiza de forma idempotente una entrada por entorno en
`/etc/cron.d/portal-distribuidores-{entorno}`, valida que el daemon `cron` esté
activo y confirma la tarea con `php artisan schedule:list`:

```bash
* * * * * www-data cd /var/www/portalDistribuidores && /usr/bin/php artisan schedule:run >> /var/log/laravel/scheduler-production.log 2>&1
* * * * * www-data cd /var/www/portalDistribuidores-staging && /usr/bin/php artisan schedule:run >> /var/log/laravel/scheduler-staging.log 2>&1
```

Verificar la tarea registrada con:

```bash
systemctl status cron
sudo -u www-data php artisan schedule:list
tail -f /var/log/laravel/scheduler-production.log
tail -f /var/log/laravel/scheduler-staging.log
```

---

## 14. Seguridad y buenas prácticas

- `APP_DEBUG=false` en producción — evita exponer stack traces al público
- `APP_KEY` nunca debe subirse a Git ni compartirse
- Permisos del `.env`: `chmod 640 .env && chown www-data:www-data .env`
- Habilitar firewall en el VPS:

  ```bash
  ufw allow 22 && ufw allow 80 && ufw allow 443 && ufw enable
  ```

- PostgreSQL debe escuchar solo en `localhost`; el puerto 5432 no debe estar expuesto externamente
- Verificar que OPcache está habilitado:

  ```bash
  php -r "echo opcache_get_status()['opcache_enabled'] ? 'OPcache ON' : 'OPcache OFF';"
  ```

- Los logs de producción usan rotación diaria (`LOG_STACK=daily`); verificar espacio en disco periódicamente
- La renovación automática de SSL está gestionada por `certbot.timer`; verificar con `systemctl status certbot.timer`

---

## 15. Troubleshooting

### La app muestra error 500

```bash
# Revisar logs de Laravel
tail -f /var/www/portal-distribuidores/storage/logs/laravel.log

# Verificar permisos
ls -la /var/www/portal-distribuidores/storage/
ls -la /var/www/portal-distribuidores/bootstrap/cache/

# Corregir permisos si es necesario
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### Error de caché después de un deploy

```bash
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:cache
```

### Los PDFs o correos no se generan

Los jobs son asíncronos. Si el worker no está activo, los jobs quedan pendientes en la tabla `jobs`.

```bash
# Verificar estado del worker
systemctl status laravel-queue

# Ver jobs pendientes
sudo -u www-data php artisan queue:monitor

# Procesar la cola manualmente (diagnóstico)
sudo -u www-data php artisan queue:work --once
```

### Error de conexión a la base de datos

```bash
# Verificar que PostgreSQL está activo
systemctl status postgresql

# Probar la conexión desde Laravel
sudo -u www-data php artisan db:show

# Revisar credenciales en .env
grep DB_ /var/www/portal-distribuidores/.env
```

### Los assets no cargan (CSS / JS en blanco)

```bash
# Verificar que los assets están compilados
ls -la /var/www/portal-distribuidores/public/build/

# Recompilar si el directorio está vacío o desactualizado
npm ci --omit=dev && npm run build

# Limpiar y regenerar caché de vistas
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan view:cache
```

### El correo SMTP no funciona

```bash
sudo -u www-data php artisan tinker
>>> Mail::raw('Test SMTP', fn($m) => $m->to('tu@email.com')->subject('Test'));
```

Revisar `MAIL_HOST`, `MAIL_PORT`, `MAIL_SCHEME`, `MAIL_USERNAME` y `MAIL_PASSWORD` en `.env`. Con Hostinger en puerto 587, usar `MAIL_SCHEME=tls`.

### Verificar conexión con Cloudflare R2

```bash
sudo -u www-data php artisan tinker
>>> Storage::disk('public')->exists('test.txt') ? 'R2 OK' : 'Verificar credenciales R2';
```

### Nginx devuelve 502 Bad Gateway

```bash
# Verificar que PHP-FPM está activo
systemctl status php8.3-fpm

# Verificar que el socket existe
ls -la /var/run/php/php8.3-fpm.sock

# Reiniciar PHP-FPM
systemctl restart php8.3-fpm
```

---

## 16. Recursos adicionales

- **Guía completa de despliegue:** [`DEPLOY.md`](./DEPLOY.md) — cubre en detalle cada paso del despliegue inicial en el VPS, incluyendo preparación del servidor, configuración de PostgreSQL, Nginx, SSL y worker de colas.
- **Guia de staging y produccion:** [`STAGING.md`](./STAGING.md) — define la separacion de ambientes, los workflows `develop`/`master`, el bucket `portal-distribuidores-staging` y el refresco seguro de datos hacia staging.
- **Script de actualización:** [`deploy.sh`](./deploy.sh) — automatiza el ciclo completo de actualización en producción.
- **Script de refresco de staging:** [`deploy/refresh-staging.sh`](./deploy/refresh-staging.sh) — crea un dump solo lectura desde produccion, restaura en staging y ejecuta la sanitizacion.
- **Configuraciones de Nginx:** [`deploy/nginx.production.conf`](./deploy/nginx.production.conf) y [`deploy/nginx.staging.conf`](./deploy/nginx.staging.conf) — plantillas separadas por ambiente.
- **Workers systemd:** [`deploy/laravel-queue.service`](./deploy/laravel-queue.service) para producción y [`deploy/laravel-queue-staging.service`](./deploy/laravel-queue-staging.service) para staging.
- **Worker Supervisor:** [`deploy/laravel-queue-supervisor.conf`](./deploy/laravel-queue-supervisor.conf) — configuración alternativa con Supervisor.
- **Documentacion HTTP/OpenAPI:** [`docs/documentacion-api.md`](./docs/documentacion-api.md) — explica como generar y proteger `/docs`, `/docs.openapi` y `/docs.postman` con Scribe.
- **Colección Postman:** [`postman/PortalDistribuidores.postman_collection.json`](./postman/PortalDistribuidores.postman_collection.json) — rutas principales del proyecto documentadas.

---

*Portal de Distribuidores — Import Corporal Medical SAS*

---

---

## Cambios realizados

### Qué se corrigió y por qué

---

#### 1. Se eliminó la mezcla de flujos de desarrollo local y producción

**Problema:** El README original mezclaba en una única sección de instalación pasos que aplican a ambos entornos (p. ej., `php artisan serve` junto a instrucciones de Railway), lo que generaba ambigüedad sobre cuándo y dónde aplicar cada comando.

**Corrección:** Se separaron explícitamente en secciones independientes: instalación local (secciones 5–10) y despliegue en producción VPS (sección 11). Cada paso indica claramente si aplica a uno u otro contexto.

---

#### 2. Se eliminó la referencia a Railway como flujo de producción

**Problema:** El README incluía una sección titulada "Correo en Railway" que mezclaba la configuración SMTP con la plataforma Railway, lo que no refleja el estado actual del proyecto (desplegado en VPS Hostinger).

**Corrección:** Se eliminó Railway como flujo principal. La configuración de correo se documenta de forma genérica (SMTP configurable) con los valores concretos de Hostinger ya presentes en `DEPLOY.md` y `.env.example`. Railway no aparece en el nuevo README porque el repositorio no contiene archivos de configuración activos para esa plataforma.

---

#### 3. Se documentaron los scripts de `composer.json`

**Problema:** El README original no hacía referencia a los scripts `setup` y `dev` definidos en `composer.json`, que son la forma más directa de iniciar el proyecto en desarrollo.

**Corrección:** Se documenta `composer run setup` como método de instalación automatizado y `composer run dev` como comando para levantar el entorno completo de desarrollo (servidor + worker + Pail + Vite en paralelo mediante `concurrently`).

---

#### 4. Se documentó el script `deploy.sh`

**Problema:** El README original no mencionaba `deploy.sh`, que es el mecanismo principal de actualización en producción.

**Corrección:** Se agregó la sección "Flujo recomendado de deploy" con el uso de `deploy.sh` y el detalle de cada paso que ejecuta, extraído del propio script del repositorio.

---

#### 5. Se documentaron los archivos de despliegue incluidos en `deploy/`

**Problema:** El README original no hacía referencia a los archivos en `deploy/` (`nginx.conf`, `laravel-queue.service`, `laravel-queue-supervisor.conf`), a pesar de que son piezas operativas críticas ya listas para usar.

**Corrección:** Se referencian explícitamente en las secciones de Nginx, worker de colas y recursos adicionales, con los comandos exactos para copiarlos y activarlos.

---

#### 6. Se clarificó el worker de colas como requisito de producción

**Problema:** El README original mencionaba el worker (`php artisan queue:work`) como un paso más de instalación, sin enfatizar que su ausencia en producción impide la generación de PDFs y el envío de correos.

**Corrección:** Se destaca explícitamente que los jobs `GenerateOrderPdfJob` y `SendOrderNotificationEmailJob` son asíncronos y **requieren un worker activo en producción**. Se documentan las dos opciones soportadas por el repositorio: systemd (`laravel-queue.service`) y Supervisor (`laravel-queue-supervisor.conf`).

---

#### 7. Se documentó Cloudflare R2 como almacenamiento de producción

**Problema:** El README original no mencionaba R2, a pesar de que `.env.example` y `DEPLOY.md` lo configuran como disco público en producción.

**Corrección:** Se documenta la lógica de auto-detección del disco (`PUBLIC_DISK_DRIVER`), el proceso de creación del bucket y la relación con `storage:link` (no necesario cuando se usa R2 para el disco público).

---

#### 8. Se clarificó el comportamiento de `SEED_DEMO_DATA`

**Problema:** El README original mencionaba el seed pero no diferenciaba claramente qué se crea con y sin `SEED_DEMO_DATA=true`.

**Corrección:** Se explica que sin la variable solo se crean usuarios de acceso; el catálogo y las categorías demo solo se crean cuando `SEED_DEMO_DATA=true`. Las credenciales de demo se ubican en una sección visualmente marcada como exclusiva de desarrollo.

---

#### 9. Se añadió sección de Troubleshooting operacional

**Problema:** El README original no incluía guía de diagnóstico para los problemas más comunes en producción.

**Corrección:** Se agregó la sección 15 con los casos más frecuentes: error 500, problemas de caché, jobs sin procesar, error de base de datos, assets en blanco, fallo de SMTP, error de R2 y 502 Bad Gateway de Nginx.

---

#### 10. Se añadió la sección de Operación en producción

**Problema:** No había instrucciones para operar el sistema una vez desplegado (reiniciar servicios, monitorear la cola, gestionar cachés, revisar logs, activar mantenimiento).

**Corrección:** Se agregó la sección 12 con los comandos operativos agrupados por función: reinicio de servicios, monitoreo de colas, gestión de cachés, rutas de logs y modo mantenimiento.

---

#### 11. Se eliminó el texto de plantilla genérico

**Problema:** El README original no tenía texto genérico, pero la reestructura podría haberlo introducido.

**Corrección:** Todo el contenido nuevo está respaldado por archivos reales del repositorio. No se inventaron comandos, variables de entorno ni servicios. Donde existía ambigüedad (p. ej., el script `start.ps1` referenciado en el README original pero ausente del árbol de directorios), se omitió la referencia en lugar de incluirla sin respaldo.

---

#### 12. Se añadió tabla de contenidos y estructura navegable

**Corrección:** Se agregó tabla de contenidos con anclas para facilitar la navegación, dado que el README es ahora un documento de referencia más extenso.
# CI/CD Test
