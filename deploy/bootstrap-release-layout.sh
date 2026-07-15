#!/usr/bin/env bash

set -Eeuo pipefail

ENVIRONMENT=""
BASE_DIR=""
NGINX_SITE=""
TLS_CONFIGURATION=""
RENDERED_NGINX=""

while [[ "$#" -gt 0 ]]; do
    case "$1" in
        --environment) ENVIRONMENT="${2:-}"; shift 2 ;;
        --base-dir) BASE_DIR="${2:-}"; shift 2 ;;
        --nginx-site) NGINX_SITE="${2:-}"; shift 2 ;;
        *) echo "Unknown argument: $1" >&2; exit 64 ;;
    esac
done

[[ "$(id -u)" -eq 0 ]] || { echo "This bootstrap must run as root." >&2; exit 1; }
[[ "$ENVIRONMENT" == "staging" || "$ENVIRONMENT" == "production" ]] || { echo "Invalid environment." >&2; exit 64; }

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
case "$ENVIRONMENT" in
    production)
        [[ "$BASE_DIR" == "/var/www/portalDistribuidores" ]] || { echo "Unexpected production base directory." >&2; exit 1; }
        NGINX_TEMPLATE="$SCRIPT_DIR/nginx.production.conf"
        NGINX_SITE="${NGINX_SITE:-/etc/nginx/sites-available/portal-distribuidores}"
        QUEUE_UNIT="$SCRIPT_DIR/laravel-queue.service"
        QUEUE_SERVICE="laravel-queue"
        ;;
    staging)
        [[ "$BASE_DIR" == "/var/www/portalDistribuidores-staging" ]] || { echo "Unexpected staging base directory." >&2; exit 1; }
        NGINX_TEMPLATE="$SCRIPT_DIR/nginx.staging.conf"
        NGINX_SITE="${NGINX_SITE:-/etc/nginx/sites-available/portal-distribuidores-staging}"
        QUEUE_UNIT="$SCRIPT_DIR/laravel-queue-staging.service"
        QUEUE_SERVICE="laravel-queue-staging"
        ;;
esac

[[ -f "$BASE_DIR/.env" && -f "$BASE_DIR/artisan" ]] || {
    echo "The legacy application checkout must be healthy before bootstrapping." >&2
    exit 1
}
[[ -f "$NGINX_TEMPLATE" && -f "$QUEUE_UNIT" ]] || { echo "Deployment templates are missing." >&2; exit 1; }
[[ "$NGINX_SITE" == /etc/nginx/sites-available/* ]] || { echo "Nginx site must be under /etc/nginx/sites-available." >&2; exit 1; }
[[ -f "$NGINX_SITE" ]] || { echo "The active HTTPS Nginx site is required to preserve its TLS configuration." >&2; exit 1; }

TLS_CONFIGURATION="$(mktemp)"
RENDERED_NGINX="$(mktemp)"
cleanup() {
    rm -f "$TLS_CONFIGURATION" "$RENDERED_NGINX"
}
trap cleanup EXIT

grep -E \
    '^[[:space:]]*(ssl_certificate|ssl_certificate_key|ssl_trusted_certificate|ssl_dhparam)[[:space:]]|^[[:space:]]*include[[:space:]]+/etc/letsencrypt/' \
    "$NGINX_SITE" > "$TLS_CONFIGURATION" || true
grep -Eq '^[[:space:]]*ssl_certificate[[:space:]]' "$TLS_CONFIGURATION" || { echo "The existing Nginx site has no TLS certificate directive." >&2; exit 1; }
grep -Eq '^[[:space:]]*ssl_certificate_key[[:space:]]' "$TLS_CONFIGURATION" || { echo "The existing Nginx site has no TLS private-key directive." >&2; exit 1; }

awk -v tls_file="$TLS_CONFIGURATION" '
    /__TLS_CONFIGURATION__/ {
        while ((getline line < tls_file) > 0) { print line }
        close(tls_file)
        next
    }
    { print }
' "$NGINX_TEMPLATE" > "$RENDERED_NGINX"
grep -Fq '__TLS_CONFIGURATION__' "$RENDERED_NGINX" && { echo "TLS template rendering failed." >&2; exit 1; }

install -d -m 0755 "$BASE_DIR/releases" "$BASE_DIR/shared" "$BASE_DIR/shared/deployments"

if [[ ! -e "$BASE_DIR/current" ]]; then
    ln -s "$BASE_DIR" "$BASE_DIR/current"
elif [[ ! -L "$BASE_DIR/current" ]]; then
    echo "$BASE_DIR/current exists and is not a symlink." >&2
    exit 1
fi

NGINX_BACKUP="${NGINX_SITE}.pre-cicd-v2-$(date -u '+%Y%m%d%H%M%S')"
cp -a "$NGINX_SITE" "$NGINX_BACKUP"
install -o root -g root -m 0644 "$RENDERED_NGINX" "$NGINX_SITE"
ln -sfn "$NGINX_SITE" "/etc/nginx/sites-enabled/$(basename "$NGINX_SITE")"

install -o root -g root -m 0644 "$QUEUE_UNIT" "/etc/systemd/system/${QUEUE_SERVICE}.service"
systemctl daemon-reload
systemctl enable "$QUEUE_SERVICE.service" >/dev/null

if ! nginx -t; then
    cp -a "$NGINX_BACKUP" "$NGINX_SITE"
    nginx -t
    echo "The rendered Nginx configuration was invalid; the previous site was restored." >&2
    exit 1
fi
systemctl reload nginx
systemctl restart "$QUEUE_SERVICE.service"
systemctl is-active --quiet "$QUEUE_SERVICE.service"
systemctl is-active --quiet php8.3-fpm.service

echo "BOOTSTRAP_RESULT status=success environment=$ENVIRONMENT current=$(readlink -f "$BASE_DIR/current") nginx_site=$NGINX_SITE"
