#!/usr/bin/env bash
# shellcheck disable=SC2016

set -Eeuo pipefail

APP_USER="www-data"
SYSTEM_PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"
EXPECTED_PRODUCTION_DB="portal_distribuidores"
EXPECTED_STAGING_DB="portal_distribuidores_staging"

COMMAND="${1:-}"
[[ "$COMMAND" == "deploy" ]] || {
    echo "Usage: sudo bash deploy.sh deploy --environment <staging|production> --base-dir <path> --artifact <tar> --source-sha <sha> --promotion-sha <sha> [--simulate-failure-after-switch]" >&2
    exit 64
}
shift

ENVIRONMENT=""
BASE_DIR=""
ARTIFACT=""
SOURCE_SHA=""
PROMOTION_SHA=""
SIMULATE_FAILURE=false

while [[ "$#" -gt 0 ]]; do
    case "$1" in
        --environment) ENVIRONMENT="${2:-}"; shift 2 ;;
        --base-dir) BASE_DIR="${2:-}"; shift 2 ;;
        --artifact) ARTIFACT="${2:-}"; shift 2 ;;
        --source-sha) SOURCE_SHA="${2:-}"; shift 2 ;;
        --promotion-sha) PROMOTION_SHA="${2:-}"; shift 2 ;;
        --simulate-failure-after-switch) SIMULATE_FAILURE=true; shift ;;
        *) echo "Unknown argument: $1" >&2; exit 64 ;;
    esac
done

fail() {
    echo "ERROR: $1" >&2
    return 1
}

[[ "$(id -u)" -eq 0 ]] || fail "deploy.sh must run as root."
[[ "$ENVIRONMENT" == "staging" || "$ENVIRONMENT" == "production" ]] || fail "Invalid environment."
[[ "$SOURCE_SHA" =~ ^[0-9a-f]{40}$ ]] || fail "source-sha must be a full Git SHA."
[[ "$PROMOTION_SHA" =~ ^[0-9a-f]{40}$ ]] || fail "promotion-sha must be a full Git SHA."
[[ -f "$ARTIFACT" ]] || fail "Artifact not found: $ARTIFACT"

case "$ENVIRONMENT" in
    production)
        [[ "$BASE_DIR" == "/var/www/portalDistribuidores" ]] || fail "Unexpected production base directory."
        EXPECTED_DB="$EXPECTED_PRODUCTION_DB"
        QUEUE_SERVICE="laravel-queue"
        QUEUE_UNIT_NAME="laravel-queue.service"
        NGINX_TEMPLATE_NAME="nginx.production.conf"
        NGINX_SITE_NAME="portal-distribuidores"
        ;;
    staging)
        [[ "$BASE_DIR" == "/var/www/portalDistribuidores-staging" ]] || fail "Unexpected staging base directory."
        EXPECTED_DB="$EXPECTED_STAGING_DB"
        QUEUE_SERVICE="laravel-queue-staging"
        QUEUE_UNIT_NAME="laravel-queue-staging.service"
        NGINX_TEMPLATE_NAME="nginx.staging.conf"
        NGINX_SITE_NAME="portal-distribuidores-staging"
        ;;
esac

BASE_DIR="$(realpath -m "$BASE_DIR")"
RELEASES_DIR="$BASE_DIR/releases"
SHARED_DIR="$BASE_DIR/shared"
CURRENT_LINK="$BASE_DIR/current"
RELEASE_DIR="$RELEASES_DIR/$SOURCE_SHA"
INCOMING_DIR="$RELEASES_DIR/.incoming-${SOURCE_SHA}-$$"
ARTIFACT_DIR="$(dirname "$(realpath "$ARTIFACT")")"
MANIFEST_FILE="$ARTIFACT_DIR/manifest.json"
CHECKSUM_FILE="$ARTIFACT_DIR/SHA256SUMS"
APP_HOME="$(getent passwd "$APP_USER" | cut -d: -f6 || true)"
PHP_BIN="$(command -v php8.3 2>/dev/null || command -v php 2>/dev/null || true)"
NGINX_BIN="$(command -v nginx 2>/dev/null || true)"
BACKUP_FILE=""
PREVIOUS_TARGET=""
SWITCHED=false
NGINX_SITE="/etc/nginx/sites-available/$NGINX_SITE_NAME"
NGINX_ENABLED_LINK="/etc/nginx/sites-enabled/$NGINX_SITE_NAME"
NGINX_BACKUP=""
NGINX_LINK_EXISTED=false
NGINX_PREVIOUS_LINK_TARGET=""
NGINX_CONFIG_CHANGED=false

[[ -n "$APP_HOME" ]] || fail "Application user $APP_USER does not exist."
[[ -n "$PHP_BIN" ]] || fail "PHP was not found."
[[ -n "$NGINX_BIN" ]] || fail "Nginx was not found."
[[ -f "$MANIFEST_FILE" && -f "$CHECKSUM_FILE" ]] || fail "Artifact metadata is incomplete."

run_as_app() {
    local working_directory="$1"
    shift
    sudo -u "$APP_USER" env HOME="$APP_HOME" PATH="$SYSTEM_PATH" \
        bash -c 'cd "$1"; shift; exec "$@"' bash "$working_directory" "$@"
}

read_env_value() {
    local key="$1"
    local raw
    raw="$(grep -E "^${key}=" "$SHARED_DIR/.env" | head -n 1 | cut -d= -f2- || true)"
    raw="${raw%\"}"; raw="${raw#\"}"; raw="${raw%\'}"; raw="${raw#\'}"
    printf '%s' "$raw"
}

manifest_value() {
    "$PHP_BIN" -r '
        $data = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
        $value = $data[$argv[2]] ?? null;
        if (!is_string($value)) { exit(2); }
        echo $value;
    ' "$MANIFEST_FILE" "$1"
}

activate_target() {
    local target="$1"
    local temporary_link="$BASE_DIR/.current-$$"
    rm -f "$temporary_link"
    ln -s "$target" "$temporary_link"
    mv -Tf "$temporary_link" "$CURRENT_LINK"
}

restore_nginx_configuration() {
    local restore_candidate

    [[ "$NGINX_CONFIG_CHANGED" == true ]] || return 0
    [[ -f "$NGINX_BACKUP" ]] || return 1

    restore_candidate="$(mktemp "/etc/nginx/sites-available/.${NGINX_SITE_NAME}.restore.XXXXXX")"
    install -o root -g root -m 0644 "$NGINX_BACKUP" "$restore_candidate"
    mv -f "$restore_candidate" "$NGINX_SITE"

    if [[ "$NGINX_LINK_EXISTED" == true ]]; then
        ln -sfn "$NGINX_PREVIOUS_LINK_TARGET" "$NGINX_ENABLED_LINK"
    else
        rm -f "$NGINX_ENABLED_LINK"
    fi

    if ! "$NGINX_BIN" -t; then
        echo "NGINX_ROLLBACK_RESULT status=failure reason=restored_configuration_invalid" >&2
        return 1
    fi

    if ! systemctl reload nginx.service; then
        echo "NGINX_ROLLBACK_RESULT status=failure reason=reload_failed" >&2
        return 1
    fi

    NGINX_CONFIG_CHANGED=false
    echo "NGINX_ROLLBACK_RESULT status=success site=$NGINX_SITE" >&2
}

install_nginx_configuration() {
    local template="$RELEASE_DIR/deploy/$NGINX_TEMPLATE_NAME"
    local tls_configuration rendered_configuration candidate

    [[ -f "$template" ]] || fail "Nginx template is missing: $template"
    [[ -f "$NGINX_SITE" ]] || fail "The active Nginx site is required to preserve its TLS configuration: $NGINX_SITE"

    if [[ -e "$NGINX_ENABLED_LINK" && ! -L "$NGINX_ENABLED_LINK" ]]; then
        fail "$NGINX_ENABLED_LINK exists and is not a symlink."
    fi

    tls_configuration="$(mktemp)"
    rendered_configuration="$(mktemp)"
    candidate="$(mktemp "/etc/nginx/sites-available/.${NGINX_SITE_NAME}.candidate.XXXXXX")"

    grep -E \
        '^[[:space:]]*(ssl_certificate|ssl_certificate_key|ssl_trusted_certificate|ssl_dhparam)[[:space:]]|^[[:space:]]*include[[:space:]]+/etc/letsencrypt/' \
        "$NGINX_SITE" > "$tls_configuration" || true
    grep -Eq '^[[:space:]]*ssl_certificate[[:space:]]' "$tls_configuration" \
        || fail "The active Nginx site has no TLS certificate directive."
    grep -Eq '^[[:space:]]*ssl_certificate_key[[:space:]]' "$tls_configuration" \
        || fail "The active Nginx site has no TLS private-key directive."
    grep -Fq '__TLS_CONFIGURATION__' "$template" \
        || fail "The Nginx template has no TLS placeholder."

    awk -v tls_file="$tls_configuration" '
        /__TLS_CONFIGURATION__/ {
            while ((getline line < tls_file) > 0) { print line }
            close(tls_file)
            next
        }
        { print }
    ' "$template" > "$rendered_configuration"

    grep -Fq '__TLS_CONFIGURATION__' "$rendered_configuration" \
        && fail "The Nginx TLS template was not rendered."
    grep -Fq "$BASE_DIR/current/public" "$rendered_configuration" \
        || fail "The Nginx template does not target the atomic current symlink."

    NGINX_BACKUP="$(mktemp "$SHARED_DIR/deployments/nginx-${ENVIRONMENT}.previous.XXXXXX")"
    cp -a "$NGINX_SITE" "$NGINX_BACKUP"

    if [[ -L "$NGINX_ENABLED_LINK" ]]; then
        NGINX_LINK_EXISTED=true
        NGINX_PREVIOUS_LINK_TARGET="$(readlink "$NGINX_ENABLED_LINK")"
    fi

    install -o root -g root -m 0644 "$rendered_configuration" "$candidate"
    mv -f "$candidate" "$NGINX_SITE"
    ln -sfn "$NGINX_SITE" "$NGINX_ENABLED_LINK"
    NGINX_CONFIG_CHANGED=true

    rm -f "$tls_configuration" "$rendered_configuration"

    if ! "$NGINX_BIN" -t; then
        restore_nginx_configuration || true
        fail "The candidate Nginx configuration is invalid; the previous site was restored."
    fi

    if ! systemctl reload nginx.service; then
        restore_nginx_configuration || true
        fail "Nginx could not be reloaded; the previous site was restored."
    fi

    echo "NGINX_DEPLOY_RESULT status=success environment=$ENVIRONMENT site=$NGINX_SITE"
}

restart_runtime() {
    systemctl daemon-reload
    systemctl enable "$QUEUE_SERVICE.service" >/dev/null
    systemctl restart "$QUEUE_SERVICE.service"
    systemctl is-active --quiet "$QUEUE_SERVICE.service"
    systemctl is-active --quiet php8.3-fpm.service
    systemctl reload php8.3-fpm.service
    systemctl enable --now cron.service >/dev/null
    systemctl is-active --quiet cron.service
}

verify_health() {
    local target="$1"
    local expected_revision=""
    local expected_tree=""
    local app_url host response_file

    if [[ -f "$target/REVISION" && -f "$target/TREE" ]]; then
        expected_revision="$(tr -d '\r\n' < "$target/REVISION")"
        expected_tree="$(tr -d '\r\n' < "$target/TREE")"
    fi

    app_url="$(read_env_value APP_URL)"
    [[ "$app_url" == https://* ]] || fail "APP_URL must use HTTPS."
    host="$("$PHP_BIN" -r '$host = parse_url($argv[1], PHP_URL_HOST); if (!is_string($host) || $host === "") { exit(2); } echo $host;' "$app_url")"
    response_file="$(mktemp)"

    if [[ -n "$expected_revision" ]]; then
        curl --fail --silent --show-error --max-time 30 \
            --resolve "${host}:443:127.0.0.1" \
            --output "$response_file" \
            "${app_url%/}/up"
        "$PHP_BIN" -r '
            $payload = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
            if (($payload["status"] ?? null) !== "ok" || ($payload["revision"] ?? null) !== $argv[2] || ($payload["tree"] ?? null) !== $argv[3]) {
                fwrite(STDERR, "Health response does not match the active release.\n");
                exit(1);
            }
        ' "$response_file" "$expected_revision" "$expected_tree"
    else
        curl --fail --silent --show-error --location --max-time 30 \
            --resolve "${host}:443:127.0.0.1" \
            --output /dev/null \
            "$app_url"
    fi

    rm -f "$response_file"
}

rollback_after_failure() {
    local original_status="$1"
    local line_number="$2"
    trap - ERR
    echo "DEPLOY_RESULT status=failure source_sha=$SOURCE_SHA line=$line_number" >&2

    if [[ "$NGINX_CONFIG_CHANGED" == true ]]; then
        restore_nginx_configuration || true
    fi

    if [[ "$SWITCHED" == true && -n "$PREVIOUS_TARGET" && -d "$PREVIOUS_TARGET" ]]; then
        echo "Restoring previous release: $PREVIOUS_TARGET" >&2
        if activate_target "$PREVIOUS_TARGET" && restart_runtime && verify_health "$PREVIOUS_TARGET"; then
            echo "ROLLBACK_RESULT status=success target=$PREVIOUS_TARGET" >&2
        else
            echo "ROLLBACK_RESULT status=failure target=$PREVIOUS_TARGET" >&2
        fi
    else
        echo "ROLLBACK_RESULT status=not_required" >&2
    fi

    rm -rf "$INCOMING_DIR"
    exit "$original_status"
}
trap 'rollback_after_failure $? $LINENO' ERR

install -d -m 0755 "$BASE_DIR" "$RELEASES_DIR" "$SHARED_DIR" "$SHARED_DIR/deployments"

if [[ ! -f "$SHARED_DIR/.env" ]]; then
    [[ -f "$BASE_DIR/.env" ]] || fail "Neither shared/.env nor the legacy .env exists."
    install -o "$APP_USER" -g "$APP_USER" -m 0640 "$BASE_DIR/.env" "$SHARED_DIR/.env"
fi

if [[ ! -e "$SHARED_DIR/storage" ]]; then
    if [[ -d "$BASE_DIR/storage" && ! -L "$BASE_DIR/storage" ]]; then
        mv "$BASE_DIR/storage" "$SHARED_DIR/storage"
        ln -s "$SHARED_DIR/storage" "$BASE_DIR/storage"
    else
        install -d -o "$APP_USER" -g "$APP_USER" -m 0775 "$SHARED_DIR/storage"
    fi
fi
chown -R "$APP_USER:$APP_USER" "$SHARED_DIR/storage"

(
    cd "$ARTIFACT_DIR"
    sha256sum --check "$(basename "$CHECKSUM_FILE")"
)

MANIFEST_SOURCE_SHA="$(manifest_value source_commit)"
MANIFEST_SOURCE_TREE="$(manifest_value source_tree)"
[[ "$MANIFEST_SOURCE_SHA" == "$SOURCE_SHA" ]] || fail "Artifact source SHA mismatch."
[[ "$MANIFEST_SOURCE_TREE" =~ ^[0-9a-f]{40}$ ]] || fail "Artifact tree SHA is invalid."

while IFS= read -r archive_entry; do
    [[ "$archive_entry" != /* ]] || fail "Artifact contains an absolute path."
    IFS='/' read -r -a path_parts <<< "$archive_entry"
    for path_part in "${path_parts[@]}"; do
        [[ "$path_part" != ".." ]] || fail "Artifact contains path traversal."
    done
done < <(tar -tzf "$ARTIFACT")

if [[ ! -d "$RELEASE_DIR" ]]; then
    rm -rf "$INCOMING_DIR"
    install -d -m 0755 "$INCOMING_DIR"
    tar -xzf "$ARTIFACT" -C "$INCOMING_DIR"

    [[ "$(tr -d '\r\n' < "$INCOMING_DIR/REVISION")" == "$SOURCE_SHA" ]] || fail "Extracted REVISION mismatch."
    [[ "$(tr -d '\r\n' < "$INCOMING_DIR/TREE")" == "$MANIFEST_SOURCE_TREE" ]] || fail "Extracted TREE mismatch."
    cmp --silent "$MANIFEST_FILE" "$INCOMING_DIR/manifest.json" || fail "Embedded manifest mismatch."

    rm -rf "$INCOMING_DIR/storage"
    ln -s "$SHARED_DIR/storage" "$INCOMING_DIR/storage"
    ln -s "$SHARED_DIR/.env" "$INCOMING_DIR/.env"
    install -d -m 0775 "$INCOMING_DIR/bootstrap/cache"
    install -d -m 0775 "$SHARED_DIR/storage/app/public"
    if [[ -e "$INCOMING_DIR/public/storage" && ! -L "$INCOMING_DIR/public/storage" ]]; then
        fail "public/storage must not be a real directory in the artifact."
    fi
    rm -f "$INCOMING_DIR/public/storage"
    ln -s "$SHARED_DIR/storage/app/public" "$INCOMING_DIR/public/storage"

    "$PHP_BIN" -r '
        file_put_contents($argv[1], json_encode([
            "source_commit" => $argv[2],
            "source_tree" => $argv[3],
            "promotion_commit" => $argv[4],
            "environment" => $argv[5],
            "deployed_at" => gmdate("c"),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    ' "$INCOMING_DIR/promotion.json" "$SOURCE_SHA" "$MANIFEST_SOURCE_TREE" "$PROMOTION_SHA" "$ENVIRONMENT"

    chown -R root:"$APP_USER" "$INCOMING_DIR"
    chown -R "$APP_USER:$APP_USER" "$INCOMING_DIR/bootstrap/cache" "$SHARED_DIR/storage"
    mv "$INCOMING_DIR" "$RELEASE_DIR"
else
    [[ "$(tr -d '\r\n' < "$RELEASE_DIR/REVISION")" == "$SOURCE_SHA" ]] || fail "Existing release has a different REVISION."
    [[ "$(tr -d '\r\n' < "$RELEASE_DIR/TREE")" == "$MANIFEST_SOURCE_TREE" ]] || fail "Existing release has a different TREE."
fi

APP_ENV_VALUE="$(read_env_value APP_ENV)"
APP_DEBUG_VALUE="$(read_env_value APP_DEBUG)"
APP_URL_VALUE="$(read_env_value APP_URL)"
DB_DATABASE_VALUE="$(read_env_value DB_DATABASE)"
DB_HOST_VALUE="$(read_env_value DB_HOST)"; DB_HOST_VALUE="${DB_HOST_VALUE:-127.0.0.1}"
DB_PORT_VALUE="$(read_env_value DB_PORT)"; DB_PORT_VALUE="${DB_PORT_VALUE:-5432}"
DB_USERNAME_VALUE="$(read_env_value DB_USERNAME)"
DB_PASSWORD_VALUE="$(read_env_value DB_PASSWORD)"
DB_SSLMODE_VALUE="$(read_env_value DB_SSLMODE)"
QUEUE_CONNECTION_VALUE="$(read_env_value QUEUE_CONNECTION)"; QUEUE_CONNECTION_VALUE="${QUEUE_CONNECTION_VALUE:-database}"
CACHE_STORE_VALUE="$(read_env_value CACHE_STORE)"; CACHE_STORE_VALUE="${CACHE_STORE_VALUE:-database}"
DB_QUEUE_VALUE="$(read_env_value DB_QUEUE)"; DB_QUEUE_VALUE="${DB_QUEUE_VALUE:-default}"
DB_QUEUE_RETRY_AFTER_VALUE="$(read_env_value DB_QUEUE_RETRY_AFTER)"; DB_QUEUE_RETRY_AFTER_VALUE="${DB_QUEUE_RETRY_AFTER_VALUE:-660}"
SESSION_SECURE_COOKIE_VALUE="$(read_env_value SESSION_SECURE_COOKIE)"
DEPLOY_ENV_NAME_VALUE="$(read_env_value DEPLOY_ENV_NAME)"; DEPLOY_ENV_NAME_VALUE="${DEPLOY_ENV_NAME_VALUE:-$APP_ENV_VALUE}"
DEPLOY_QUEUE_SERVICE_VALUE="$(read_env_value DEPLOY_QUEUE_SERVICE)"; DEPLOY_QUEUE_SERVICE_VALUE="${DEPLOY_QUEUE_SERVICE_VALUE:-$QUEUE_SERVICE}"
DEPLOY_PHP_FPM_SERVICE_VALUE="$(read_env_value DEPLOY_PHP_FPM_SERVICE)"; DEPLOY_PHP_FPM_SERVICE_VALUE="${DEPLOY_PHP_FPM_SERVICE_VALUE:-php8.3-fpm}"
PRIVATE_DISK_DRIVER_VALUE="$(read_env_value PRIVATE_DISK_DRIVER)"
PRIVATE_BUCKET_VALUE="$(read_env_value PRIVATE_BUCKET)"
ORDER_PDFS_DISK_VALUE="$(read_env_value ORDER_PDFS_DISK)"; ORDER_PDFS_DISK_VALUE="${ORDER_PDFS_DISK_VALUE:-private}"
TECH_SHEETS_DISK_VALUE="$(read_env_value TECH_SHEETS_DISK)"; TECH_SHEETS_DISK_VALUE="${TECH_SHEETS_DISK_VALUE:-private}"

[[ "$APP_ENV_VALUE" == "$ENVIRONMENT" ]] || fail "APP_ENV must match the deployment environment."
[[ "$DEPLOY_ENV_NAME_VALUE" == "$ENVIRONMENT" ]] || fail "DEPLOY_ENV_NAME must match the deployment environment."
[[ "$DEPLOY_QUEUE_SERVICE_VALUE" == "$QUEUE_SERVICE" ]] || fail "DEPLOY_QUEUE_SERVICE does not match the environment."
[[ "$DEPLOY_PHP_FPM_SERVICE_VALUE" == "php8.3-fpm" ]] || fail "DEPLOY_PHP_FPM_SERVICE must be php8.3-fpm."
[[ "${APP_DEBUG_VALUE,,}" != "true" ]] || fail "APP_DEBUG must be false."
[[ "$APP_URL_VALUE" == https://* ]] || fail "APP_URL must use HTTPS."
[[ "$DB_DATABASE_VALUE" == "$EXPECTED_DB" ]] || fail "Unexpected database for $ENVIRONMENT."
[[ -n "$DB_USERNAME_VALUE" ]] || fail "DB_USERNAME is required."
[[ "${DB_SSLMODE_VALUE,,}" =~ ^(require|verify-ca|verify-full)$ ]] || fail "DB_SSLMODE must enforce TLS."
[[ "$QUEUE_CONNECTION_VALUE" == "database" ]] || fail "QUEUE_CONNECTION must be database."
[[ "$CACHE_STORE_VALUE" == "database" ]] || fail "CACHE_STORE must be database."
[[ "$DB_QUEUE_VALUE" == "default" ]] || fail "DB_QUEUE must be default."
[[ "$DB_QUEUE_RETRY_AFTER_VALUE" =~ ^[0-9]+$ ]] || fail "DB_QUEUE_RETRY_AFTER must be numeric."
(( DB_QUEUE_RETRY_AFTER_VALUE > 600 )) || fail "DB_QUEUE_RETRY_AFTER must exceed the worker timeout."
[[ "${SESSION_SECURE_COOKIE_VALUE,,}" == "true" ]] || fail "SESSION_SECURE_COOKIE must be true."
[[ "$ORDER_PDFS_DISK_VALUE" != "public" ]] || fail "ORDER_PDFS_DISK must not be public."
[[ "$TECH_SHEETS_DISK_VALUE" != "public" ]] || fail "TECH_SHEETS_DISK must not be public."
if [[ "${PRIVATE_DISK_DRIVER_VALUE,,}" == "s3" ]]; then
    [[ -n "$PRIVATE_BUCKET_VALUE" ]] || fail "PRIVATE_BUCKET is required when PRIVATE_DISK_DRIVER=s3."
fi

if [[ "$ENVIRONMENT" == "production" ]]; then
    PG_DUMP_BIN="$(command -v pg_dump 2>/dev/null || true)"
    [[ -n "$PG_DUMP_BIN" ]] || fail "pg_dump is required in production."
    BACKUP_DIR="$(read_env_value DEPLOY_DB_BACKUP_DIR)"
    BACKUP_DIR="${BACKUP_DIR:-/var/backups/portal-distribuidores/production}"
    install -d -m 0750 -o "$APP_USER" -g "$APP_USER" "$BACKUP_DIR"
    BACKUP_FILE="$BACKUP_DIR/${DB_DATABASE_VALUE}-$(date -u '+%Y%m%d-%H%M%S').dump"
    sudo -u "$APP_USER" env PGPASSWORD="$DB_PASSWORD_VALUE" PGSSLMODE="$DB_SSLMODE_VALUE" "$PG_DUMP_BIN" \
        --host="$DB_HOST_VALUE" \
        --port="$DB_PORT_VALUE" \
        --username="$DB_USERNAME_VALUE" \
        --format=custom \
        --file="$BACKUP_FILE" \
        "$DB_DATABASE_VALUE"
    [[ -s "$BACKUP_FILE" ]] || fail "The production backup is empty."
    chmod 0640 "$BACKUP_FILE"
fi

run_as_app "$RELEASE_DIR" "$PHP_BIN" artisan config:clear
run_as_app "$RELEASE_DIR" "$PHP_BIN" artisan route:clear
run_as_app "$RELEASE_DIR" "$PHP_BIN" artisan view:clear
run_as_app "$RELEASE_DIR" "$PHP_BIN" artisan migrate --force --no-interaction
run_as_app "$RELEASE_DIR" "$PHP_BIN" artisan protected-media:migrate --no-interaction
run_as_app "$RELEASE_DIR" "$PHP_BIN" artisan config:cache
run_as_app "$RELEASE_DIR" "$PHP_BIN" artisan route:cache
run_as_app "$RELEASE_DIR" "$PHP_BIN" artisan view:cache

[[ -f "$RELEASE_DIR/public/build/manifest.json" ]] || fail "Vite manifest is missing."

if [[ -L "$CURRENT_LINK" ]]; then
    PREVIOUS_TARGET="$(readlink -f "$CURRENT_LINK" || true)"
elif [[ -e "$CURRENT_LINK" ]]; then
    fail "$CURRENT_LINK must be a symlink."
fi

if [[ "$SIMULATE_FAILURE" == true && "$PREVIOUS_TARGET" == "$RELEASE_DIR" && -f "$SHARED_DIR/deployments/previous-release" ]]; then
    PREVIOUS_TARGET="$(tr -d '\r\n' < "$SHARED_DIR/deployments/previous-release")"
fi

install -o root -g root -m 0644 "$RELEASE_DIR/deploy/$QUEUE_UNIT_NAME" "/etc/systemd/system/$QUEUE_UNIT_NAME"

SCHEDULER_LOG="/var/log/laravel/scheduler-${ENVIRONMENT}.log"
install -d -m 0755 -o "$APP_USER" -g "$APP_USER" /var/log/laravel
touch "$SCHEDULER_LOG"
chown "$APP_USER:$APP_USER" "$SCHEDULER_LOG"
cat > "/etc/cron.d/portal-distribuidores-${ENVIRONMENT}" <<CRON
SHELL=/bin/bash
PATH=$SYSTEM_PATH
* * * * * $APP_USER cd $BASE_DIR/current && $PHP_BIN artisan schedule:run >> $SCHEDULER_LOG 2>&1
CRON
chmod 0644 "/etc/cron.d/portal-distribuidores-${ENVIRONMENT}"

install_nginx_configuration

activate_target "$RELEASE_DIR"
SWITCHED=true
restart_runtime

run_as_app "$RELEASE_DIR" "$PHP_BIN" artisan queue:restart || true
run_as_app "$RELEASE_DIR" "$PHP_BIN" artisan schedule:list | grep -Fq 'contapyme-stock-sync' || fail "ContaPyme schedule is missing."

if [[ "$SIMULATE_FAILURE" == true ]]; then
    [[ -n "$PREVIOUS_TARGET" && "$PREVIOUS_TARGET" != "$RELEASE_DIR" ]] || fail "A previous release is required for a rollback drill."
    fail "Simulated post-switch failure for rollback drill."
fi

verify_health "$RELEASE_DIR"

if [[ -n "$PREVIOUS_TARGET" && "$PREVIOUS_TARGET" != "$RELEASE_DIR" ]]; then
    printf '%s\n' "$PREVIOUS_TARGET" > "$SHARED_DIR/deployments/previous-release"
fi
printf '%s\n' "$RELEASE_DIR" > "$SHARED_DIR/deployments/active-release"
printf '{"environment":"%s","source_commit":"%s","source_tree":"%s","promotion_commit":"%s","archive_sha256":"%s","deployed_at":"%s"}\n' \
    "$ENVIRONMENT" "$SOURCE_SHA" "$MANIFEST_SOURCE_TREE" "$PROMOTION_SHA" \
    "$(awk '{print $1}' "$CHECKSUM_FILE")" "$(date -u '+%Y-%m-%dT%H:%M:%SZ')" \
    >> "$SHARED_DIR/deployments/history.jsonl"

mapfile -t ALL_RELEASES < <(find "$RELEASES_DIR" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %p\n' | sort -nr | cut -d' ' -f2-)
if (( ${#ALL_RELEASES[@]} > 5 )); then
    for old_release in "${ALL_RELEASES[@]:5}"; do
        [[ "$old_release" == "$RELEASE_DIR" || "$old_release" == "$PREVIOUS_TARGET" ]] && continue
        [[ "$old_release" == "$RELEASES_DIR"/* ]] || fail "Refusing to clean an unsafe release path."
        rm -rf "$old_release"
    done
fi

rm -f "$NGINX_BACKUP"
NGINX_CONFIG_CHANGED=false

echo "DEPLOY_RESULT status=success environment=$ENVIRONMENT source_sha=$SOURCE_SHA source_tree=$MANIFEST_SOURCE_TREE promotion_sha=$PROMOTION_SHA previous=${PREVIOUS_TARGET:-none} backup=${BACKUP_FILE:-none}"
