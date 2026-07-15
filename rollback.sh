#!/usr/bin/env bash
# =============================================================================
# rollback.sh - Portal Distribuidores
# Revierte el último despliegue al commit anterior de Git.
#
# Uso (como root o con sudo):
#   sudo bash rollback.sh
#
# El script:
#   1. Identifica el commit anterior al HEAD actual.
#   2. Hace git reset --hard a ese commit.
#   3. Reinstala dependencias del commit anterior.
#   4. Reconstruye assets de frontend.
#   5. Revierte la última migración de base de datos (si hay).
#   6. Reinicia PHP-FPM y la cola de trabajos.
#   7. Limpia cachés de Laravel.
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

log_step()  { echo -e "\n${CYAN}>> $1${NC}"; }
log_ok()    { echo -e "${GREEN}  OK  $1${NC}"; }
log_warn()  { echo -e "${YELLOW}  WARN $1${NC}"; }
log_error() { echo -e "${RED}  ERR  $1${NC}"; }

fail() {
    log_error "$1"
    exit 1
}

run_as_app() {
    sudo -u "$APP_USER" env \
        HOME="$(getent passwd "$APP_USER" | cut -d: -f6)" \
        PATH="$SYSTEM_PATH" \
        "$@"
}

# ── Validaciones previas ──────────────────────────────────────────────────────
[[ "$(id -u)" -eq 0 ]] || fail "rollback.sh debe ejecutarse como root (sudo bash rollback.sh)"
[[ -d "$APP_DIR/.git" || -f "$APP_DIR/.git" ]] || fail "No se encontró .git en $APP_DIR"
[[ -f "$APP_DIR/.env" ]] || fail "No se encontró .env en $APP_DIR"

cd "$APP_DIR"

PHP_BIN="$(command -v php8.3 2>/dev/null || command -v php 2>/dev/null)" || fail "PHP no encontrado"
COMPOSER_BIN="$(command -v composer 2>/dev/null)" || fail "Composer no encontrado"
GIT_BIN="$(command -v git 2>/dev/null)" || fail "Git no encontrado"
NPM_BIN="$(command -v npm 2>/dev/null)" || fail "npm no encontrado"

# ── Identificar commit anterior ───────────────────────────────────────────────
log_step "Identificando commit anterior..."

CURRENT_COMMIT="$("$GIT_BIN" rev-parse HEAD)"
PREVIOUS_COMMIT="$("$GIT_BIN" rev-parse HEAD~1 2>/dev/null || true)"

if [[ -z "$PREVIOUS_COMMIT" ]]; then
    fail "No existe commit anterior al HEAD ($CURRENT_COMMIT). No se puede hacer rollback."
fi

log_ok "HEAD actual:   $CURRENT_COMMIT"
log_ok "Rollback a:    $PREVIOUS_COMMIT"

# ── Mantenimiento ─────────────────────────────────────────────────────────────
log_step "Activando modo mantenimiento..."
run_as_app "$PHP_BIN" artisan down --render="errors::503" --retry=60 || true

# ── Git reset ─────────────────────────────────────────────────────────────────
log_step "Revirtiendo al commit anterior ($PREVIOUS_COMMIT)..."
"$GIT_BIN" reset --hard "$PREVIOUS_COMMIT"
log_ok "Reset completado"

# ── Dependencias PHP ──────────────────────────────────────────────────────────
log_step "Reinstalando dependencias PHP..."
run_as_app "$COMPOSER_BIN" install \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --optimize-autoloader \
    --no-dev
log_ok "Dependencias PHP instaladas"

# ── Assets de frontend ────────────────────────────────────────────────────────
log_step "Reconstruyendo assets de frontend..."
if [[ -f package.json ]]; then
    run_as_app "$NPM_BIN" ci --prefer-offline 2>/dev/null || run_as_app "$NPM_BIN" ci
    run_as_app "$NPM_BIN" run build
    log_ok "Assets reconstruidos"
else
    log_warn "package.json no encontrado. Saltando build de frontend."
fi

# ── Reversión de migraciones ──────────────────────────────────────────────────
log_step "Intentando revertir última migración (si aplica)..."
run_as_app "$PHP_BIN" artisan migrate:rollback --step=1 --force 2>/dev/null \
    && log_ok "Migración revertida" \
    || log_warn "No se pudo revertir la migración (puede que no haya ninguna que revertir)."

# ── Caché de Laravel ──────────────────────────────────────────────────────────
log_step "Limpiando caché de Laravel..."
run_as_app "$PHP_BIN" artisan config:clear
run_as_app "$PHP_BIN" artisan cache:clear
run_as_app "$PHP_BIN" artisan route:clear
run_as_app "$PHP_BIN" artisan view:clear
log_ok "Caché limpiada"

# ── Optimizar ─────────────────────────────────────────────────────────────────
log_step "Optimizando Laravel..."
run_as_app "$PHP_BIN" artisan config:cache
run_as_app "$PHP_BIN" artisan route:cache
run_as_app "$PHP_BIN" artisan view:cache
log_ok "Optimización completada"

# ── Reiniciar servicios ───────────────────────────────────────────────────────
log_step "Reiniciando servicios..."
APP_ENV_VALUE="$(grep -E '^APP_ENV=' "$APP_DIR/.env" | head -n1 | cut -d= -f2- | tr -d '"' || true)"

if [[ "$APP_ENV_VALUE" == "production" ]]; then
    QUEUE_SERVICE="laravel-queue"
    PHP_FPM_SERVICE="php8.3-fpm"
elif [[ "$APP_ENV_VALUE" == "staging" ]]; then
    QUEUE_SERVICE="laravel-queue-staging"
    PHP_FPM_SERVICE="php8.3-fpm"
else
    QUEUE_SERVICE="laravel-queue"
    PHP_FPM_SERVICE="php8.3-fpm"
fi

systemctl restart "$PHP_FPM_SERVICE" 2>/dev/null && log_ok "$PHP_FPM_SERVICE reiniciado" || log_warn "No se pudo reiniciar $PHP_FPM_SERVICE"
systemctl restart "$QUEUE_SERVICE" 2>/dev/null && log_ok "$QUEUE_SERVICE reiniciado" || log_warn "No se pudo reiniciar $QUEUE_SERVICE"

# ── Salir de mantenimiento ────────────────────────────────────────────────────
log_step "Desactivando modo mantenimiento..."
run_as_app "$PHP_BIN" artisan up
log_ok "Aplicación en línea"

echo -e "\n${GREEN}======================================================"
echo -e " ROLLBACK COMPLETADO: $CURRENT_COMMIT → $PREVIOUS_COMMIT"
echo -e "======================================================${NC}\n"
