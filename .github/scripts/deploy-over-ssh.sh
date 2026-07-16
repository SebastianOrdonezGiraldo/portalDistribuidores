#!/usr/bin/env bash
# shellcheck disable=SC2016,SC2029

set -Eeuo pipefail

for variable_name in DEPLOY_ENVIRONMENT DEPLOY_BASE_DIR DEPLOY_URL SOURCE_SHA PROMOTION_SHA ARTIFACT_DIR SSH_HOST SSH_PORT SSH_USER SSH_KEY_PATH SSH_KNOWN_HOSTS_PATH; do
    [[ -n "${!variable_name:-}" ]] || {
        echo "$variable_name is required." >&2
        exit 64
    }
done

[[ "$DEPLOY_ENVIRONMENT" == "staging" || "$DEPLOY_ENVIRONMENT" == "production" ]] || {
    echo "DEPLOY_ENVIRONMENT must be staging or production." >&2
    exit 64
}
[[ "$SOURCE_SHA" =~ ^[0-9a-f]{40}$ ]] || {
    echo "SOURCE_SHA must be a full Git SHA." >&2
    exit 64
}
[[ "$PROMOTION_SHA" =~ ^[0-9a-f]{40}$ ]] || {
    echo "PROMOTION_SHA must be a full Git SHA." >&2
    exit 64
}
[[ "$SSH_USER" =~ ^[a-z_][a-z0-9_-]*$ ]] || {
    echo "SSH_USER has an unsafe value." >&2
    exit 64
}

for file_name in portal.tar.gz manifest.json SHA256SUMS; do
    [[ -f "$ARTIFACT_DIR/$file_name" ]] || {
        echo "$ARTIFACT_DIR/$file_name is missing." >&2
        exit 1
    }
done

(
    cd "$ARTIFACT_DIR"
    sha256sum --check SHA256SUMS
)

ARTIFACT_SHA256="$(awk '{print $1}' "$ARTIFACT_DIR/SHA256SUMS")"
SOURCE_TREE="$(php -r '
    $manifest = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
    if (($manifest["source_commit"] ?? null) !== $argv[2] || !preg_match("/^[0-9a-f]{40}$/", $manifest["source_tree"] ?? "")) { exit(1); }
    echo $manifest["source_tree"];
' "$ARTIFACT_DIR/manifest.json" "$SOURCE_SHA")"

SSH_OPTIONS=(
    -i "$SSH_KEY_PATH"
    -p "$SSH_PORT"
    -o BatchMode=yes
    -o IdentitiesOnly=yes
    -o StrictHostKeyChecking=yes
    -o "UserKnownHostsFile=$SSH_KNOWN_HOSTS_PATH"
    -o ConnectTimeout=20
)
SCP_OPTIONS=(
    -i "$SSH_KEY_PATH"
    -P "$SSH_PORT"
    -o BatchMode=yes
    -o IdentitiesOnly=yes
    -o StrictHostKeyChecking=yes
    -o "UserKnownHostsFile=$SSH_KNOWN_HOSTS_PATH"
    -o ConnectTimeout=20
)
REMOTE_DIR="/tmp/portal-deploy-${SOURCE_SHA}"
REMOTE_TARGET="${SSH_USER}@${SSH_HOST}"
HTML_FILE=""

cleanup() {
    [[ -z "$HTML_FILE" ]] || rm -f "$HTML_FILE"
    ssh "${SSH_OPTIONS[@]}" "$REMOTE_TARGET" "rm -rf '$REMOTE_DIR'" >/dev/null 2>&1 || true
}
trap cleanup EXIT

ssh "${SSH_OPTIONS[@]}" "$REMOTE_TARGET" "mkdir -p '$REMOTE_DIR' && chmod 700 '$REMOTE_DIR'"
scp "${SCP_OPTIONS[@]}" \
    "$ARTIFACT_DIR/portal.tar.gz" \
    "$ARTIFACT_DIR/manifest.json" \
    "$ARTIFACT_DIR/SHA256SUMS" \
    deploy.sh \
    rollback.sh \
    "$REMOTE_TARGET:$REMOTE_DIR/"

run_remote_deploy() {
    local simulate_failure="$1"
    local remote_command
    local simulate_argument=""

    if [[ "$simulate_failure" == "true" ]]; then
        simulate_argument=" --simulate-failure-after-switch"
    fi

    printf -v remote_command \
        "sudo bash %q deploy --environment %q --base-dir %q --artifact %q --source-sha %q --promotion-sha %q%s" \
        "$REMOTE_DIR/deploy.sh" \
        "$DEPLOY_ENVIRONMENT" \
        "$DEPLOY_BASE_DIR" \
        "$REMOTE_DIR/portal.tar.gz" \
        "$SOURCE_SHA" \
        "$PROMOTION_SHA" \
        "$simulate_argument"

    ssh "${SSH_OPTIONS[@]}" "$REMOTE_TARGET" "$remote_command"
}

ROLLBACK_DRILL_RESULT="not_run"
if [[ "${ROLLBACK_DRILL:-false}" == "true" ]]; then
    [[ "$DEPLOY_ENVIRONMENT" == "staging" ]] || {
        echo "Rollback drills are only allowed in staging." >&2
        exit 1
    }

    set +e
    run_remote_deploy true 2>&1 | tee rollback-drill.log
    drill_status=${PIPESTATUS[0]}
    set -e

    if [[ "$drill_status" -eq 0 ]]; then
        echo "The rollback drill unexpectedly returned success." >&2
        exit 1
    fi

    grep -Fq 'ROLLBACK_RESULT status=success' rollback-drill.log || {
        echo "The simulated failure did not confirm a healthy rollback." >&2
        exit 1
    }

    ROLLBACK_DRILL_RESULT="success"
    echo "Rollback drill succeeded; redeploying the candidate normally."
fi

run_remote_deploy false 2>&1 | tee deploy.log
DEPLOY_RESULT_LINE="$(grep -F 'DEPLOY_RESULT status=success' deploy.log | tail -n 1 || true)"
[[ -n "$DEPLOY_RESULT_LINE" ]] || {
    echo "The remote deployment did not emit a success result." >&2
    exit 1
}

result_value() {
    local key="$1"
    tr ' ' '\n' <<< "$DEPLOY_RESULT_LINE" | awk -F= -v key="$key" '$1 == key { print substr($0, length(key) + 2); exit }'
}

PREVIOUS_RELEASE="$(result_value previous)"
BACKUP_FILE="$(result_value backup)"
HTML_FILE="$(mktemp)"

curl --fail --silent --show-error --location \
    --proto '=https' \
    --max-redirs 3 \
    --max-time 30 \
    --output "$HTML_FILE" \
    "$DEPLOY_URL"

ASSET_PATH="$(grep -Eo '/build/[^"'"'"' ?]+' "$HTML_FILE" | head -n 1 || true)"
[[ -n "$ASSET_PATH" ]] || {
    echo "The deployed page did not reference a versioned /build asset." >&2
    exit 1
}

ORIGIN="$(php -r '$parts = parse_url($argv[1]); echo $parts["scheme"]."://".$parts["host"].(isset($parts["port"]) ? ":".$parts["port"] : "");' "$DEPLOY_URL")"
curl --fail --silent --show-error --location \
    --proto '=https' \
    --max-redirs 3 \
    --max-time 30 \
    --output /dev/null \
    "${ORIGIN}${ASSET_PATH}"

if [[ -n "${GITHUB_STEP_SUMMARY:-}" ]]; then
    {
        echo "### Deployment ${DEPLOY_ENVIRONMENT}"
        echo
        echo "- Source commit: \`${SOURCE_SHA}\`"
        echo "- Source tree: \`${SOURCE_TREE}\`"
        echo "- Promotion commit: \`${PROMOTION_SHA}\`"
        echo "- Artifact SHA-256: \`${ARTIFACT_SHA256}\`"
        echo "- Previous release: \`${PREVIOUS_RELEASE:-none}\`"
        echo "- New release: \`${SOURCE_SHA}\`"
        echo "- Database backup: \`${BACKUP_FILE:-none}\`"
        echo "- URL: ${DEPLOY_URL}"
        echo "- Internal /up, PHP-FPM, queue, cron and ContaPyme schedule: healthy"
        echo "- Rollback drill: \`${ROLLBACK_DRILL_RESULT}\`"
        echo "- External page and versioned asset: healthy"
    } >> "$GITHUB_STEP_SUMMARY"
fi
