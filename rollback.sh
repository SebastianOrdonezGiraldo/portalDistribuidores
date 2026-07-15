#!/usr/bin/env bash
# shellcheck disable=SC2016

set -Eeuo pipefail

APP_USER="www-data"
ENVIRONMENT=""
BASE_DIR=""
TARGET_SHA=""

while [[ "$#" -gt 0 ]]; do
    case "$1" in
        --environment) ENVIRONMENT="${2:-}"; shift 2 ;;
        --base-dir) BASE_DIR="${2:-}"; shift 2 ;;
        --to) TARGET_SHA="${2:-}"; shift 2 ;;
        *) echo "Unknown argument: $1" >&2; exit 64 ;;
    esac
done

[[ "$(id -u)" -eq 0 ]] || { echo "rollback.sh must run as root." >&2; exit 1; }
[[ "$ENVIRONMENT" == "staging" || "$ENVIRONMENT" == "production" ]] || { echo "Invalid environment." >&2; exit 64; }

case "$ENVIRONMENT" in
    production)
        EXPECTED_BASE_DIR="/var/www/portalDistribuidores"
        QUEUE_SERVICE="laravel-queue"
        ;;
    staging)
        EXPECTED_BASE_DIR="/var/www/portalDistribuidores-staging"
        QUEUE_SERVICE="laravel-queue-staging"
        ;;
esac

BASE_DIR="${BASE_DIR:-$EXPECTED_BASE_DIR}"
[[ "$BASE_DIR" == "$EXPECTED_BASE_DIR" ]] || { echo "Unexpected base directory for $ENVIRONMENT." >&2; exit 1; }

BASE_DIR="$(realpath -m "$BASE_DIR")"
CURRENT_LINK="$BASE_DIR/current"
RELEASES_DIR="$BASE_DIR/releases"
SHARED_DIR="$BASE_DIR/shared"
PHP_BIN="$(command -v php8.3 2>/dev/null || command -v php 2>/dev/null || true)"
APP_HOME="$(getent passwd "$APP_USER" | cut -d: -f6 || true)"

[[ -n "$PHP_BIN" && -n "$APP_HOME" ]] || { echo "Runtime prerequisites are missing." >&2; exit 1; }
[[ -L "$CURRENT_LINK" ]] || { echo "$CURRENT_LINK is not an atomic symlink." >&2; exit 1; }

CURRENT_TARGET="$(readlink -f "$CURRENT_LINK")"
if [[ -n "$TARGET_SHA" ]]; then
    [[ "$TARGET_SHA" =~ ^[0-9a-f]{40}$ ]] || { echo "--to must be a full Git SHA." >&2; exit 64; }
    TARGET_RELEASE="$RELEASES_DIR/$TARGET_SHA"
else
    [[ -f "$SHARED_DIR/deployments/previous-release" ]] || { echo "No previous release was recorded." >&2; exit 1; }
    TARGET_RELEASE="$(tr -d '\r\n' < "$SHARED_DIR/deployments/previous-release")"
fi

TARGET_RELEASE="$(realpath "$TARGET_RELEASE")"
[[ -d "$TARGET_RELEASE" ]] || { echo "Rollback target does not exist." >&2; exit 1; }
if [[ "$TARGET_RELEASE" != "$BASE_DIR" && "$TARGET_RELEASE" != "$RELEASES_DIR"/* ]]; then
    echo "Rollback target is outside the approved release layout." >&2
    exit 1
fi
[[ "$TARGET_RELEASE" != "$CURRENT_TARGET" ]] || { echo "Rollback target is already active." >&2; exit 1; }

TEMPORARY_LINK="$BASE_DIR/.current-rollback-$$"
RESPONSE_FILE=""
SWITCHED=false

cleanup() {
    rm -f "$TEMPORARY_LINK"
    [[ -z "$RESPONSE_FILE" ]] || rm -f "$RESPONSE_FILE"
}
trap cleanup EXIT

activate_target() {
    local target="$1"
    rm -f "$TEMPORARY_LINK"
    ln -s "$target" "$TEMPORARY_LINK"
    mv -Tf "$TEMPORARY_LINK" "$CURRENT_LINK"
}

restart_runtime() {
    systemctl restart "$QUEUE_SERVICE.service"
    systemctl is-active --quiet "$QUEUE_SERVICE.service"
    systemctl is-active --quiet php8.3-fpm.service
    systemctl reload php8.3-fpm.service
    systemctl is-active --quiet cron.service
}

APP_URL="$(grep -E '^APP_URL=' "$SHARED_DIR/.env" | head -n1 | cut -d= -f2- | tr -d '\"\r' || true)"
[[ "$APP_URL" == https://* ]] || { echo "APP_URL must use HTTPS." >&2; exit 1; }
HOST="$("$PHP_BIN" -r '$host = parse_url($argv[1], PHP_URL_HOST); if (!is_string($host) || $host === "") { exit(2); } echo $host;' "$APP_URL")"

verify_target() {
    local target="$1"

    if [[ -f "$target/REVISION" && -f "$target/TREE" ]]; then
        local expected_revision expected_tree
        expected_revision="$(tr -d '\r\n' < "$target/REVISION")"
        expected_tree="$(tr -d '\r\n' < "$target/TREE")"
        RESPONSE_FILE="$(mktemp)"
        curl --fail --silent --show-error --max-time 30 \
            --resolve "${HOST}:443:127.0.0.1" \
            --output "$RESPONSE_FILE" \
            "${APP_URL%/}/up"
        "$PHP_BIN" -r '
            $payload = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
            if (($payload["status"] ?? null) !== "ok" || ($payload["revision"] ?? null) !== $argv[2] || ($payload["tree"] ?? null) !== $argv[3]) { exit(1); }
        ' "$RESPONSE_FILE" "$expected_revision" "$expected_tree"
        rm -f "$RESPONSE_FILE"
        RESPONSE_FILE=""
    else
        curl --fail --silent --show-error --location --max-time 30 \
            --resolve "${HOST}:443:127.0.0.1" \
            --output /dev/null \
            "$APP_URL"
    fi
}

restore_failed_rollback() {
    local original_status="$1"
    local line_number="$2"
    trap - ERR
    echo "ROLLBACK_RESULT status=failure target=$TARGET_RELEASE line=$line_number" >&2

    if [[ "$SWITCHED" == true ]]; then
        if activate_target "$CURRENT_TARGET" && restart_runtime && verify_target "$CURRENT_TARGET"; then
            echo "ROLLBACK_RECOVERY status=success restored=$CURRENT_TARGET database_action=none" >&2
        else
            echo "ROLLBACK_RECOVERY status=failure restored=$CURRENT_TARGET database_action=none" >&2
        fi
    fi

    exit "$original_status"
}
trap 'restore_failed_rollback $? $LINENO' ERR

activate_target "$TARGET_RELEASE"
SWITCHED=true
restart_runtime
verify_target "$TARGET_RELEASE"

printf '%s\n' "$CURRENT_TARGET" > "$SHARED_DIR/deployments/previous-release"
printf '%s\n' "$TARGET_RELEASE" > "$SHARED_DIR/deployments/active-release"
printf '{"environment":"%s","rollback_from":"%s","rollback_to":"%s","rolled_back_at":"%s"}\n' \
    "$ENVIRONMENT" "$CURRENT_TARGET" "$TARGET_RELEASE" "$(date -u '+%Y-%m-%dT%H:%M:%SZ')" \
    >> "$SHARED_DIR/deployments/history.jsonl"

trap - ERR
echo "ROLLBACK_RESULT status=success target=$TARGET_RELEASE previous=$CURRENT_TARGET database_action=none"
