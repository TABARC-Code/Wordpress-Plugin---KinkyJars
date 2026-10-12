#!/usr/bin/env bash
# Builds the installable zips from the kink-jars/ folder:
#   dist/kink-jars.zip          always the same name, so "latest" download links never change
#   dist/kink-jars-<version>.zip the same file with its version in the name, for keeping
set -euo pipefail
cd "$(dirname "$0")/.."
version=$(grep -oE '^ \* Version: +[0-9]+\.[0-9]+\.[0-9]+' kink-jars/kink-jars.php | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')
[ -n "$version" ] || { echo "Couldn't read Version from kink-jars/kink-jars.php" >&2; exit 1; }
mkdir -p dist
rm -f dist/kink-jars.zip "dist/kink-jars-$version.zip"
zip -qr -X dist/kink-jars.zip kink-jars -x '*.DS_Store'
cp dist/kink-jars.zip "dist/kink-jars-$version.zip"
echo "dist/kink-jars.zip"
echo "dist/kink-jars-$version.zip"
