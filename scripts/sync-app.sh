#!/usr/bin/env bash
# Pulls the latest Kink Jars app (index.html and fonts) from the main app repository into
# kink-jars/app/. Run it when the app has a new version, then bump the plugin version and
# add a changelog line. Usage: bash scripts/sync-app.sh [git ref, default main]
set -euo pipefail
cd "$(dirname "$0")/.."
ref="${1:-main}"
tmp=$(mktemp -d); trap 'rm -rf "$tmp"' EXIT
git clone -q --depth 1 --branch "$ref" https://github.com/TABARC-Code/kinky-jars "$tmp/app"
rm -rf kink-jars/app
mkdir -p kink-jars/app
cp "$tmp/app/index.html" kink-jars/app/
cp -R "$tmp/app/fonts" kink-jars/app/fonts
echo "App synced: $(grep -oE 'const VERSION = "[0-9.]+"' kink-jars/app/index.html)"
