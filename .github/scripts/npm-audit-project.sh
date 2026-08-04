#!/usr/bin/env bash
# Audit only packages present in the committed package-lock.json.
# GitHub runners may inject extraneous tooling (e.g. @openai/codex-security)
# into the workspace; those must not fail this project's dependency gate.
set -euo pipefail

audit_dir="${RUNNER_TEMP:-/tmp}/npm-project-audit"
rm -rf "$audit_dir"
mkdir -p "$audit_dir"

git show HEAD:package.json >"$audit_dir/package.json"
git show HEAD:package-lock.json >"$audit_dir/package-lock.json"

cd "$audit_dir"

# npm exits non-zero when vulnerabilities exist; capture JSON either way.
set +e
npm audit --json --package-lock-only >audit.json 2>/dev/null
set -e

node <<'NODE'
const fs = require('fs');

const lock = JSON.parse(fs.readFileSync('package-lock.json', 'utf8'));
const lockNames = new Set();

for (const key of Object.keys(lock.packages || {})) {
  if (!key) {
    continue;
  }

  const parts = key.split('node_modules/');
  const name = parts[parts.length - 1];

  if (name) {
    lockNames.add(name);
  }
}

for (const name of Object.keys(lock.dependencies || {})) {
  lockNames.add(name);
}

let report;

try {
  report = JSON.parse(fs.readFileSync('audit.json', 'utf8'));
} catch (error) {
  console.error('npm audit did not produce valid JSON.');
  process.exit(1);
}

if (report.error && !report.vulnerabilities) {
  console.error(report.error);
  process.exit(1);
}

const blocking = [];

for (const [name, meta] of Object.entries(report.vulnerabilities || {})) {
  const severity = meta.severity;

  if (severity !== 'high' && severity !== 'critical') {
    continue;
  }

  if (!lockNames.has(name)) {
    console.log(`Ignoring non-project advisory: ${name} (${severity})`);
    continue;
  }

  blocking.push({
    name,
    severity,
    via: meta.via,
    range: meta.range,
  });
}

if (blocking.length > 0) {
  console.error('High/critical vulnerabilities in project lockfile:');
  console.error(JSON.stringify(blocking, null, 2));
  process.exit(1);
}

console.log('npm audit: no high/critical vulnerabilities in project lockfile.');
process.exit(0);
NODE
