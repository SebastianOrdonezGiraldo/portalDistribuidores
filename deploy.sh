#!/usr/bin/env bash
# =============================================================================
# deploy.sh - Portal Distribuidores
# Safe deploy script for production and staging on the same VPS.
# =============================================================================

set -Eeuo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_USER="www-data"
SYSTEM_PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"
EXPECTED_PRODUCTION_DB="portal_distribuidores"
EXPECTED_STAGING_DB="portal_distribuidores_staging"
DEFAULT_PHP_FPM_SERVICE="php8.3-fpm"

DEPLOY_ENV_NAME=""
QUEUE_SERVICE=""
PHP_FPM_SERVICE=""
DEPLOY_CREATE_DB_BACKUP=""
DEPLOY_DB_BACKUP_DIR=""
SCHEDULER_CRON_FILE=""
SCHEDULER_LOG_FILE=""
DEPLOY_BRANCH_VALUE="${DEPLOY_BRANCH:-}"
DEPLOY_TARGET_SHA_VALUE="${DEPLOY_TARGET_SHA:-}"

APP_ENV_VALUE=""
APP_DEBUG_VALUE=""
QUEUE_CONNECTION_VALUE=""
CACHE_STORE_VALUE=""
DB_QUEUE_VALUE=""
DB_QUEUE_RETRY_AFTER_VALUE=""
DB_HOST_VALUE=""
DB_PORT_VALUE=""
DB_DATABASE_VALUE=""
DB_USERNAME_VALUE=""
DB_PASSWORD_VALUE=""
APP_URL_VALUE=""
SESSION_SECURE_COOKIE_VALUE=""
DB_SSLMODE_VALUE=""
PRIVATE_DISK_DRIVER_VALUE=""
PRIVATE_BUCKET_VALUE=""
ORDER_PDFS_DISK_VALUE=""
TECH_SHEETS_DISK_VALUE=""

APP_HOME=""
QUEUE_UNIT_SOURCE=""
PHP_BIN=""
PG_DUMP_BIN=""
COMPOSER_BIN=""
GIT_BIN=""
NPM_BIN=""
NODE_BIN=""
SSH_KEYSCAN_BIN=""
SSH_KEYGEN_BIN=""
CURL_BIN=""

MAINTENANCE_ACTIVE=false
BACKUP_FILE=""
BOOTSTRAP_COMPOSER_DONE=false

log_step()  { echo -e "\n${CYAN}>> $1${NC}"; }
log_ok()    { echo -e "${GREEN}  OK  $1${NC}"; }
log_warn()  { echo -e "${YELLOW}  WARN $1${NC}"; }
log_error() { echo -e "${RED}  ERR  $1${NC}"; }

fail() {
    log_error "$1"
    exit 1
}

shell_escape() {
    printf '%q' "$1"
}

bool_true() {
    case "${1,,}" in
        1|true|yes|on) return 0 ;;
        *) return 1 ;;
    esac
}

extract_url_host() {
    local url="$1"
    local host="${url#http://}"
    host="${host#https://}"
    host="${host%%/*}"
    host="${host%%:*}"
    printf '%s' "$host"
}

is_non_canonical_host() {
    local host="${1,,}"

    case "$host" in
        ""|localhost|127.0.0.1|::1|*.test|*.localhost|*.invalid|*.example|*.ngrok.io|*.ngrok-free.dev)
            return 0
            ;;
        *)
            return 1
            ;;
    esac
}

resolve_cmd() {
    local name="$1"
    local path
    path="$(command -v "$name" 2>/dev/null || true)"
    [[ -n "$path" ]] || fail "No se encontro '$name' en PATH"
    printf '%s\n' "$path"
}

require_file() {
    local file="$1"
    [[ -f "$file" ]] || fail "No se encontro $(basename "$file") en $APP_DIR"
}

require_git_repo() {
    if [[ ! -d "$APP_DIR/.git" && ! -f "$APP_DIR/.git" ]]; then
        fail "No se encontro .git en $APP_DIR. Este script debe ejecutarse dentro del repositorio."
    fi
}

refresh_github_known_hosts() {
    local ssh_dir="$APP_HOME/.ssh"
    local known_hosts_file="$ssh_dir/known_hosts"
    local refreshed_keys_file=""
    local api_version="2022-11-28"

    mkdir -p "$ssh_dir"
    touch "$known_hosts_file"
    chown -R "$APP_USER:$APP_USER" "$ssh_dir"
    chmod 700 "$ssh_dir"
    chmod 644 "$known_hosts_file"

    refreshed_keys_file="$(mktemp)"

    if [[ -n "$CURL_BIN" ]]; then
        if "$CURL_BIN" --silent --show-error --fail \
            -H "Accept: application/vnd.github+json" \
            -H "X-GitHub-Api-Version: $api_version" \
            "https://api.github.com/meta" \
            | "$PHP_BIN" -r '
                $meta = json_decode(stream_get_contents(STDIN), true);
                if (!is_array($meta) || empty($meta["ssh_keys"]) || !is_array($meta["ssh_keys"])) {
                    fwrite(STDERR, "GitHub meta API sin ssh_keys.\n");
                    exit(1);
                }

                foreach ($meta["ssh_keys"] as $key) {
                    echo "github.com {$key}\n";
                }
            ' > "$refreshed_keys_file"; then
            log_ok "Host keys de GitHub obtenidas desde la API oficial"
        else
            rm -f "$refreshed_keys_file"
            refreshed_keys_file="$(mktemp)"
            log_warn "No se pudo consultar la API oficial de GitHub. Se usara ssh-keyscan como fallback."
        fi
    else
        log_warn "curl no esta disponible. Se usara ssh-keyscan para refrescar known_hosts."
    fi

    if [[ ! -s "$refreshed_keys_file" ]]; then
        "$SSH_KEYSCAN_BIN" github.com 2>/dev/null > "$refreshed_keys_file"
        [[ -s "$refreshed_keys_file" ]] || fail "No se pudo obtener ninguna host key publica para github.com"
        log_ok "Host keys de GitHub obtenidas con ssh-keyscan"
    fi

    "$SSH_KEYGEN_BIN" -R "github.com" -f "$known_hosts_file" >/dev/null 2>&1 || true
    "$SSH_KEYGEN_BIN" -R "[github.com]:22" -f "$known_hosts_file" >/dev/null 2>&1 || true
    cat "$refreshed_keys_file" >> "$known_hosts_file"
    chown "$APP_USER:$APP_USER" "$known_hosts_file"
    chmod 644 "$known_hosts_file"
    rm -f "$refreshed_keys_file"

    log_ok "Entradas de github.com actualizadas en known_hosts"
}

read_env_value() {
    local key="$1"
    local raw

    raw="$(grep -E "^${key}=" "$APP_DIR/.env" | head -n 1 | cut -d= -f2- || true)"
    raw="${raw%\"}"
    raw="${raw#\"}"
    raw="${raw%\'}"
    raw="${raw#\'}"
    printf '%s' "$raw"
}

set_laravel_writable_permissions() {
    mkdir -p "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

    chown -R "$APP_USER:$APP_USER" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

    find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type d -exec chmod 775 {} \;
    find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type f ! -name ".gitignore" -exec chmod 664 {} \;
    find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type f -name ".gitignore" -exec chmod 644 {} \;
}

run_as_app() {
    sudo -u "$APP_USER" env \
        HOME="$APP_HOME" \
        XDG_CONFIG_HOME="$APP_HOME/.config" \
        PATH="$SYSTEM_PATH" \
        bash -c "cd \"$APP_DIR\" && $1"
}

configure_scheduler() {
    local cron_tmp
    local schedule_list

    SCHEDULER_CRON_FILE="/etc/cron.d/portal-distribuidores-${DEPLOY_ENV_NAME}"
    SCHEDULER_LOG_FILE="/var/log/laravel/scheduler-${DEPLOY_ENV_NAME}.log"

    systemctl cat cron.service >/dev/null 2>&1 \
        || fail "No se encontro el servicio cron requerido por el scheduler de Laravel"

    systemctl enable --now cron.service
    systemctl is-active --quiet cron.service \
        || fail "El servicio cron no quedo activo"

    mkdir -p /var/log/laravel
    touch "$SCHEDULER_LOG_FILE"
    chown "$APP_USER:$APP_USER" "$SCHEDULER_LOG_FILE"
    chmod 664 "$SCHEDULER_LOG_FILE"

    cron_tmp="$(mktemp)"
    {
        printf 'SHELL=/bin/bash\n'
        printf 'PATH=%s\n' "$SYSTEM_PATH"
        printf '* * * * * %s cd %s && %s artisan schedule:run >> %s 2>&1\n' \
            "$APP_USER" "$APP_DIR" "$PHP_BIN" "$SCHEDULER_LOG_FILE"
    } > "$cron_tmp"

    chown root:root "$cron_tmp"
    chmod 644 "$cron_tmp"
    mv "$cron_tmp" "$SCHEDULER_CRON_FILE"

    schedule_list="$(run_as_app "\"$PHP_BIN\" artisan schedule:list")"
    echo "$schedule_list"
    grep -Fq 'contapyme-stock-sync' <<< "$schedule_list" \
        || fail "Laravel no registro la tarea contapyme-stock-sync"

    log_ok "Scheduler configurado en $SCHEDULER_CRON_FILE"
    log_ok "Log del scheduler: $SCHEDULER_LOG_FILE"
}

configure_queue_worker() {
    local target_unit="/etc/systemd/system/${QUEUE_SERVICE}.service"
    local override_dir="/etc/systemd/system/${QUEUE_SERVICE}.service.d"
    local override_file="$override_dir/override.conf"
    local effective_exec_start
    local queue_log_name

    require_file "$QUEUE_UNIT_SOURCE"

    if [[ -f "$override_file" ]] && grep -Eq -- '--queue=(production|staging)' "$override_file"; then
        rm -f "$override_file"
        rmdir "$override_dir" 2>/dev/null || true
        log_warn "Se elimino el override temporal de Redis para $QUEUE_SERVICE"
    fi

    if [[ "$DEPLOY_ENV_NAME" == "production" ]] && systemctl cat laravel-queue-prod.service >/dev/null 2>&1; then
        systemctl disable --now laravel-queue-prod.service >/dev/null 2>&1 || true
        log_warn "Se deshabilito la unidad obsoleta laravel-queue-prod.service"
    fi

    queue_log_name="queue-staging"
    [[ "$DEPLOY_ENV_NAME" == "production" ]] && queue_log_name="queue-prod"

    mkdir -p /var/log/laravel
    touch "/var/log/laravel/${queue_log_name}.log" "/var/log/laravel/${queue_log_name}-error.log"
    chown "$APP_USER:$APP_USER" "/var/log/laravel/${queue_log_name}.log" "/var/log/laravel/${queue_log_name}-error.log"
    chmod 664 "/var/log/laravel/${queue_log_name}.log" "/var/log/laravel/${queue_log_name}-error.log"

    install -o root -g root -m 0644 "$QUEUE_UNIT_SOURCE" "$target_unit"
    systemctl daemon-reload
    systemctl enable "$QUEUE_SERVICE.service" >/dev/null

    effective_exec_start="$(systemctl show "$QUEUE_SERVICE.service" --property=ExecStart --value)"
    grep -Fq -- '--queue=default' <<< "$effective_exec_start" \
        || fail "$QUEUE_SERVICE no escucha --queue=default. Revisa overrides en $override_dir"
    grep -Fq -- '--timeout=600' <<< "$effective_exec_start" \
        || fail "$QUEUE_SERVICE no usa --timeout=600"

    log_ok "Unidad $QUEUE_SERVICE instalada y validada"
}

ensure_app_up() {
    if [[ "$MAINTENANCE_ACTIVE" == "true" ]]; then
        log_warn "Intentando levantar la aplicacion tras un error..."
        run_as_app "\"$PHP_BIN\" artisan up" >/dev/null 2>&1 || true
        MAINTENANCE_ACTIVE=false
    fi
}

on_error() {
    local exit_code="$1"
    local line_no="$2"
    log_error "Deploy abortado en la linea ${line_no} (exit code ${exit_code})"
    ensure_app_up
    exit "$exit_code"
}
trap 'on_error $? $LINENO' ERR

load_deploy_config() {
    APP_ENV_VALUE="$(read_env_value APP_ENV)"
    APP_DEBUG_VALUE="$(read_env_value APP_DEBUG)"
    QUEUE_CONNECTION_VALUE="$(read_env_value QUEUE_CONNECTION)"
    CACHE_STORE_VALUE="$(read_env_value CACHE_STORE)"
    DB_QUEUE_VALUE="$(read_env_value DB_QUEUE)"
    DB_QUEUE_RETRY_AFTER_VALUE="$(read_env_value DB_QUEUE_RETRY_AFTER)"
    DB_HOST_VALUE="$(read_env_value DB_HOST)"
    DB_PORT_VALUE="$(read_env_value DB_PORT)"
    DB_DATABASE_VALUE="$(read_env_value DB_DATABASE)"
    DB_USERNAME_VALUE="$(read_env_value DB_USERNAME)"
    DB_PASSWORD_VALUE="$(read_env_value DB_PASSWORD)"
    APP_URL_VALUE="$(read_env_value APP_URL)"
    SESSION_SECURE_COOKIE_VALUE="$(read_env_value SESSION_SECURE_COOKIE)"
    DB_SSLMODE_VALUE="$(read_env_value DB_SSLMODE)"
    PRIVATE_DISK_DRIVER_VALUE="$(read_env_value PRIVATE_DISK_DRIVER)"
    PRIVATE_BUCKET_VALUE="$(read_env_value PRIVATE_BUCKET)"
    ORDER_PDFS_DISK_VALUE="$(read_env_value ORDER_PDFS_DISK)"
    TECH_SHEETS_DISK_VALUE="$(read_env_value TECH_SHEETS_DISK)"

    DEPLOY_ENV_NAME="$(read_env_value DEPLOY_ENV_NAME)"
    QUEUE_SERVICE="$(read_env_value DEPLOY_QUEUE_SERVICE)"
    PHP_FPM_SERVICE="$(read_env_value DEPLOY_PHP_FPM_SERVICE)"
    DEPLOY_CREATE_DB_BACKUP="$(read_env_value DEPLOY_CREATE_DB_BACKUP)"
    DEPLOY_DB_BACKUP_DIR="$(read_env_value DEPLOY_DB_BACKUP_DIR)"

    [[ -n "$APP_ENV_VALUE" ]] || fail "APP_ENV no esta definido en .env"
    [[ -n "$APP_URL_VALUE" ]] || fail "APP_URL no esta definido en .env"
    [[ -n "$DB_DATABASE_VALUE" ]] || fail "DB_DATABASE no esta definido en .env"
    [[ -n "$DB_USERNAME_VALUE" ]] || fail "DB_USERNAME no esta definido en .env"

    DB_HOST_VALUE="${DB_HOST_VALUE:-127.0.0.1}"
    DB_PORT_VALUE="${DB_PORT_VALUE:-5432}"
    QUEUE_CONNECTION_VALUE="${QUEUE_CONNECTION_VALUE:-database}"
    CACHE_STORE_VALUE="${CACHE_STORE_VALUE:-database}"
    DB_QUEUE_VALUE="${DB_QUEUE_VALUE:-default}"
    DB_QUEUE_RETRY_AFTER_VALUE="${DB_QUEUE_RETRY_AFTER_VALUE:-660}"
    DEPLOY_ENV_NAME="${DEPLOY_ENV_NAME:-$APP_ENV_VALUE}"
    PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-$DEFAULT_PHP_FPM_SERVICE}"

    case "$DEPLOY_ENV_NAME" in
        production)
            [[ "$APP_ENV_VALUE" == "production" ]] || fail "DEPLOY_ENV_NAME=production exige APP_ENV=production. Valor actual: $APP_ENV_VALUE"
            [[ "$DB_DATABASE_VALUE" == "$EXPECTED_PRODUCTION_DB" ]] || fail "Produccion debe apuntar a DB_DATABASE=$EXPECTED_PRODUCTION_DB. Valor actual: $DB_DATABASE_VALUE"
            QUEUE_SERVICE="${QUEUE_SERVICE:-laravel-queue}"
            [[ "$QUEUE_SERVICE" == "laravel-queue" ]] || fail "Produccion exige DEPLOY_QUEUE_SERVICE=laravel-queue. Valor actual: $QUEUE_SERVICE"
            QUEUE_UNIT_SOURCE="$APP_DIR/deploy/laravel-queue.service"
            DEPLOY_CREATE_DB_BACKUP="${DEPLOY_CREATE_DB_BACKUP:-true}"
            ;;
        staging)
            [[ "$APP_ENV_VALUE" == "staging" ]] || fail "DEPLOY_ENV_NAME=staging exige APP_ENV=staging. Valor actual: $APP_ENV_VALUE"
            [[ "$DB_DATABASE_VALUE" == "$EXPECTED_STAGING_DB" ]] || fail "Staging debe apuntar a DB_DATABASE=$EXPECTED_STAGING_DB. Valor actual: $DB_DATABASE_VALUE"
            QUEUE_SERVICE="${QUEUE_SERVICE:-laravel-queue-staging}"
            [[ "$QUEUE_SERVICE" == "laravel-queue-staging" ]] || fail "Staging exige DEPLOY_QUEUE_SERVICE=laravel-queue-staging. Valor actual: $QUEUE_SERVICE"
            QUEUE_UNIT_SOURCE="$APP_DIR/deploy/laravel-queue-staging.service"
            DEPLOY_CREATE_DB_BACKUP="${DEPLOY_CREATE_DB_BACKUP:-false}"
            ;;
        *)
            fail "DEPLOY_ENV_NAME debe ser 'production' o 'staging'. Valor actual: $DEPLOY_ENV_NAME"
            ;;
    esac

    [[ "$QUEUE_CONNECTION_VALUE" == "database" ]] || fail "QUEUE_CONNECTION debe ser database en $DEPLOY_ENV_NAME. Valor actual: $QUEUE_CONNECTION_VALUE"
    [[ "$CACHE_STORE_VALUE" == "database" ]] || fail "CACHE_STORE debe ser database en $DEPLOY_ENV_NAME. Valor actual: $CACHE_STORE_VALUE"
    [[ "$DB_QUEUE_VALUE" == "default" ]] || fail "DB_QUEUE debe ser default en $DEPLOY_ENV_NAME. Valor actual: $DB_QUEUE_VALUE"
    [[ "$DB_QUEUE_RETRY_AFTER_VALUE" =~ ^[0-9]+$ ]] || fail "DB_QUEUE_RETRY_AFTER debe ser numerico"
    (( DB_QUEUE_RETRY_AFTER_VALUE > 600 )) || fail "DB_QUEUE_RETRY_AFTER debe ser mayor que el timeout del worker (600)"

    [[ "${APP_DEBUG_VALUE,,}" != "true" ]] || fail "APP_DEBUG no puede estar habilitado en ${DEPLOY_ENV_NAME}."
    bool_true "${SESSION_SECURE_COOKIE_VALUE:-false}" || fail "SESSION_SECURE_COOKIE debe ser true en ${DEPLOY_ENV_NAME}. Corrige el .env del servidor antes de desplegar."

    local app_url_host
    app_url_host="$(extract_url_host "$APP_URL_VALUE")"
    if is_non_canonical_host "$app_url_host"; then
        fail "APP_URL debe usar un host canonico en ${DEPLOY_ENV_NAME}. Valor actual: $APP_URL_VALUE"
    fi

    case "${DB_SSLMODE_VALUE,,}" in
        require|verify-ca|verify-full) ;;
        *)
            fail "DB_SSLMODE debe ser require, verify-ca o verify-full en ${DEPLOY_ENV_NAME}. Valor actual: ${DB_SSLMODE_VALUE:-vacio}"
            ;;
    esac

    [[ "${ORDER_PDFS_DISK_VALUE:-private}" != "public" ]] || fail "ORDER_PDFS_DISK no puede apuntar a public."
    [[ "${TECH_SHEETS_DISK_VALUE:-private}" != "public" ]] || fail "TECH_SHEETS_DISK no puede apuntar a public."

    if [[ "${PRIVATE_DISK_DRIVER_VALUE,,}" == "s3" && -z "$PRIVATE_BUCKET_VALUE" ]]; then
        fail "PRIVATE_DISK_DRIVER=s3 exige PRIVATE_BUCKET en .env."
    fi

    if bool_true "$DEPLOY_CREATE_DB_BACKUP"; then
        PG_DUMP_BIN="$(resolve_cmd pg_dump)"
        DEPLOY_DB_BACKUP_DIR="${DEPLOY_DB_BACKUP_DIR:-/var/backups/portal-distribuidores/${DEPLOY_ENV_NAME}}"
    fi
}

backup_database() {
    if ! bool_true "$DEPLOY_CREATE_DB_BACKUP"; then
        log_warn "Backup de base de datos omitido para este entorno."
        return
    fi

    mkdir -p "$DEPLOY_DB_BACKUP_DIR"
    chown "$APP_USER:$APP_USER" "$DEPLOY_DB_BACKUP_DIR" || true

    BACKUP_FILE="$DEPLOY_DB_BACKUP_DIR/${DB_DATABASE_VALUE}-$(date '+%Y%m%d-%H%M%S').dump"

    log_step "Creando backup de PostgreSQL antes de migrar..."

    run_as_app "PGPASSWORD=$(shell_escape "$DB_PASSWORD_VALUE") $(shell_escape "$PG_DUMP_BIN") --host=$(shell_escape "$DB_HOST_VALUE") --port=$(shell_escape "$DB_PORT_VALUE") --username=$(shell_escape "$DB_USERNAME_VALUE") --format=custom --file=$(shell_escape "$BACKUP_FILE") $(shell_escape "$DB_DATABASE_VALUE")"

    [[ -s "$BACKUP_FILE" ]] || fail "No se pudo crear el backup de la base de datos."

    chmod 640 "$BACKUP_FILE" || true
    chown "$APP_USER:$APP_USER" "$BACKUP_FILE" || true

    log_ok "Backup creado en $BACKUP_FILE"
}

echo -e "\n${CYAN}============================================"
echo "  Portal Distribuidores - Deploy Script"
echo -e "============================================${NC}"
echo "  Directorio: $APP_DIR"
echo "  Fecha:      $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

[[ "${EUID}" -eq 0 ]] || fail "Este script debe ejecutarse con sudo o como root"
[[ "$#" -eq 0 ]] || fail "Este script no acepta argumentos."

require_file "$APP_DIR/.env"
require_file "$APP_DIR/artisan"
require_git_repo

PHP_BIN="$(resolve_cmd php)"
COMPOSER_BIN="$(resolve_cmd composer)"
GIT_BIN="$(resolve_cmd git)"
NPM_BIN="$(resolve_cmd npm)"
NODE_BIN="$(resolve_cmd node)"
SSH_KEYSCAN_BIN="$(resolve_cmd ssh-keyscan)"
SSH_KEYGEN_BIN="$(resolve_cmd ssh-keygen)"
CURL_BIN="$(command -v curl 2>/dev/null || true)"

APP_HOME="$(getent passwd "$APP_USER" | cut -d: -f6 || true)"
[[ -n "$APP_HOME" ]] || fail "No se pudo determinar el HOME del usuario '$APP_USER'"

load_deploy_config

log_step "Verificando entorno objetivo..."
log_ok "Entorno de deploy: $DEPLOY_ENV_NAME"
log_ok "APP_ENV: $APP_ENV_VALUE"
log_ok "Base de datos: $DB_DATABASE_VALUE"
log_ok "Worker configurado: $QUEUE_SERVICE"
log_ok "PHP-FPM configurado: $PHP_FPM_SERVICE"

log_step "Preparando HOME del usuario de aplicacion..."
mkdir -p "$APP_HOME/.config"
chown "$APP_USER:$APP_USER" "$APP_HOME"
chown -R "$APP_USER:$APP_USER" "$APP_HOME/.config"
chmod 755 "$APP_HOME"
chmod 755 "$APP_HOME/.config"
log_ok "HOME listo para $APP_USER"

log_step "Preparando permisos iniciales..."
set_laravel_writable_permissions
chown "$APP_USER:$APP_USER" "$APP_DIR/.env"
chmod 640 "$APP_DIR/.env"
log_ok "Permisos iniciales listos"

log_step "Verificando remoto Git..."
chown -R "$APP_USER:$APP_USER" "$APP_DIR"
set_laravel_writable_permissions
chown "$APP_USER:$APP_USER" "$APP_DIR/.env"
chmod 640 "$APP_DIR/.env"

REMOTE_URL="$(run_as_app "\"$GIT_BIN\" remote get-url origin" 2>/dev/null || true)"
[[ -n "$REMOTE_URL" ]] || fail "No se pudo leer el remoto 'origin'"
echo "  origin: $REMOTE_URL"

if [[ "$REMOTE_URL" == https://github.com/* ]]; then
    log_warn "origin usa HTTPS. Se recomienda SSH para evitar prompts en deploy."
fi

if [[ "$REMOTE_URL" == git@github.com:* || "$REMOTE_URL" == ssh://git@github.com/* ]]; then
    log_step "Preparando SSH para GitHub..."
    mkdir -p "$APP_HOME/.ssh"
    touch "$APP_HOME/.ssh/known_hosts"
    chown -R "$APP_USER:$APP_USER" "$APP_HOME/.ssh"
    chmod 700 "$APP_HOME/.ssh"
    chmod 644 "$APP_HOME/.ssh/known_hosts"

    if [[ -f "$APP_HOME/.ssh/id_ed25519" ]]; then
        chmod 600 "$APP_HOME/.ssh/id_ed25519"
    fi
    if [[ -f "$APP_HOME/.ssh/id_rsa" ]]; then
        chmod 600 "$APP_HOME/.ssh/id_rsa"
    fi

    if [[ ! -f "$APP_HOME/.ssh/id_ed25519" && ! -f "$APP_HOME/.ssh/id_rsa" ]]; then
        fail "El remoto usa SSH pero no existe una llave privada en $APP_HOME/.ssh para $APP_USER"
    fi

    refresh_github_known_hosts
    log_ok "SSH listo para GitHub"
fi

log_step "Validando estado local del repositorio..."
GIT_STATUS="$(run_as_app "\"$GIT_BIN\" status --porcelain --untracked-files=no" || true)"
if [[ -n "$GIT_STATUS" ]]; then
    echo "$GIT_STATUS"
    fail "El repositorio tiene cambios locales en archivos versionados. Haz commit, stash o restore antes del deploy."
fi
log_ok "Working tree limpio"

log_step "Resolviendo rama actual..."
BRANCH="$DEPLOY_BRANCH_VALUE"
if [[ -n "$BRANCH" ]]; then
    log_ok "Rama solicitada por workflow: $BRANCH"
else
    BRANCH="$(run_as_app "\"$GIT_BIN\" rev-parse --abbrev-ref HEAD" 2>/dev/null || true)"
    if [[ -z "$BRANCH" || "$BRANCH" == "HEAD" ]]; then
        BRANCH="$(run_as_app "\"$GIT_BIN\" symbolic-ref --short refs/remotes/origin/HEAD | sed 's@^origin/@@'" 2>/dev/null || true)"
    fi
fi
[[ -n "$BRANCH" ]] || fail "No se pudo determinar la rama actual del repositorio"

case "$DEPLOY_ENV_NAME" in
    production)
        [[ "$BRANCH" == "master" ]] || fail "Produccion solo puede desplegar desde la rama master. Rama actual: $BRANCH"
        ;;
    staging)
        [[ "$BRANCH" == "develop" ]] || fail "Staging solo puede desplegar desde la rama develop. Rama actual: $BRANCH"
        ;;
esac
log_ok "Rama valida para $DEPLOY_ENV_NAME: $BRANCH"

if [[ -n "$DEPLOY_TARGET_SHA_VALUE" ]]; then
    if [[ ! "$DEPLOY_TARGET_SHA_VALUE" =~ ^[0-9a-fA-F]{7,40}$ ]]; then
        fail "DEPLOY_TARGET_SHA debe ser un SHA Git hexadecimal de 7 a 40 caracteres. Valor recibido: $DEPLOY_TARGET_SHA_VALUE"
    fi
    log_ok "Commit objetivo recibido: $DEPLOY_TARGET_SHA_VALUE"
fi

if [[ ! -f "$APP_DIR/vendor/autoload.php" ]]; then
    log_step "Bootstrap inicial: instalando dependencias PHP antes del primer artisan..."
    run_as_app "\"$COMPOSER_BIN\" install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-progress"
    BOOTSTRAP_COMPOSER_DONE=true
    log_ok "Bootstrap inicial de Composer completado"
fi

log_step "Activando modo mantenimiento antes de actualizar codigo..."
run_as_app "\"$PHP_BIN\" artisan down --render=\"errors::503\" --retry=60"
MAINTENANCE_ACTIVE=true
log_ok "Aplicacion en mantenimiento"

log_step "Actualizando codigo fuente..."
run_as_app "\"$GIT_BIN\" fetch --prune origin"
if [[ -n "$DEPLOY_TARGET_SHA_VALUE" ]]; then
    TARGET_COMMIT="$(run_as_app "target=$(shell_escape "$DEPLOY_TARGET_SHA_VALUE"); \"$GIT_BIN\" rev-parse \"\${target}^{commit}\"")"
    run_as_app "target=$(shell_escape "$TARGET_COMMIT"); \"$GIT_BIN\" merge-base --is-ancestor \"\$target\" origin/\"$BRANCH\"" \
        || fail "El commit objetivo $TARGET_COMMIT no pertenece a origin/$BRANCH. Se aborta para no desplegar un commit incorrecto."
    run_as_app "target=$(shell_escape "$TARGET_COMMIT"); \"$GIT_BIN\" reset --hard \"\$target\""
else
    run_as_app "\"$GIT_BIN\" reset --hard origin/\"$BRANCH\""
fi
COMMIT="$(run_as_app "\"$GIT_BIN\" rev-parse --short HEAD")"
COMMIT_FULL="$(run_as_app "\"$GIT_BIN\" rev-parse HEAD")"
if [[ -n "${TARGET_COMMIT:-}" && "$COMMIT_FULL" != "$TARGET_COMMIT" ]]; then
    fail "El VPS quedo en $COMMIT_FULL, pero el deploy esperaba $TARGET_COMMIT."
fi
log_ok "Codigo actualizado al commit $COMMIT"

log_step "Instalando dependencias PHP..."
if [[ "$BOOTSTRAP_COMPOSER_DONE" == "true" ]]; then
    log_warn "Composer ya fue ejecutado durante el bootstrap inicial. Se ejecutara nuevamente sobre el codigo actualizado."
fi
run_as_app "\"$COMPOSER_BIN\" install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-progress"
log_ok "Dependencias PHP instaladas"

backup_database

log_step "Limpiando caches antes de migrar..."
run_as_app "\"$PHP_BIN\" artisan config:clear"
run_as_app "\"$PHP_BIN\" artisan route:clear"
run_as_app "\"$PHP_BIN\" artisan view:clear"
run_as_app "\"$PHP_BIN\" artisan event:clear"
log_ok "Caches limpiados"

log_step "Ejecutando migraciones no destructivas..."
run_as_app "\"$PHP_BIN\" artisan migrate --force"
log_ok "Migraciones completadas"

log_step "Migrando media protegida fuera del disco public..."
run_as_app "\"$PHP_BIN\" artisan protected-media:migrate --no-interaction"
log_ok "Media protegida migrada"

if [[ "$(read_env_value SCRIBE_ENABLED)" == "true" ]]; then
    log_step "Generando documentacion Scribe..."
    run_as_app "\"$PHP_BIN\" artisan scribe:generate --no-interaction"
    log_ok "Documentacion Scribe generada"
fi

log_step "Regenerando caches de Laravel..."
run_as_app "\"$PHP_BIN\" artisan config:cache"
run_as_app "\"$PHP_BIN\" artisan route:cache"
run_as_app "\"$PHP_BIN\" artisan view:cache"
run_as_app "\"$PHP_BIN\" artisan event:cache"
log_ok "Caches regenerados"

if [[ -f "$APP_DIR/package.json" ]]; then
    log_step "Instalando dependencias Node.js..."
    if [[ -f "$APP_DIR/package-lock.json" ]]; then
        run_as_app "\"$NPM_BIN\" ci"
        log_ok "Dependencias instaladas con npm ci"
    else
        run_as_app "\"$NPM_BIN\" install"
        log_ok "Dependencias instaladas con npm install"
    fi

    log_step "Compilando assets frontend..."
    run_as_app "NODE_ENV=production \"$NPM_BIN\" run build"
    [[ -f "$APP_DIR/public/build/manifest.json" ]] || fail "El build termino pero no se encontro public/build/manifest.json"
    log_ok "Assets compilados"
else
    log_warn "No se encontro package.json. Se omite el build frontend."
fi

log_step "Verificando storage link..."
run_as_app "\"$PHP_BIN\" artisan storage:link --force"
log_ok "Storage link verificado"

log_step "Ajustando permisos finales..."
chown -R "$APP_USER:$APP_USER" "$APP_DIR"
set_laravel_writable_permissions
chown "$APP_USER:$APP_USER" "$APP_DIR/.env"
chmod 640 "$APP_DIR/.env"
log_ok "Permisos finales ajustados"

log_step "Configurando scheduler de Laravel..."
configure_scheduler

log_step "Configurando worker PostgreSQL..."
configure_queue_worker

log_step "Reiniciando worker de colas..."
run_as_app "\"$PHP_BIN\" artisan queue:restart" || true
sleep 2
systemctl restart "$QUEUE_SERVICE.service"
systemctl is-active --quiet "$QUEUE_SERVICE.service" \
    || fail "El servicio $QUEUE_SERVICE no quedo activo"
log_ok "Servicio $QUEUE_SERVICE reiniciado y activo"

log_step "Recargando PHP-FPM..."
if systemctl is-active --quiet "$PHP_FPM_SERVICE"; then
    systemctl reload "$PHP_FPM_SERVICE"
    log_ok "$PHP_FPM_SERVICE recargado"
else
    log_warn "$PHP_FPM_SERVICE no esta activo. Verifica con: systemctl status $PHP_FPM_SERVICE"
fi

log_step "Levantando aplicacion..."
run_as_app "\"$PHP_BIN\" artisan up"
MAINTENANCE_ACTIVE=false
log_ok "Aplicacion en linea"

echo -e "\n${GREEN}============================================"
echo "  Deploy completado exitosamente"
echo "  Entorno: $DEPLOY_ENV_NAME"
echo "  Rama:    $BRANCH"
echo "  Commit:  $COMMIT"
if [[ -n "$BACKUP_FILE" ]]; then
    echo "  Backup:  $BACKUP_FILE"
fi
echo -e "============================================${NC}\n"

echo "  Proximos pasos:"
echo "   - Verificar logs:      tail -f storage/logs/laravel.log"
echo "   - Verificar scheduler: systemctl status cron && tail -f $SCHEDULER_LOG_FILE"
echo "   - Verificar worker:    systemctl status $QUEUE_SERVICE"
echo "   - Verificar aplicacion: curl -I $(read_env_value APP_URL)"
