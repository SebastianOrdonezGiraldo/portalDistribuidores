#!/usr/bin/env bash

set -Eeuo pipefail

: "${SSH_HOST:?SSH_HOST is required}"
: "${SSH_PORT:?SSH_PORT is required}"
: "${SSH_PRIVATE_KEY:?SSH_PRIVATE_KEY is required}"
: "${SSH_EXPECTED_FINGERPRINT:?SSH_EXPECTED_FINGERPRINT is required}"
: "${RUNNER_TEMP:?RUNNER_TEMP is required}"
: "${GITHUB_OUTPUT:?GITHUB_OUTPUT is required}"

[[ "$SSH_PORT" =~ ^[0-9]{1,5}$ ]] || {
    echo "SSH_PORT must be numeric." >&2
    exit 1
}

SSH_DIR="$RUNNER_TEMP/portal-ssh"
KEY_PATH="$SSH_DIR/deploy_key"
KNOWN_HOSTS_PATH="$SSH_DIR/known_hosts"
SCANNED_KEYS_PATH="$SSH_DIR/scanned_keys"
EXPECTED_PATH="$SSH_DIR/expected_fingerprints"
ACTUAL_PATH="$SSH_DIR/actual_fingerprints"
SINGLE_KEY_PATH="$SSH_DIR/single_key"

install -d -m 0700 "$SSH_DIR"
printf '%s\n' "$SSH_PRIVATE_KEY" | tr -d '\r' > "$KEY_PATH"
chmod 0600 "$KEY_PATH"

ssh-keygen -y -f "$KEY_PATH" >/dev/null 2>&1 || {
    echo "The deploy key cannot be read non-interactively. Use a dedicated key without a passphrase." >&2
    exit 1
}

ssh-keyscan -T 15 -p "$SSH_PORT" "$SSH_HOST" 2>/dev/null > "$SCANNED_KEYS_PATH"
[[ -s "$SCANNED_KEYS_PATH" ]] || {
    echo "No SSH host keys were returned by ${SSH_HOST}:${SSH_PORT}." >&2
    exit 1
}

printf '%s\n' "$SSH_EXPECTED_FINGERPRINT" \
    | sed 's/[,; ]/\n/g' \
    | tr -d '\r' \
    | sed '/^$/d' \
    | sort -u > "$EXPECTED_PATH"

: > "$ACTUAL_PATH"
: > "$KNOWN_HOSTS_PATH"
while IFS= read -r scanned_key; do
    printf '%s\n' "$scanned_key" > "$SINGLE_KEY_PATH"
    fingerprint="$(ssh-keygen -lf "$SINGLE_KEY_PATH" -E sha256 | awk '{print $2}')"
    printf '%s\n' "$fingerprint" >> "$ACTUAL_PATH"

    if grep -Fqx "$fingerprint" "$EXPECTED_PATH"; then
        printf '%s\n' "$scanned_key" >> "$KNOWN_HOSTS_PATH"
    fi
done < "$SCANNED_KEYS_PATH"
sort -u -o "$ACTUAL_PATH" "$ACTUAL_PATH"
sort -u -o "$KNOWN_HOSTS_PATH" "$KNOWN_HOSTS_PATH"

if [[ ! -s "$KNOWN_HOSTS_PATH" ]]; then
    echo "SSH fingerprint mismatch for ${SSH_HOST}:${SSH_PORT}." >&2
    echo "Received fingerprints:" >&2
    sed 's/^/  /' "$ACTUAL_PATH" >&2
    exit 1
fi

chmod 0600 "$KNOWN_HOSTS_PATH"

{
    echo "key_path=${KEY_PATH}"
    echo "known_hosts_path=${KNOWN_HOSTS_PATH}"
} >> "$GITHUB_OUTPUT"

echo "Verified SSH host identity for ${SSH_HOST}:${SSH_PORT}."
