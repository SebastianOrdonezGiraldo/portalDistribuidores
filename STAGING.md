# Staging y Produccion

Guia operativa para mantener `staging` y `production` en el mismo VPS sin poner en riesgo la base de datos productiva.

## Resumen

- Produccion:
  - URL: `https://pedidos.importcorporalmedical.com`
  - Carpeta: `/var/www/portalDistribuidores`
  - Rama: `master`
  - Worker: `laravel-queue-prod`
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

## Branch protection

Esto no se puede forzar solo con archivos del repo. Configuralo manualmente en GitHub:

- `develop`: PR obligatorio, status checks obligatorios, sin push directo
- `master`: PR obligatorio, status checks obligatorios, al menos 1 review, sin push directo

## Variables de entorno nuevas

Agregar en `.env` del VPS:

```dotenv
DEPLOY_ENV_NAME=production
DEPLOY_QUEUE_SERVICE=laravel-queue-prod
DEPLOY_PHP_FPM_SERVICE=php8.3-fpm
DEPLOY_CREATE_DB_BACKUP=true
DEPLOY_DB_BACKUP_DIR=/var/backups/portal-distribuidores/production
```

Para staging:

```dotenv
APP_ENV=staging
APP_URL=https://staging-pedidos.importcorporalmedical.com
SESSION_DOMAIN=staging-pedidos.importcorporalmedical.com

DB_DATABASE=portal_distribuidores_staging

PUBLIC_DISK_DRIVER=s3
AWS_BUCKET=portal-distribuidores-staging

MAIL_MAILER=log
ORDER_NOTIFICATION_EMAIL_DISPATCH=queue

DEPLOY_ENV_NAME=staging
DEPLOY_QUEUE_SERVICE=laravel-queue-staging
DEPLOY_PHP_FPM_SERVICE=php8.3-fpm
DEPLOY_CREATE_DB_BACKUP=false

STAGING_SANITIZE_PASSWORD=<password-controlado>
```

El usuario PostgreSQL de staging debe tener permiso `CREATEDB`, porque `deploy/refresh-staging.sh` recrea la base `portal_distribuidores_staging` en cada refresco.

## Archivos de infraestructura

Usar estas plantillas:

- Produccion Nginx: `deploy/nginx.production.conf`
- Staging Nginx: `deploy/nginx.staging.conf`
- Worker prod: `deploy/laravel-queue-prod.service`
- Worker staging: `deploy/laravel-queue-staging.service`

Los archivos legacy `deploy/nginx.conf` y `deploy/laravel-queue.service` quedaron como alias de compatibilidad orientados a produccion.

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
7. Hace dump solo lectura desde produccion
8. Recrea solo la base de staging
9. Restaura el dump en staging
10. Ejecuta `php artisan staging:sanitize-data`
11. Reinicia la cola de staging
12. Levanta staging

## Credenciales de acceso de staging

Despues de sanitizar, el comando deja estas cuentas controladas:

- Admin: `admin-staging@example.test`
- Distribuidor: `distribuidor-staging@example.test`
- Password: valor de `STAGING_SANITIZE_PASSWORD`

Todos los demas usuarios quedan anonimizados y sus passwords se invalidan.
