#!/usr/bin/env bash
# =============================================================================
# deploy.sh — Portal Distribuidores
# Despliegue en Hostinger VPS · Ubuntu 24.04 · Sin Docker
#
# Uso:
#   sudo bash deploy.sh
#
# Requisito: ejecutar desde el directorio raíz del proyecto.
# Git, Composer, Artisan y npm se ejecutan como www-data.
# =============================================================================

set -Eeuo pipefail

# ── Colores ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

# ── Variables base ───────────────────────────────────────────────────────────
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_USER="www-data"
SYSTEM_PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

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

# ── Modo mantenimiento: se levanta automáticamente al salir por error ─────────
MAINTENANCE_UP=false

ensure_app_up() {
    if [[ "$MAINTENANCE_UP" == "false" ]]; then
        log_warn "Levantando aplicación por salida inesperada..."
        run_as_app "\"$PHP_BIN\" artisan up" 2>/dev/null || true
        MAINTENANCE_UP=true
    fi
}

on_error() {
    local exit_code="$1"
    local line_no="$2"
    log_error "Deploy abortado en la línea ${line_no} (exit code ${exit_code})"
    ensure_app_up
    exit "$exit_code"
}
trap 'on_error $? $LINENO' ERR

require_file() {
    local file="$1"
    [[ -f "$file" ]] || fail "No se encontró $(basename "$file") en $APP_DIR"
}

require_git_repo() {
    if [[ ! -d "$APP_DIR/.git" && ! -f "$APP_DIR/.git" ]]; then
        fail "No se encontró .git en $APP_DIR. Este script debe ejecutarse dentro del repositorio."
    fi
}

resolve_cmd() {
    local name="$1"
    local path
    path="$(command -v "$name" 2>/dev/null || true)"
    [[ -n "$path" ]] || fail "No se encontró '$name' en PATH"
    printf '%s\n' "$path"
}

# ── Usuario de aplicación ────────────────────────────────────────────────────
APP_HOME="$(getent passwd "$APP_USER" | cut -d: -f6 || true)"
[[ -n "$APP_HOME" ]] || fail "No se pudo determinar el HOME del usuario '$APP_USER'"

set_laravel_writable_permissions() {
    mkdir -p "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

    chown -R "$APP_USER:$APP_USER" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

    find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type d -exec chmod 775 {} \;
    find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type f ! -name ".gitignore" -exec chmod 664 {} \;
    find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type f -name ".gitignore" -exec chmod 644 {} \;
}

# run_as_app: ejecuta un comando como APP_USER con el entorno mínimo necesario.
# Se usa bash -c (sin -l) para evitar que los archivos de perfil de www-data
# sobreescriban el PATH que pasamos explícitamente.
run_as_app() {
    sudo -u "$APP_USER" env \
        HOME="$APP_HOME" \
        XDG_CONFIG_HOME="$APP_HOME/.config" \
        PATH="$SYSTEM_PATH" \
        bash -c "cd \"$APP_DIR\" && $1"
}

# ── Inicio ───────────────────────────────────────────────────────────────────
echo -e "\n${CYAN}============================================"
echo "  Portal Distribuidores — Deploy Script"
echo -e "============================================${NC}"
echo "  Directorio: $APP_DIR"
echo "  Usuario app: $APP_USER"
echo "  Home app:    $APP_HOME"
echo "  Fecha:       $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

[[ "${EUID}" -eq 0 ]] || fail "Este script debe ejecutarse con sudo o como root"

# ── Verificaciones previas ───────────────────────────────────────────────────
log_step "Verificando requisitos..."

require_file "$APP_DIR/.env"
require_file "$APP_DIR/artisan"
require_git_repo

PHP_BIN="$(resolve_cmd php)"
COMPOSER_BIN="$(resolve_cmd composer)"
GIT_BIN="$(resolve_cmd git)"
NPM_BIN="$(resolve_cmd npm)"
NODE_BIN="$(resolve_cmd node)"
SSH_KEYSCAN_BIN="$(resolve_cmd ssh-keyscan)"

PHP_VERSION="$("$PHP_BIN" -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")"
log_ok "PHP $PHP_VERSION encontrado en $PHP_BIN"
log_ok "Composer encontrado en $COMPOSER_BIN"
log_ok "Git encontrado en $GIT_BIN"
log_ok "Node.js $("$NODE_BIN" --version) / npm $("$NPM_BIN" --version) encontrados"

# ── Advertencia si APP_ENV no es production ───────────────────────────────────
log_step "Verificando entorno de la aplicación..."

APP_ENV_VALUE="$(grep -E '^APP_ENV=' "$APP_DIR/.env" | cut -d= -f2 | tr -d '"' | tr -d "'" | tr -d '[:space:]' || true)"
APP_DEBUG_VALUE="$(grep -E '^APP_DEBUG=' "$APP_DIR/.env" | cut -d= -f2 | tr -d '"' | tr -d "'" | tr -d '[:space:]' || true)"

if [[ "$APP_ENV_VALUE" != "production" ]]; then
    log_warn "APP_ENV=${APP_ENV_VALUE} — Se recomienda APP_ENV=production en el VPS."
fi
if [[ "$APP_DEBUG_VALUE" == "true" ]]; then
    log_warn "APP_DEBUG=true — En producción debe ser false para no exponer errores."
fi
log_ok "Variables de entorno revisadas (APP_ENV=${APP_ENV_VALUE})"

# ── Preparar HOME/config del usuario app ─────────────────────────────────────
log_step "Preparando HOME y configuración del usuario de aplicación..."

mkdir -p "$APP_HOME/.config"
chown "$APP_USER:$APP_USER" "$APP_HOME"
chown -R "$APP_USER:$APP_USER" "$APP_HOME/.config"
chmod 755 "$APP_HOME"
chmod 755 "$APP_HOME/.config"

log_ok "HOME listo para $APP_USER"

# ── Alinear permisos de storage y .env (no el proyecto entero aún) ────────────
log_step "Preparando permisos de escritura iniciales..."

set_laravel_writable_permissions

if [[ -f "$APP_DIR/.env" ]]; then
    chown "$APP_USER:$APP_USER" "$APP_DIR/.env"
    chmod 640 "$APP_DIR/.env"
fi

log_ok "Permisos de storage y .env listos"

# ── Verificar remoto Git ─────────────────────────────────────────────────────
log_step "Verificando remoto Git..."

# Para que git funcione como www-data necesitamos que el directorio esté
# bajo su propiedad antes del fetch/reset.
chown -R "$APP_USER:$APP_USER" "$APP_DIR"
set_laravel_writable_permissions
if [[ -f "$APP_DIR/.env" ]]; then
    chown "$APP_USER:$APP_USER" "$APP_DIR/.env"
    chmod 640 "$APP_DIR/.env"
fi

REMOTE_URL="$(run_as_app "\"$GIT_BIN\" remote get-url origin" 2>/dev/null || true)"
[[ -n "$REMOTE_URL" ]] || fail "No se pudo leer el remoto 'origin'"

echo "  origin: $REMOTE_URL"

if [[ "$REMOTE_URL" == https://github.com/* ]]; then
    log_warn "origin usa HTTPS. Se recomienda SSH para evitar prompts en cada deploy."
fi

# ── Preparar SSH si el remoto usa GitHub por SSH ─────────────────────────────
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
    if [[ -f "$APP_HOME/.ssh/id_ed25519.pub" ]]; then
        chmod 644 "$APP_HOME/.ssh/id_ed25519.pub"
    fi
    if [[ -f "$APP_HOME/.ssh/id_rsa" ]]; then
        chmod 600 "$APP_HOME/.ssh/id_rsa"
    fi
    if [[ -f "$APP_HOME/.ssh/id_rsa.pub" ]]; then
        chmod 644 "$APP_HOME/.ssh/id_rsa.pub"
    fi

    if [[ ! -f "$APP_HOME/.ssh/id_ed25519" && ! -f "$APP_HOME/.ssh/id_rsa" ]]; then
        fail "El remoto usa SSH pero no existe una llave privada en $APP_HOME/.ssh para $APP_USER"
    fi

    if ! grep -q "github.com" "$APP_HOME/.ssh/known_hosts" 2>/dev/null; then
        run_as_app "\"$SSH_KEYSCAN_BIN\" -H github.com >> \"$APP_HOME/.ssh/known_hosts\""
        log_ok "github.com agregado a known_hosts"
    fi

    log_ok "SSH listo para GitHub"
fi

# ── Validar working tree limpio antes del pull ───────────────────────────────
log_step "Validando estado local del repositorio..."

GIT_STATUS="$(run_as_app "\"$GIT_BIN\" status --porcelain --untracked-files=no" || true)"
if [[ -n "$GIT_STATUS" ]]; then
    echo "$GIT_STATUS"
    fail "El repositorio tiene cambios locales en archivos versionados. Haz commit, stash o restore antes del deploy."
fi
log_ok "Working tree limpio"

# ── 1. Git fetch + reset --hard (más robusto que pull --ff-only) ──────────────
log_step "Actualizando código fuente..."

BRANCH="$(run_as_app "\"$GIT_BIN\" rev-parse --abbrev-ref HEAD" 2>/dev/null || true)"
if [[ -z "$BRANCH" || "$BRANCH" == "HEAD" ]]; then
    BRANCH="$(run_as_app "\"$GIT_BIN\" symbolic-ref --short refs/remotes/origin/HEAD | sed 's@^origin/@@'" 2>/dev/null || true)"
fi
[[ -n "$BRANCH" ]] || fail "No se pudo determinar la rama actual del repositorio"

log_warn "Rama actual: $BRANCH"

run_as_app "\"$GIT_BIN\" fetch --prune origin"
run_as_app "\"$GIT_BIN\" reset --hard origin/\"$BRANCH\""

COMMIT="$(run_as_app "\"$GIT_BIN\" rev-parse --short HEAD")"
log_ok "Código actualizado — commit: $COMMIT"

# ── 2. Dependencias PHP ──────────────────────────────────────────────────────
log_step "Instalando dependencias PHP (composer install --no-dev)..."

run_as_app "\"$COMPOSER_BIN\" install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-progress"
log_ok "Dependencias PHP instaladas"

# ── 3. Modo mantenimiento ─────────────────────────────────────────────────────
log_step "Activando modo mantenimiento..."

run_as_app "\"$PHP_BIN\" artisan down --render=\"errors::503\" --retry=60"
MAINTENANCE_UP=false
log_ok "Aplicación en modo mantenimiento"

# ── 4. Limpiar cachés ANTES de migrar ────────────────────────────────────────
log_step "Limpiando cachés antes de migrar..."

run_as_app "\"$PHP_BIN\" artisan config:clear"
run_as_app "\"$PHP_BIN\" artisan route:clear"
run_as_app "\"$PHP_BIN\" artisan view:clear"
run_as_app "\"$PHP_BIN\" artisan event:clear"

log_ok "Cachés limpiados"

# ── 5. Migraciones ───────────────────────────────────────────────────────────
log_step "Ejecutando migraciones de base de datos..."

run_as_app "\"$PHP_BIN\" artisan migrate --force"
log_ok "Migraciones completadas"

# ── 6. Regenerar cachés ──────────────────────────────────────────────────────
log_step "Regenerando caché de configuración, rutas, vistas y eventos..."

run_as_app "\"$PHP_BIN\" artisan config:cache"
run_as_app "\"$PHP_BIN\" artisan route:cache"
run_as_app "\"$PHP_BIN\" artisan view:cache"
run_as_app "\"$PHP_BIN\" artisan event:cache"

log_ok "Cachés regenerados"

# ── 7. Build de assets (Vite) ────────────────────────────────────────────────
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
    run_as_app "NODE_ENV=production \"$NPM_BIN\" run build"

    [[ -f "$APP_DIR/public/build/manifest.json" ]] || fail "El build terminó pero no se encontró public/build/manifest.json"

    log_ok "Assets compilados en public/build/"
else
    log_warn "No se encontró package.json. Se omite el build frontend."
fi

# ── 8. Storage link ───────────────────────────────────────────────────────────
log_step "Asegurando enlace simbólico de storage..."

run_as_app "\"$PHP_BIN\" artisan storage:link --force"
log_ok "Storage link verificado"

# ── 9. Ajuste final de permisos ──────────────────────────────────────────────
log_step "Ajustando permisos finales..."

chown -R "$APP_USER:$APP_USER" "$APP_DIR"
set_laravel_writable_permissions

if [[ -f "$APP_DIR/.env" ]]; then
    chown "$APP_USER:$APP_USER" "$APP_DIR/.env"
    chmod 640 "$APP_DIR/.env"
fi

log_ok "Permisos finales ajustados"

# ── 10. Reiniciar worker de colas ─────────────────────────────────────────────
log_step "Señalizando reinicio del worker de colas..."

run_as_app "\"$PHP_BIN\" artisan queue:restart" || true

if systemctl cat "$QUEUE_SERVICE" >/dev/null 2>&1; then
    sleep 2
    systemctl restart "$QUEUE_SERVICE"
    log_ok "Servicio $QUEUE_SERVICE reiniciado"
elif command -v supervisorctl >/dev/null 2>&1 && supervisorctl status "laravel-queue:*" >/dev/null 2>&1; then
    supervisorctl restart "laravel-queue:*"
    log_ok "Supervisor laravel-queue reiniciado"
else
    log_warn "No se encontró un servicio de colas administrado por systemd o Supervisor"
    log_warn "Revisa con: systemctl status $QUEUE_SERVICE"
fi

# ── 11. Recargar PHP-FPM ──────────────────────────────────────────────────────
log_step "Recargando PHP-FPM..."

if systemctl is-active --quiet "$PHP_FPM_SERVICE"; then
    systemctl reload "$PHP_FPM_SERVICE"
    log_ok "$PHP_FPM_SERVICE recargado"
else
    log_warn "$PHP_FPM_SERVICE no está activo. Verifica con: systemctl status $PHP_FPM_SERVICE"
fi

# ── 12. Levantar aplicación ───────────────────────────────────────────────────
log_step "Levantando aplicación..."

run_as_app "\"$PHP_BIN\" artisan up"
MAINTENANCE_UP=true
log_ok "Aplicación en línea"

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
