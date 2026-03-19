#!/usr/bin/env bash
# =============================================================================
# deploy.sh — Portal Distribuidores
# Despliegue en Hostinger VPS · Ubuntu 24.04 · Sin Docker
#
# Uso:
#   sudo bash deploy.sh
#
# Requisito: ejecutar desde el directorio raíz del proyecto.
# El código, Composer, Git, Artisan y npm se ejecutan como www-data.
# =============================================================================

set -euo pipefail

# ── Colores ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

# ── Variables ────────────────────────────────────────────────────────────────
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_USER="www-data"

PHP_BIN="/usr/bin/php"
COMPOSER_BIN="/usr/local/bin/composer"
GIT_BIN="/usr/bin/git"
NPM_BIN="/usr/bin/npm"

QUEUE_SERVICE="laravel-queue"
PHP_FPM_SERVICE="php8.3-fpm"

# ── Funciones de log ─────────────────────────────────────────────────────────
log_step()  { echo -e "\n${CYAN}▶ $1${NC}"; }
log_ok()    { echo -e "${GREEN}  ✓ $1${NC}"; }
log_warn()  { echo -e "${YELLOW}  ⚠ $1${NC}"; }
log_error() { echo -e "${RED}  ✗ $1${NC}"; }

fail() {
    log_error "$1"
    exit 1
}

run_as_app() {
    sudo -H -u "$APP_USER" bash -lc "cd \"$APP_DIR\" && $1"
}

require_file() {
    local file="$1"
    [[ -f "$file" ]] || fail "No se encontró $(basename "$file") en $APP_DIR"
}

require_cmd() {
    local name="$1"
    local path="$2"

    if [[ -n "$path" && -x "$path" ]]; then
        return 0
    fi

    command -v "$name" >/dev/null 2>&1 || fail "No se encontró $name"
}

# ── Inicio ───────────────────────────────────────────────────────────────────
echo -e "\n${CYAN}============================================"
echo "  Portal Distribuidores — Deploy Script"
echo -e "============================================${NC}"
echo "  Directorio: $APP_DIR"
echo "  Usuario app: $APP_USER"
echo "  Fecha: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

[[ "${EUID}" -eq 0 ]] || fail "Este script debe ejecutarse con sudo o como root"

# ── Verificaciones previas ───────────────────────────────────────────────────
log_step "Verificando requisitos..."

require_file "$APP_DIR/.env"
require_file "$APP_DIR/artisan"

require_cmd "php" "$PHP_BIN"
require_cmd "composer" "$COMPOSER_BIN"
require_cmd "git" "$GIT_BIN"
require_cmd "npm" "$NPM_BIN"

PHP_VERSION="$("$PHP_BIN" -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")"
log_ok "PHP $PHP_VERSION encontrado"
log_ok "Composer encontrado"
log_ok "Git encontrado"
log_ok "Node.js $(node --version) / npm $(npm --version) encontrados"

# ── Alinear permisos base del proyecto ───────────────────────────────────────
log_step "Alineando propietario y permisos base del proyecto..."

chown -R "$APP_USER":"$APP_USER" "$APP_DIR"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

if [[ -f "$APP_DIR/.env" ]]; then
    chown "$APP_USER":"$APP_USER" "$APP_DIR/.env"
    chmod 640 "$APP_DIR/.env"
fi

log_ok "Propietario del proyecto alineado con $APP_USER"

# ── Verificar remoto Git ─────────────────────────────────────────────────────
log_step "Verificando remoto Git..."

REMOTE_URL="$(run_as_app "\"$GIT_BIN\" remote get-url origin" 2>/dev/null || true)"
if [[ -z "$REMOTE_URL" ]]; then
    fail "No se pudo leer el remoto 'origin'"
fi

echo "  origin: $REMOTE_URL"

if [[ "$REMOTE_URL" == https://github.com/* ]]; then
    log_warn "origin está usando HTTPS. Se recomienda SSH para evitar pedir credenciales en cada deploy."
fi

# ── 1. Git pull ──────────────────────────────────────────────────────────────
log_step "Actualizando código fuente (git pull)..."

BRANCH="$(run_as_app "\"$GIT_BIN\" rev-parse --abbrev-ref HEAD" 2>/dev/null || true)"

if [[ -z "$BRANCH" || "$BRANCH" == "HEAD" ]]; then
    BRANCH="$(run_as_app "\"$GIT_BIN\" symbolic-ref --short refs/remotes/origin/HEAD | sed 's@^origin/@@'" 2>/dev/null || true)"
fi

[[ -n "$BRANCH" ]] || fail "No se pudo determinar la rama actual del repositorio"

log_warn "Rama actual: $BRANCH"

run_as_app "\"$GIT_BIN\" fetch origin"
run_as_app "\"$GIT_BIN\" pull --ff-only origin \"$BRANCH\""

COMMIT="$(run_as_app "\"$GIT_BIN\" rev-parse --short HEAD")"
log_ok "Código actualizado — commit: $COMMIT"

# ── 2. Dependencias PHP ──────────────────────────────────────────────────────
log_step "Instalando dependencias PHP (composer install --no-dev)..."

run_as_app "\"$COMPOSER_BIN\" install --no-dev --optimize-autoloader --no-interaction --prefer-dist"
log_ok "Dependencias PHP instaladas"

# ── 3. Migraciones ───────────────────────────────────────────────────────────
log_step "Ejecutando migraciones de base de datos..."

run_as_app "\"$PHP_BIN\" artisan migrate --force"
log_ok "Migraciones completadas"

# ── 4. Limpiar y regenerar cachés ────────────────────────────────────────────
log_step "Regenerando caché de configuración, rutas, vistas y eventos..."

run_as_app "\"$PHP_BIN\" artisan config:clear"
run_as_app "\"$PHP_BIN\" artisan route:clear"
run_as_app "\"$PHP_BIN\" artisan view:clear"
run_as_app "\"$PHP_BIN\" artisan event:clear"

run_as_app "\"$PHP_BIN\" artisan config:cache"
run_as_app "\"$PHP_BIN\" artisan route:cache"
run_as_app "\"$PHP_BIN\" artisan view:cache"
run_as_app "\"$PHP_BIN\" artisan event:cache"

log_ok "Cachés regenerados"

# ── 5. Build de assets (Vite) ────────────────────────────────────────────────
if [[ -f "$APP_DIR/package.json" ]]; then
    log_step "Instalando dependencias Node.js..."

    if [[ -f "$APP_DIR/package-lock.json" ]]; then
        run_as_app "\"$NPM_BIN\" ci"
        log_ok "Dependencias instaladas con npm ci"
    else
        run_as_app "\"$NPM_BIN\" install"
        log_ok "Dependencias instaladas con npm install"
    fi

    log_step "Compilando assets frontend (Vite)..."
    run_as_app "\"$NPM_BIN\" run build"
    log_ok "Assets compilados en public/build/"
else
    log_warn "No se encontró package.json. Se omite el build frontend."
fi

# ── 6. Ajuste final de permisos ──────────────────────────────────────────────
log_step "Ajustando permisos finales..."

chown -R "$APP_USER":"$APP_USER" "$APP_DIR"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

if [[ -f "$APP_DIR/.env" ]]; then
    chown "$APP_USER":"$APP_USER" "$APP_DIR/.env"
    chmod 640 "$APP_DIR/.env"
fi

log_ok "Permisos finales ajustados"

# ── 7. Reiniciar worker de colas ─────────────────────────────────────────────
log_step "Señalizando reinicio del worker de colas..."

run_as_app "\"$PHP_BIN\" artisan queue:restart" 2>/dev/null || true

if systemctl cat "$QUEUE_SERVICE" >/dev/null 2>&1; then
    sleep 2
    systemctl restart "$QUEUE_SERVICE"
    log_ok "Servicio $QUEUE_SERVICE reiniciado"
elif command -v supervisorctl >/dev/null 2>&1 && supervisorctl status "laravel-queue:*" >/dev/null 2>&1; then
    supervisorctl restart "laravel-queue:*"
    log_ok "Supervisor laravel-queue reiniciado"
else
    log_warn "No se encontró un servicio de colas administrado por systemd o Supervisor"
    log_warn "  Revisa con: systemctl status $QUEUE_SERVICE"
fi

# ── 8. Recargar PHP-FPM ──────────────────────────────────────────────────────
log_step "Recargando PHP-FPM..."

if systemctl is-active --quiet "$PHP_FPM_SERVICE"; then
    systemctl reload "$PHP_FPM_SERVICE"
    log_ok "$PHP_FPM_SERVICE recargado"
else
    log_warn "$PHP_FPM_SERVICE no está activo. Verifica con: systemctl status $PHP_FPM_SERVICE"
fi

# ── Resumen final ────────────────────────────────────────────────────────────
echo -e "\n${GREEN}============================================"
echo "  ✓ Despliegue completado exitosamente"
echo "  Commit: $COMMIT"
echo "  Rama:   $BRANCH"
echo "  Hora:   $(date '+%H:%M:%S')"
echo -e "============================================${NC}\n"

echo "  Próximos pasos:"
echo "   → Verificar logs:  tail -f storage/logs/laravel.log"
echo "   → Estado worker:   systemctl status $QUEUE_SERVICE"
echo "   → Test app:        curl -I https://tu-dominio.com"
echo ""