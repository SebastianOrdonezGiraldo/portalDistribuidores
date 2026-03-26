#!/usr/bin/env bash
# =============================================================================
# refresh-staging.sh
# Copia datos desde produccion hacia staging en modo solo lectura para el origen.
# NUNCA modifica la base de datos de produccion.
# =============================================================================

set -Eeuo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

PRODUCTION_APP_DIR="${PRODUCTION_APP_DIR:-/var/www/portalDistribuidores}"
STAGING_APP_DIR="${STAGING_APP_DIR:-/var/www/portalDistribuidores-staging}"
EXPECTED_PROD_DB="portal_distribuidores"
EXPECTED_STAGING_DB="portal_distribuidores_staging"
DUMP_DIR="${DUMP_DIR:-/var/backups/portal-distribuidores/staging-refresh}"
APP_USER="${APP_USER:-www-data}"

log_step()  { echo -e "\n${CYAN}▶ $1${NC}"; }
log_ok()    { echo -e "${GREEN}  ✓ $1${NC}"; }
log_warn()  { echo -e "${YELLOW}  ! $1${NC}"; }
log_error() { echo -e "${RED}  x $1${NC}"; }

fail() {
    log_error "$1"
    exit 1
}

shell_escape() {
    printf '%q' "$1"
}

require_file() {
    local file="$1"
    [[ -f "$file" ]] || fail "No se encontro el archivo requerido: $file"
}

resolve_cmd() {
    local name="$1"
    local path
    path="$(command -v "$name" 2>/dev/null || true)"
    [[ -n "$path" ]] || fail "No se encontro '$name' en PATH"
    printf '%s\n' "$path"
}

read_env_value() {
    local env_file="$1"
    local key="$2"
    local raw

    raw="$(grep -E "^${key}=" "$env_file" | head -n 1 | cut -d= -f2- || true)"
    raw="${raw%\"}"
    raw="${raw#\"}"
    raw="${raw%\'}"
    raw="${raw#\'}"
    printf '%s' "$raw"
}

load_app_env() {
    local env_file="$1"
    local prefix="$2"

    printf -v "${prefix}_APP_ENV" '%s' "$(read_env_value "$env_file" APP_ENV)"
    printf -v "${prefix}_DB_HOST" '%s' "$(read_env_value "$env_file" DB_HOST)"
    printf -v "${prefix}_DB_PORT" '%s' "$(read_env_value "$env_file" DB_PORT)"
    printf -v "${prefix}_DB_NAME" '%s' "$(read_env_value "$env_file" DB_DATABASE)"
    printf -v "${prefix}_DB_USER" '%s' "$(read_env_value "$env_file" DB_USERNAME)"
    printf -v "${prefix}_DB_PASSWORD" '%s' "$(read_env_value "$env_file" DB_PASSWORD)"
}

run_in_staging_app() {
    local command="$1"
    sudo -u "$APP_USER" bash -lc "cd $(shell_escape "$STAGING_APP_DIR") && $command"
}

require_file "$PRODUCTION_APP_DIR/.env"
require_file "$STAGING_APP_DIR/.env"

PG_DUMP_BIN="$(resolve_cmd pg_dump)"
PG_RESTORE_BIN="$(resolve_cmd pg_restore)"
DROPDB_BIN="$(resolve_cmd dropdb)"
CREATEDB_BIN="$(resolve_cmd createdb)"
PHP_BIN="$(resolve_cmd php)"

load_app_env "$PRODUCTION_APP_DIR/.env" PROD
load_app_env "$STAGING_APP_DIR/.env" STAGING

[[ "$PROD_APP_ENV" == "production" ]] || fail "El origen debe tener APP_ENV=production. Valor actual: $PROD_APP_ENV"
[[ "$STAGING_APP_ENV" == "staging" ]] || fail "El destino debe tener APP_ENV=staging. Valor actual: $STAGING_APP_ENV"
[[ "$PROD_DB_NAME" == "$EXPECTED_PROD_DB" ]] || fail "La base de produccion esperada es $EXPECTED_PROD_DB. Valor actual: $PROD_DB_NAME"
[[ "$STAGING_DB_NAME" == "$EXPECTED_STAGING_DB" ]] || fail "La base de staging esperada es $EXPECTED_STAGING_DB. Valor actual: $STAGING_DB_NAME"
[[ "$PROD_DB_NAME" != "$STAGING_DB_NAME" ]] || fail "La base origen y destino no pueden ser la misma."
[[ "$PRODUCTION_APP_DIR" != "$STAGING_APP_DIR" ]] || fail "La carpeta de produccion y staging no pueden coincidir."

STAGING_SANITIZE_PASSWORD="$(read_env_value "$STAGING_APP_DIR/.env" STAGING_SANITIZE_PASSWORD)"
[[ -n "$STAGING_SANITIZE_PASSWORD" ]] || fail "Define STAGING_SANITIZE_PASSWORD en el .env de staging antes de refrescar datos."

mkdir -p "$DUMP_DIR"
chown "$APP_USER:$APP_USER" "$DUMP_DIR" || true

DUMP_FILE="$DUMP_DIR/${PROD_DB_NAME}-$(date '+%Y%m%d-%H%M%S').dump"
STAGING_DOWN=false
STAGING_QUEUE_STOPPED=false

cleanup() {
    if [[ "$STAGING_QUEUE_STOPPED" == "true" ]]; then
        log_warn "Levantando el worker de staging tras la operacion..."
        systemctl start laravel-queue-staging >/dev/null 2>&1 || true
        STAGING_QUEUE_STOPPED=false
    fi

    if [[ "$STAGING_DOWN" == "true" ]]; then
        log_warn "Levantando staging tras la operacion..."
        run_in_staging_app "$(shell_escape "$PHP_BIN") artisan up" || true
    fi
}
trap cleanup EXIT

log_step "Poniendo staging en mantenimiento..."
run_in_staging_app "$(shell_escape "$PHP_BIN") artisan down --render=\"errors::503\" --retry=60"
STAGING_DOWN=true
log_ok "Staging en mantenimiento"

log_step "Deteniendo el worker de staging para liberar conexiones..."
run_in_staging_app "$(shell_escape "$PHP_BIN") artisan queue:restart" || true
if systemctl cat laravel-queue-staging >/dev/null 2>&1; then
    systemctl stop laravel-queue-staging
    STAGING_QUEUE_STOPPED=true
    log_ok "Worker laravel-queue-staging detenido"
else
    log_warn "No se encontro el servicio laravel-queue-staging. Continuando sin detenerlo."
fi

log_step "Creando dump solo lectura desde produccion..."
sudo -u "$APP_USER" env PGPASSWORD="$PROD_DB_PASSWORD" \
    "$PG_DUMP_BIN" \
    --host="${PROD_DB_HOST:-127.0.0.1}" \
    --port="${PROD_DB_PORT:-5432}" \
    --username="$PROD_DB_USER" \
    --format=custom \
    --file="$DUMP_FILE" \
    "$PROD_DB_NAME"
[[ -s "$DUMP_FILE" ]] || fail "No se pudo generar el dump de produccion."
log_ok "Dump generado en $DUMP_FILE"

log_step "Recreando la base de staging..."
env PGPASSWORD="$STAGING_DB_PASSWORD" \
    "$DROPDB_BIN" \
    --if-exists \
    --host="${STAGING_DB_HOST:-127.0.0.1}" \
    --port="${STAGING_DB_PORT:-5432}" \
    --username="$STAGING_DB_USER" \
    "$STAGING_DB_NAME"
env PGPASSWORD="$STAGING_DB_PASSWORD" \
    "$CREATEDB_BIN" \
    --host="${STAGING_DB_HOST:-127.0.0.1}" \
    --port="${STAGING_DB_PORT:-5432}" \
    --username="$STAGING_DB_USER" \
    "$STAGING_DB_NAME"
log_ok "Base de staging recreada"

log_step "Restaurando dump sobre staging..."
env PGPASSWORD="$STAGING_DB_PASSWORD" \
    "$PG_RESTORE_BIN" \
    --no-owner \
    --no-privileges \
    --host="${STAGING_DB_HOST:-127.0.0.1}" \
    --port="${STAGING_DB_PORT:-5432}" \
    --username="$STAGING_DB_USER" \
    --dbname="$STAGING_DB_NAME" \
    "$DUMP_FILE"
log_ok "Dump restaurado sobre staging"

log_step "Sanitizando datos en staging..."
run_in_staging_app "$(shell_escape "$PHP_BIN") artisan staging:sanitize-data --password=$(shell_escape "$STAGING_SANITIZE_PASSWORD")"
log_ok "Datos sanitizados"

log_step "Reiniciando la cola de staging..."
run_in_staging_app "$(shell_escape "$PHP_BIN") artisan queue:restart" || true
if systemctl cat laravel-queue-staging >/dev/null 2>&1; then
    systemctl restart laravel-queue-staging
    STAGING_QUEUE_STOPPED=false
    log_ok "Servicio laravel-queue-staging reiniciado"
else
    log_warn "No se encontro el servicio laravel-queue-staging. Reinicialo manualmente si aplica."
fi

run_in_staging_app "$(shell_escape "$PHP_BIN") artisan up"
STAGING_DOWN=false
log_ok "Staging nuevamente en linea"

echo -e "\n${GREEN}============================================"
echo "  Refresco de staging completado"
echo "  Dump usado: $DUMP_FILE"
echo -e "============================================${NC}\n"
