# CI/CD v2 - operacion y activacion

Esta guia describe el pipeline real. `master` es una rama de promocion: ningun
cambio se prueba por primera vez alli y ningun push a `master` despliega
produccion automaticamente.

## Flujo

1. Crear una rama desde `develop` y abrir PR hacia `develop`.
2. CI ejecuta Pint, PHPStan, auditorias, actionlint, ShellCheck, tests SQLite,
   tests PostgreSQL 16 y build con Node 24.
3. El push resultante en `develop` construye `portal-<sha>` y despliega ese
   archivo en staging.
4. El job de staging publica `staging-proof-<tree-sha>` con commit, tree SHA,
   checksum y run de CI.
5. El PR `develop -> master` solo pasa `Staging Promotion Gate` cuando el HEAD
   de `develop` tiene CI, artefacto y deployment de staging exitosos.
6. El merge a `master` no despliega. `Promote Production` debe iniciarse
   manualmente por `SebastianOrdonezGiraldo`.
7. Produccion busca la prueba con el mismo tree SHA que `master`, promueve el
   archivo exacto de staging y crea el release despues del health check.

## Checks obligatorios

- `Code Style (Pint)`
- `Static Analysis (PHPStan)`
- `Security & Workflow Validation`
- `Tests (SQLite)`
- `Tests (PostgreSQL 16)`
- `Build Release Artifact`
- `Staging Promotion Gate` solo para `master`

## GitHub Environments y secretos

Crear `staging` limitado a `develop` y `production` limitado a `master`. Copiar
los valores actuales a secretos propios de cada environment; GitHub no permite
leer ni copiar el valor de un secret mediante API.

| Environment | Secretos |
| --- | --- |
| `staging` | `VPS_HOST_STAGING`, `VPS_SSH_PORT_STAGING`, `VPS_USER_STAGING`, `VPS_SSH_KEY_STAGING`, `VPS_SSH_FINGERPRINT_STAGING` |
| `production` | `VPS_HOST_PRODUCTION`, `VPS_SSH_PORT_PRODUCTION`, `VPS_USER_PRODUCTION`, `VPS_SSH_KEY_PRODUCTION`, `VPS_SSH_FINGERPRINT_PRODUCTION` |

Durante la migracion existen fallbacks a `VPS_HOST`, `VPS_SSH_PORT`,
`VPS_USER`, `VPS_SSH_KEY` y `VPS_SSH_FINGERPRINT`. Eliminarlos solo despues de
dos promociones exitosas.

Produccion valida el conjunto dedicado como una unidad: deben existir los cinco
secretos `VPS_*_PRODUCTION` o ninguno. Un conjunto parcial detiene el workflow
antes de abrir SSH para evitar mezclar host, usuario, llave o fingerprint de
ambientes distintos. Mientras no exista ninguno, el fallback legacy sigue
funcional y queda registrado como advertencia en el run.

Las llaves deben ser dedicadas, distintas por entorno y sin passphrase para
uso no interactivo. El fingerprint usa el formato `SHA256:...` producido por:

```bash
ssh-keyscan -p <puerto> <host> > /tmp/portal-host-keys
ssh-keygen -lf /tmp/portal-host-keys -E sha256
```

El workflow vuelve a obtener las host keys, reintenta hasta tres veces ante un
timeout transitorio y corta antes de autenticar si ninguna coincide con el
fingerprint almacenado.

## Bootstrap unico del VPS

Antes del primer artefacto atomico, actualizar el checkout legacy del entorno a
la version aprobada y ejecutar:

```bash
# Staging
cd /var/www/portalDistribuidores-staging
sudo bash deploy/bootstrap-release-layout.sh \
  --environment staging \
  --base-dir /var/www/portalDistribuidores-staging

# Produccion, solo despues de validar staging
cd /var/www/portalDistribuidores
sudo bash deploy/bootstrap-release-layout.sh \
  --environment production \
  --base-dir /var/www/portalDistribuidores
```

El bootstrap crea inicialmente `current -> <checkout legacy>`, conserva las
directivas TLS del sitio Nginx HTTPS existente, instala las plantillas
Nginx/systemd que apuntan a `current` y valida los servicios. Esto no activa
todavia un release nuevo y permite revertir la configuracion Nginx desde la
copia `*.pre-cicd-v2-*`. Si el sitio real usa otra ruta, se pasa con
`--nginx-site`; las plantillas con el marcador TLS no se copian directamente.
Para mantener compatibilidad con Nginx 1.24+, el bootstrap usa
`listen ... http2` antes de 1.25.1 y renderiza `http2 on` desde 1.25.1, donde el
parametro antiguo esta deprecado y puede producir advertencias al compartir el
puerto 443 entre staging y produccion.

## Layout y rollback

```text
/var/www/portalDistribuidores[-staging]/
  current -> releases/<source-sha>
  releases/<source-sha>/
  shared/.env
  shared/storage/
  shared/deployments/
```

`deploy.sh` verifica checksum y metadata, prepara el candidato, ejecuta
migraciones forward-only, cambia `current` atomicamente y valida `/up`, worker y
scheduler. En produccion crea antes un dump PostgreSQL.

Un fallo posterior al symlink restaura el target anterior. El rollback nunca
ejecuta `migrate:rollback` ni restaura automaticamente la base:

```bash
sudo bash rollback.sh \
  --environment production

sudo bash rollback.sh \
  --environment staging \
  --to <source-sha>
```

Las migraciones deben usar expand/contract. Una eliminacion o renombre de
columna no puede compartir release con codigo que haga necesario volver al
esquema anterior.

## Validacion inicial antes de `master`

1. Merge del PR CI/CD hacia `develop`; CI debe quedar verde.
2. Ejecutar el bootstrap de staging.
3. Reejecutar el run exitoso de `develop` para obtener el primer deploy atomico.
4. Reejecutarlo otra vez y confirmar idempotencia.
5. Activar temporalmente la variable de repositorio
   `STAGING_ROLLBACK_DRILL=true` y reejecutar el mismo run. El job fuerza una
   falla post-switch, exige `ROLLBACK_RESULT status=success` y redespliega el
   candidato normalmente.
6. Eliminar la variable o cambiarla a `false` inmediatamente.
7. Confirmar en el summary: commit, tree, checksum, `/up`, pagina HTTPS, asset,
   PHP-FPM, cola, cron y `contapyme-stock-sync`.
8. Solo entonces abrir `develop -> master`.

Cuando el workflow ya exista en la rama por defecto, `Staging Drill` ofrece el
mismo ejercicio mediante `workflow_dispatch` con `rollback_drill=true`.

## Rulesets y politica de Actions

Activar estos controles despues de que los checks nuevos hayan aparecido al
menos una vez; hacerlo antes bloquearia ramas con checks inexistentes.

- `develop`: PR, una aprobacion, dismiss stale reviews, aprobacion del ultimo
  push, conversaciones resueltas, rama actualizada y seis checks de CI.
- `master`: los mismos controles mas `Staging Promotion Gate`.
- Ambas: sin push directo, borrado, force-push ni bypass configurado.
- Tags `v*`: prohibir eliminacion y reescritura.
- Actions: `sha_pinning_required=true`; permitir solo Actions de GitHub y
  `shivammathur/setup-php`.

## Diagnostico

- Fallo de fingerprint: revisar el secret; no sustituirlo por `ssh-keyscan` sin
  comparacion.
- No hay `staging-proof`: rerun completo de CI en el SHA de `develop`; el
  artefacto expira a los 30 dias.
- `/up` falla: revisar `storage/logs/laravel.log`, DB/cache, permisos de
  `shared/storage`, PHP-FPM, cola y cron. Publicamente `/up` debe seguir
  bloqueado por Nginx.
- Fallo antes del symlink: `current` debe permanecer igual.
- Fallo despues del symlink: buscar `ROLLBACK_RESULT`; si tambien falla,
  detener promociones y ejecutar el rollback manual.
