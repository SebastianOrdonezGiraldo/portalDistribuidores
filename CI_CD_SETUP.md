# CI/CD Setup — Portal Distribuidores

Guía completa para configurar los pipelines de integración y entrega continua del proyecto en GitHub Actions.

---

## Tabla de contenidos

1. [Arquitectura del pipeline](#1-arquitectura-del-pipeline)
2. [Secretos y variables necesarios](#2-secretos-y-variables-necesarios)
3. [Configurar el environment de producción](#3-configurar-el-environment-de-producción)
4. [Notificaciones Slack](#4-notificaciones-slack)
5. [Scripts de deploy y rollback](#5-scripts-de-deploy-y-rollback)
6. [Flujo de trabajo recomendado](#6-flujo-de-trabajo-recomendado)
7. [Troubleshooting](#7-troubleshooting)

---

## 1. Arquitectura del pipeline

```
┌───────────────────────────────────────────────────────────┐
│  Push a develop                                           │
│    ↓                                                      │
│  CI (4 jobs en paralelo)                                  │
│    ├─ lint     → Laravel Pint (code style)                │
│    ├─ stan     → PHPStan nivel 5 (análisis estático)      │
│    ├─ security → composer audit + npm audit               │
│    └─ test     → PHPUnit + build de assets                │
│    ↓ (todos pasan)                                        │
│  Deploy Staging (automático)                              │
│    ├─ Slack: deploy iniciado                              │
│    ├─ SSH → deploy.sh en VPS staging                      │
│    ├─ Health check (10 intentos × 10s)                    │
│    ├─ Rollback automático si falla                        │
│    └─ Slack: éxito / fallo                                │
└───────────────────────────────────────────────────────────┘

┌───────────────────────────────────────────────────────────┐
│  Push a master                                            │
│    ↓                                                      │
│  CI (mismos 4 jobs)                                       │
│    ↓ (todos pasan)                                        │
│  Release (crea tag vX.Y.Z + release en GitHub)            │
│    ↓                                                      │
│  Deploy Production                                        │
│    ├─ Slack: aprobación requerida                         │
│    ├─ ⏳ ESPERA aprobación manual (environment protection)│
│    ├─ Slack: deploy iniciado                              │
│    ├─ SSH → deploy.sh en VPS producción                   │
│    ├─ Health check (10 intentos × 10s)                    │
│    ├─ Rollback automático si falla                        │
│    └─ Slack: éxito / fallo                                │
└───────────────────────────────────────────────────────────┘
```

---

## 2. Secretos y variables necesarios

Ve a **Settings → Secrets and variables → Actions** en tu repositorio.

### Secretos (sensibles)

| Nombre | Descripción | Ejemplo |
|--------|-------------|---------|
| `VPS_HOST_STAGING` | IP o hostname del VPS de staging | `203.0.113.10` |
| `VPS_SSH_PORT_STAGING` | Puerto SSH de staging | `22` |
| `VPS_USER_STAGING` | Usuario SSH de staging | `deploy` |
| `VPS_SSH_KEY_STAGING` | Clave privada SSH (formato PEM completo) | `-----BEGIN OPENSSH...` |
| `VPS_SSH_PASSPHRASE_STAGING` | Passphrase de la clave SSH (si aplica) | *(vacío si no tiene)* |
| `VPS_HOST_PRODUCTION` | IP o hostname del VPS de producción | `203.0.113.20` |
| `VPS_SSH_PORT_PRODUCTION` | Puerto SSH de producción | `22` |
| `VPS_USER_PRODUCTION` | Usuario SSH de producción | `deploy` |
| `VPS_SSH_KEY_PRODUCTION` | Clave privada SSH (formato PEM completo) | `-----BEGIN OPENSSH...` |
| `VPS_SSH_PASSPHRASE_PRODUCTION` | Passphrase de la clave SSH (si aplica) | *(vacío si no tiene)* |
| `SLACK_WEBHOOK_URL` | URL del Incoming Webhook de Slack | `https://hooks.slack.com/...` |

> **Nota:** Los secretos `VPS_HOST`, `VPS_SSH_PORT`, `VPS_USER`, `VPS_SSH_KEY`, `VPS_SSH_PASSPHRASE` (sin sufijo) se usan como fallback si los específicos por ambiente no están definidos.

### Variables (no sensibles)

| Nombre | Descripción | Valores |
|--------|-------------|---------|
| `SLACK_NOTIFICATIONS_ENABLED` | Activa/desactiva notificaciones Slack | `true` / `false` |

---

## 3. Configurar el environment de producción

El deploy a producción requiere aprobación manual. Para activarlo:

1. Ve a **Settings → Environments** en tu repositorio.
2. Crea un environment llamado exactamente **`production`**.
3. Activa **"Required reviewers"** y agrega los usuarios/equipos que pueden aprobar.
4. Opcionalmente configura un **"Wait timer"** (tiempo de espera antes de permitir el deploy).
5. Guarda los cambios.

Cada vez que CI pase en `master`, el workflow de deploy esperará la aprobación de uno de los reviewers antes de conectar al VPS.

---

## 4. Notificaciones Slack

### Crear un Incoming Webhook en Slack

1. Ve a https://api.slack.com/apps → **Create New App** → **From scratch**.
2. Nombre: `Portal Distribuidores CI/CD` — Workspace: el tuyo.
3. En el menú izquierdo, ve a **Incoming Webhooks** → activar → **Add New Webhook**.
4. Selecciona el canal donde quieres recibir notificaciones (ej: `#deploys`).
5. Copia la URL del webhook y agrégala como secreto `SLACK_WEBHOOK_URL`.
6. Agrega la variable `SLACK_NOTIFICATIONS_ENABLED = true`.

---

## 5. Scripts de deploy y rollback

### `deploy.sh`

Script principal de despliegue. Se ejecuta en el VPS como `root`/`sudo`.

```bash
# Uso básico (se detecta el ambiente desde APP_ENV en .env)
sudo bash deploy.sh
```

### `rollback.sh`

Revierte al commit anterior en caso de fallo.

```bash
# Revertir al commit anterior
sudo bash rollback.sh
```

El script:
- Hace `git reset --hard HEAD~1`
- Reinstala dependencias del commit anterior
- Reconstruye assets frontend
- Revierte la última migración de DB (`migrate:rollback --step=1`)
- Reinicia PHP-FPM y la cola de trabajos
- Limpia y reconstruye cachés de Laravel

### `health-check.sh`

Verifica que la aplicación responde correctamente.

```bash
# Verificar producción
bash health-check.sh https://pedidos.importcorporalmedical.com

# Verificar staging
bash health-check.sh https://staging-pedidos.importcorporalmedical.com
```

---

## 6. Flujo de trabajo recomendado

### Desarrollo de features

```bash
git checkout develop
git checkout -b feature/mi-nueva-funcionalidad
# ... hacer cambios ...
git push origin feature/mi-nueva-funcionalidad
# Abrir PR hacia develop
```

### Deploy a staging

```bash
# Merge de PR a develop
git checkout develop
git merge feature/mi-nueva-funcionalidad
git push origin develop
# → CI corre automáticamente → Deploy staging automático
```

### Deploy a producción

```bash
# Abrir PR de develop → master
# Revisar y aprobar PR
# Al hacer merge a master:
# → CI corre automáticamente
# → Se crea un Release vX.Y.Z automáticamente
# → Deploy espera aprobación manual en GitHub
# → Reviewer aprueba en GitHub Actions
# → Deploy a producción
```

---

## 7. Troubleshooting

### CI falla en "Audit Node.js dependencies"

```
Error: npm audit found vulnerabilities
```

**Solución:** Revisa las vulnerabilidades con `npm audit` localmente y actualiza los paquetes afectados con `npm update` o `npm audit fix`.

### CI falla en "Run Laravel Pint"

```
Error: Found X issues
```

**Solución:** Ejecuta `./vendor/bin/pint` localmente para auto-corregir el estilo de código antes de hacer push.

### CI falla en "Run PHPStan"

```
Error: X errors found
```

**Solución:** Ejecuta `./vendor/bin/phpstan analyse` localmente para ver los errores detallados y corrígelos.

### Deploy falla: "SSH no accesible"

1. Verifica que el secreto `VPS_HOST_STAGING` / `VPS_HOST_PRODUCTION` es correcto.
2. Verifica que el puerto SSH está abierto en el firewall del VPS.
3. Asegúrate de que fail2ban no está bloqueando las IPs de GitHub Actions.

Para obtener las IPs de GitHub Actions: https://api.github.com/meta (campo `actions`).

### Deploy falla: "Health check fallido"

1. Conecta al VPS y revisa los logs: `sudo journalctl -u php8.3-fpm -n 50`
2. Revisa los logs de Nginx: `sudo tail -100 /var/log/nginx/error.log`
3. Revisa los logs de Laravel: `tail -100 /var/www/portalDistribuidores/storage/logs/laravel.log`
4. El rollback automático debería haberse ejecutado; verifica el estado de la aplicación.

### Rollback manual de emergencia

Si necesitas hacer rollback manualmente:

```bash
ssh usuario@vps
cd /var/www/portalDistribuidores  # o portalDistribuidores-staging
sudo bash rollback.sh
```

### Ver logs del pipeline

1. Ve a **Actions** en el repositorio de GitHub.
2. Selecciona el workflow run fallido.
3. Haz clic en el job específico para ver los logs detallados.
