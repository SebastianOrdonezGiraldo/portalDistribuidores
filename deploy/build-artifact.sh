#!/usr/bin/env bash
# shellcheck disable=SC2016

set -Eeuo pipefail

OUTPUT_DIR="${1:-}"

if [[ -z "$OUTPUT_DIR" ]]; then
    echo "Usage: bash deploy/build-artifact.sh <output-directory>" >&2
    exit 64
fi

for command_name in git php node tar sha256sum; do
    command -v "$command_name" >/dev/null 2>&1 || {
        echo "Required command not found: $command_name" >&2
        exit 1
    }
done

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SOURCE_SHA="$(git -C "$ROOT_DIR" rev-parse HEAD)"
SOURCE_TREE="$(git -C "$ROOT_DIR" rev-parse 'HEAD^{tree}')"
BUILD_TIME="$(date -u '+%Y-%m-%dT%H:%M:%SZ')"
STAGING_DIR="$(mktemp -d)"

cleanup() {
    rm -rf "$STAGING_DIR"
}
trap cleanup EXIT

[[ "$SOURCE_SHA" =~ ^[0-9a-f]{40}$ ]] || {
    echo "HEAD is not a full Git SHA." >&2
    exit 1
}
[[ "$SOURCE_TREE" =~ ^[0-9a-f]{40}$ ]] || {
    echo "HEAD tree is not a full Git SHA." >&2
    exit 1
}
[[ -f "$ROOT_DIR/vendor/autoload.php" ]] || {
    echo "vendor/autoload.php is missing; install production dependencies first." >&2
    exit 1
}
[[ -f "$ROOT_DIR/public/build/manifest.json" ]] || {
    echo "public/build/manifest.json is missing; build frontend assets first." >&2
    exit 1
}

mkdir -p "$OUTPUT_DIR" "$STAGING_DIR/application"

git -C "$ROOT_DIR" archive --format=tar HEAD | tar -xf - -C "$STAGING_DIR/application"
rm -rf \
    "$STAGING_DIR/application/.github" \
    "$STAGING_DIR/application/tests" \
    "$STAGING_DIR/application/storage" \
    "$STAGING_DIR/application/database/database.sqlite" \
    "$STAGING_DIR/application/public/hot"
cp -a "$ROOT_DIR/vendor" "$STAGING_DIR/application/vendor"
mkdir -p "$STAGING_DIR/application/public"
cp -a "$ROOT_DIR/public/build" "$STAGING_DIR/application/public/build"

mkdir -p "$STAGING_DIR/application/bootstrap/cache"
printf '%s\n' "$SOURCE_SHA" > "$STAGING_DIR/application/REVISION"
printf '%s\n' "$SOURCE_TREE" > "$STAGING_DIR/application/TREE"

php -r '
    $manifest = [
        "schema" => 1,
        "artifact" => "portal-".$argv[2],
        "source_commit" => $argv[2],
        "source_tree" => $argv[3],
        "php_version" => PHP_VERSION,
        "node_version" => $argv[4],
        "built_at" => $argv[5],
    ];
    file_put_contents($argv[1], json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
' "$STAGING_DIR/application/manifest.json" "$SOURCE_SHA" "$SOURCE_TREE" "$(node --version)" "$BUILD_TIME"

rm -f "$OUTPUT_DIR/portal.tar.gz" "$OUTPUT_DIR/manifest.json" "$OUTPUT_DIR/SHA256SUMS"
tar -C "$STAGING_DIR/application" -czf "$OUTPUT_DIR/portal.tar.gz" .
cp "$STAGING_DIR/application/manifest.json" "$OUTPUT_DIR/manifest.json"
(
    cd "$OUTPUT_DIR"
    sha256sum portal.tar.gz > SHA256SUMS
    sha256sum --check SHA256SUMS
)

if [[ -n "${GITHUB_OUTPUT:-}" ]]; then
    {
        echo "artifact_name=portal-${SOURCE_SHA}"
        echo "source_sha=${SOURCE_SHA}"
        echo "source_tree=${SOURCE_TREE}"
        echo "archive_sha256=$(awk '{print $1}' "$OUTPUT_DIR/SHA256SUMS")"
    } >> "$GITHUB_OUTPUT"
fi

echo "Built portal-${SOURCE_SHA} (${SOURCE_TREE}) in ${OUTPUT_DIR}"
