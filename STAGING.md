# Staging y Produccion

Guia operativa para mantener `staging` y `production` en el mismo VPS sin poner en riesgo la base de datos productiva.

## Resumen

- Produccion:
  - URL: `https://pedidos.importcorporalmedical.com`
  - Carpeta: `/var/www/portalDistribuidores`
  - Rama: `master`
  - Worker: `laravel-queue`
- Staging:
  - URL: `https://staging-pedidos.importcorporalmedical.com`
  - Carpeta: `/var/www/portalDistribuidores-staging`
  - Rama: `develop`
  - Worker: `laravel-queue-staging`
- Bucket R2 de staging: `portal-distribuidores-staging`

## Regla critica

La base de datos de produccion es intocable para operaciones destructivas.

Queda prohibido ejecutar contra produccion:

- `migrate:fresh`
- `db:wipe`
- `DROP DATABASE`
- `TRUNCATE`
- scripts de sanitizacion
- restauraciones de dump fuera de un incidente real

En este repo:

- `deploy.sh` solo permite `php artisan migrate --force`
- `deploy.sh` valida `APP_ENV`, `DEPLOY_ENV_NAME` y `DB_DATABASE`
- `deploy.sh` crea backup antes de migrar en produccion
- `deploy/refresh-staging.sh` solo restaura y sanitiza sobre `portal_distribuidores_staging`

## Workflows

Se agregaron estos workflows:

- `.github/workflows/ci.yml`
- `.github/workflows/deploy-staging.yml`
- `.github/workflows/deploy-production.yml`

Flujo esperado:

1. Trabajar en ramas `feature/*`
2. Abrir PR a `develop`
3. CI valida install, build y tests
4. Merge a `develop` despliega staging
5. Probar staging
6. Abrir PR de `develop` hacia `master`
7. CI valida otra vez
8. Merge a `master` despliega produccion

Notas operativas:

- `CI` reutiliza cache de Composer y cache de npm para reducir tiempo y dependencia de red.
- Los deploys por SSH usan `command_timeout` explicito y `set -e` en el shell remoto para cortar antes ante fallos parciales fuera de `deploy.sh`.
- Cada deploy termina con un smoke check HTTP con reintentos contra la URL canonica del entorno; si la app no responde con `200`, `301` o `302`, el workflow falla.
- Los workflows aceptan secretos dedicados por entorno con fallback a los secretos legacy `VPS_*`, para no romper el flujo actual mientras separas accesos.
- `deploy.sh` refresca en cada ejecucion las host keys de `github.com` en `~/.ssh/known_hosts` del usuario de la app para evitar fallos por claves obsoletas durante `git fetch`.

Si el job falla en `Check SSH port reachability`:

- la conexion TCP nunca llego a abrirse; todavia no es un problema de llave SSH ni de `known_hosts`
- revisar `VPS_HOST_*` y `VPS_SSH_PORT_*` en GitHub Secrets
- verificar en el VPS que `sshd` siga escuchando en ese puerto
- verificar `ufw`, reglas del proveedor y `fail2ban`, porque GitHub-hosted runners pueden quedar bloqueados aunque el puerto funcione desde tu red local

## Secretos por entorno en GitHub

Secretos opcionales para staging:

- `VPS_HOST_STAGING`
- `VPS_SSH_PORT_STAGING`
- `VPS_USER_STAGING`
- `VPS_SSH_KEY_STAGING`
- `VPS_SSH_PASSPHRASE_STAGING`

Secretos opcionales para produccion:

- `VPS_HOST_PRODUCTION`
- `VPS_SSH_PORT_PRODUCTION`
- `VPS_USER_PRODUCTION`
- `VPS_SSH_KEY_PRODUCTION`
- `VPS_SSH_PASSPHRASE_PRODUCTION`

Comportamiento actual:

- Si defines los secretos dedicados por entorno, cada workflow usa esos valores.
- Si no existen, staging y produccion siguen usando los secretos legacy `VPS_HOST`, `VPS_SSH_PORT`, `VPS_USER`, `VPS_SSH_KEY` y `VPS_SSH_PASSPHRASE`.
- Cuando quieras endurecer mas el control, puedes mover esta misma separacion a GitHub Environments `staging` y `production` sin cambiar el flujo de ramas.

## Branch protection

Esto no se puede forzar solo con archivos del repo. Configuralo manualmente en GitHub:

- `develop`: PR obligatorio, status checks obligatorios, sin push directo
- `master`: PR obligatorio, status checks obligatorios, al menos 1 review, sin push directo

## Variables de entorno nuevas

Agregar en `.env` del VPS:

```dotenv
DEPLOY_ENV_NAME=production
DEPLOY_QUEUE_SERVICE=laravel-queue
DEPLOY_PHP_FPM_SERVICE=php8.3-fpm
DEPLOY_CREATE_DB_BACKUP=true
DEPLOY_DB_BACKUP_DIR=/var/backups/portal-distribuidores/production
QUEUE_CONNECTION=database
DB_QUEUE=default
DB_QUEUE_RETRY_AFTER=660
CACHE_STORE=database
```

Para staging:

```dotenv
APP_ENV=staging
APP_URL=https://staging-pedidos.importcorporalmedical.com
SESSION_DOMAIN=staging-pedidos.importcorporalmedical.com
SESSION_SECURE_COOKIE=true

DB_DATABASE=portal_distribuidores_staging
DB_SSLMODE=require

PUBLIC_DISK_DRIVER=s3
AWS_BUCKET=portal-distribuidores-staging

MAIL_MAILER=log
ORDER_NOTIFICATION_EMAIL_DISPATCH=queue
AUTH_ALLOW_PUBLIC_REGISTRATION=true
QUEUE_CONNECTION=database
DB_QUEUE=default
DB_QUEUE_RETRY_AFTER=660
CACHE_STORE=database

DEPLOY_ENV_NAME=staging
DEPLOY_QUEUE_SERVICE=laravel-queue-staging
DEPLOY_PHP_FPM_SERVICE=php8.3-fpm
DEPLOY_CREATE_DB_BACKUP=false

STAGING_SANITIZE_PASSWORD=<password-controlado>
```

El usuario PostgreSQL de staging debe tener permiso `CREATEDB`, porque `deploy/refresh-staging.sh` recrea la base `portal_distribuidores_staging` en cada refresco.

El refresco reemplaza por completo la base de staging. Antes de hacerlo, el
script genera un dump de rollback de la base actual dentro de
`/var/backups/portal-distribuidores/staging-refresh`. Si cualquier paso falla,
staging permanece en mantenimiento y `laravel-queue-staging` queda detenido;
la recuperacion debe ser manual despues de revisar la causa.

## Archivos de infraestructura

Usar estas plantillas:

- Produccion Nginx: `deploy/nginx.production.conf`
- Staging Nginx: `deploy/nginx.staging.conf`
- Worker prod: `deploy/laravel-queue.service`
- Worker staging: `deploy/laravel-queue-staging.service`

El archivo `deploy/nginx.conf` se conserva como alias de compatibilidad orientado a produccion.

Durante cada despliegue atomico, `deploy.sh` renderiza la plantilla Nginx del
entorno desde la release candidata, conserva las directivas TLS administradas
por Certbot, actualiza el sitio de `/etc/nginx/sites-available/` y su enlace en
`sites-enabled`, ejecuta `nginx -t` y solo entonces recarga Nginx. Si la
validacion, la recarga o una fase posterior del deploy falla, se restaura la
configuracion Nginx anterior junto con la release previa.

## Refresco de datos a staging

Script:

```bash
sudo bash deploy/refresh-staging.sh
```

El flujo del script es:

1. Lee los `.env` de produccion y staging
2. Verifica que origen sea `production` y destino `staging`
3. Verifica que la DB origen sea `portal_distribuidores`
4. Verifica que la DB destino sea `portal_distribuidores_staging`
5. Pone staging en mantenimiento
6. Detiene `laravel-queue-staging` para liberar conexiones
7. Crea un backup de rollback de la base actual de staging
8. Hace dump solo lectura desde produccion
9. Recrea solo la base de staging
10. Restaura el dump en staging
11. Ejecuta `php artisan staging:sanitize-data`
12. Verifica que no queden emails externos en usuarios, distribuidores ni pedidos
13. Compara la huella `id + SKU + nombre + estado` del catalogo con produccion
14. Reinicia la cola de staging
15. Levanta staging

La sanitizacion elimina sesiones, colas, cache, descargas y trazas operativas de
inventario. Tambien reemplaza nombres, emails, NIT, telefonos, direcciones,
sucursales y datos de contacto/notas de pedidos por valores controlados de
staging. El refresco copia la base de datos, no los objetos de los buckets de
media; los archivos de R2/S3 se gestionan por separado.

## Credenciales de acceso de staging

Despues de sanitizar, el comando deja estas cuentas controladas:

- Admin: `admin-staging@example.test`
- Distribuidor: `distribuidor-staging@example.test`
- Password: valor de `STAGING_SANITIZE_PASSWORD`

Todos los demas usuarios quedan anonimizados y sus passwords se invalidan.
