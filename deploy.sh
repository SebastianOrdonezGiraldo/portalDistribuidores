#!/usr/bin/env bash
# =============================================================================
# deploy.sh — Portal Distribuidores
# Despliegue en Hostinger VPS · Ubuntu 24.04 · Sin Docker
#
# Uso:
#   sudo bash deploy.sh
#
# Requisito: ejecutar desde el directorio raíz del proyecto, como root o
# usuario con sudo. El script opera sobre los archivos como www-data.
# =============================================================================

set -euo pipefail

# ── Colores ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

# ── Variables ─────────────────────────────────────────────────────────────────
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_USER="www-data"
PHP_BIN="/usr/bin/php"
COMPOSER_BIN="/usr/local/bin/composer"
NODE_BIN="/usr/bin/node"
NPM_BIN="/usr/bin/npm"
QUEUE_SERVICE="laravel-queue"  # Nombre del servicio systemd del worker

# ── Funciones de log ──────────────────────────────────────────────────────────
log_step()  { echo -e "\n${CYAN}▶ $1${NC}"; }
log_ok()    { echo -e "${GREEN}  ✓ $1${NC}"; }
log_warn()  { echo -e "${YELLOW}  ⚠ $1${NC}"; }
log_error() { echo -e "${RED}  ✗ $1${NC}"; }

run_as_app() {
    sudo -u "$APP_USER" bash -c "cd \"$APP_DIR\" && $1"
}

# ── Inicio ────────────────────────────────────────────────────────────────────
echo -e "\n${CYAN}============================================"
echo "  Portal Distribuidores — Deploy Script"
echo -e "============================================${NC}"
echo "  Directorio: $APP_DIR"
echo "  Usuario app: $APP_USER"
echo "  Fecha: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# ── Verificaciones previas ────────────────────────────────────────────────────
log_step "Verificando requisitos..."

if [[ ! -f "$APP_DIR/.env" ]]; then
    log_error "No se encontró .env en $APP_DIR. Crea y configura el .env antes de desplegar."
    exit 1
fi

if [[ ! -f "$APP_DIR/artisan" ]]; then
    log_error "No se encontró artisan. ¿Estás en el directorio correcto?"
    exit 1
fi

if ! command -v "$PHP_BIN" &>/dev/null && ! command -v php &>/dev/null; then
    log_error "PHP no encontrado. Instala PHP 8.3."
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
log_ok "PHP $PHP_VERSION encontrado"

if ! command -v "$COMPOSER_BIN" &>/dev/null && ! command -v composer &>/dev/null; then
    log_error "Composer no encontrado."
    exit 1
fi
log_ok "Composer encontrado"

if ! command -v npm &>/dev/null; then
    log_error "npm no encontrado. Instala Node.js 20."
    exit 1
fi
log_ok "Node.js $(node --version) / npm $(npm --version) encontrados"

# ── 1. Git pull ────────────────────────────────────────────────────────────────
log_step "Actualizando código fuente (git pull)..."

BRANCH=$(git -C "$APP_DIR" rev-parse --abbrev-ref HEAD 2>/dev/null || echo "desconocida")
log_warn "Rama actual: $BRANCH"

git -C "$APP_DIR" fetch origin
git -C "$APP_DIR" pull origin "$BRANCH"
COMMIT=$(git -C "$APP_DIR" rev-parse --short HEAD)
log_ok "Código actualizado — commit: $COMMIT"

# ── 2. Dependencias PHP ────────────────────────────────────────────────────────
log_step "Instalando dependencias PHP (composer install --no-dev)..."

run_as_app "composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist"
log_ok "Dependencias PHP instaladas"

# ── 3. Migraciones ─────────────────────────────────────────────────────────────
log_step "Ejecutando migraciones de base de datos..."

run_as_app "php artisan migrate --force"
log_ok "Migraciones completadas"

# ── 4. Limpiar y regenerar caches ─────────────────────────────────────────────
log_step "Regenerando caché de configuración, rutas y vistas..."

run_as_app "php artisan config:clear"
run_as_app "php artisan route:clear"
run_as_app "php artisan view:clear"
run_as_app "php artisan event:clear"

run_as_app "php artisan config:cache"
run_as_app "php artisan route:cache"
run_as_app "php artisan view:cache"
run_as_app "php artisan event:cache"

log_ok "Cachés regenerados"

# ── 5. Build de assets (Vite) ──────────────────────────────────────────────────
log_step "Instalando dependencias Node.js..."

# Usar npm ci si existe package-lock.json para instalación determinista
if [[ -f "$APP_DIR/package-lock.json" ]]; then
    npm --prefix "$APP_DIR" ci --omit=dev
    log_ok "Dependencias instaladas con npm ci (package-lock.json)"
else
    npm --prefix "$APP_DIR" install --omit=dev
    log_ok "Dependencias instaladas con npm install"
fi

log_step "Compilando assets frontend (Vite)..."
npm --prefix "$APP_DIR" run build
log_ok "Assets compilados en public/build/"

# ── 6. Permisos ────────────────────────────────────────────────────────────────
log_step "Ajustando permisos de storage y bootstrap/cache..."

chown -R "$APP_USER":"$APP_USER" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
log_ok "Permisos ajustados"

# ── 7. Reiniciar worker de colas ───────────────────────────────────────────────
log_step "Señalizando reinicio del worker de colas..."

# Primero enviar señal de restart graceful a los workers activos
run_as_app "php artisan queue:restart" 2>/dev/null || true

# Luego reiniciar el servicio systemd si existe
if systemctl is-enabled --quiet "$QUEUE_SERVICE" 2>/dev/null; then
    sleep 2
    systemctl restart "$QUEUE_SERVICE"
    log_ok "Servicio $QUEUE_SERVICE reiniciado"
elif command -v supervisorctl &>/dev/null && supervisorctl status "laravel-queue:*" &>/dev/null; then
    supervisorctl restart "laravel-queue:*"
    log_ok "Supervisor laravel-queue reiniciado"
else
    log_warn "No se encontró servicio de colas activo. Si usas systemd, verifica:"
    log_warn "  systemctl status $QUEUE_SERVICE"
fi

# ── 8. Recargar PHP-FPM ────────────────────────────────────────────────────────
log_step "Recargando PHP-FPM..."

if systemctl is-active --quiet php8.3-fpm; then
    systemctl reload php8.3-fpm
    log_ok "php8.3-fpm recargado"
else
    log_warn "php8.3-fpm no está activo. Verifica con: systemctl status php8.3-fpm"
fi

# ── Resumen final ──────────────────────────────────────────────────────────────
echo -e "\n${GREEN}============================================"
echo "  ✓ Despliegue completado exitosamente"
echo "  Commit: $COMMIT"
echo "  Rama:   $BRANCH"
echo "  Hora:   $(date '+%H:%M:%S')"
echo -e "============================================${NC}\n"

echo "  Próximos pasos si es el primer despliegue:"
echo "   → Verificar logs:  tail -f storage/logs/laravel.log"
echo "   → Estado worker:   systemctl status laravel-queue"
echo "   → Test app:        curl -I https://tu-dominio.com"
echo ""
