#!/usr/bin/env bash
# Checks the plugin is consistent and builds into a zip that WordPress will accept.
# Usage: bash tests/check-package.sh
set -euo pipefail
cd "$(dirname "$0")/.."
fail() { echo "FAIL $*"; exit 1; }

for f in $(find kink-jars -name '*.php'); do php -l "$f" >/dev/null || fail "PHP syntax in $f"; done
echo "ok   every PHP file parses"

header=$(grep -oE '^ \* Version: +[0-9.]+' kink-jars/kink-jars.php | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')
const=$(grep -oE "define\( 'KINK_JARS_VERSION', '[0-9.]+'" kink-jars/kink-jars.php | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')
stable=$(grep -oE '^Stable tag: [0-9.]+' kink-jars/readme.txt | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')
changelog=$(grep -oE '^= [0-9]+\.[0-9]+\.[0-9]+ =' kink-jars/readme.txt | head -1 | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')
[ "$header" = "$const" ] && [ "$header" = "$stable" ] && [ "$header" = "$changelog" ] \
  || fail "versions disagree: header $header, constant $const, stable tag $stable, newest changelog $changelog"
echo "ok   version $header agrees in header, constant, stable tag and changelog"

grep -qE 'const VERSION = "[0-9]+\.[0-9]+\.[0-9]+"' kink-jars/app/index.html || fail "bundled app has no VERSION"
for f in oswald.woff2 nunito.woff2 permanent-marker.woff2 atkinson-hyperlegible-400.woff2 atkinson-hyperlegible-700.woff2; do
  [ -s "kink-jars/app/fonts/$f" ] || fail "missing font $f"
done
echo "ok   app and all five fonts are bundled"

bash scripts/build-zip.sh >/dev/null
list=$(unzip -Z1 dist/kink-jars.zip)
[ "$(echo "$list" | cut -d/ -f1 | sort -u)" = "kink-jars" ] || fail "zip must contain exactly one top folder, kink-jars/"
echo "$list" | grep -qx 'kink-jars/kink-jars.php' || fail "plugin file not at kink-jars/kink-jars.php"
if echo "$list" | grep -qE '(^|/)(tests|scripts|\.github|node_modules|dist)/'; then fail "development files leaked into the zip"; fi
cmp -s dist/kink-jars.zip "dist/kink-jars-$header.zip" || fail "versioned zip differs"
echo "ok   zip has one kink-jars/ folder, the plugin file where WordPress expects it, and no dev files"

out=$(mktemp -d); trap 'rm -rf "$out"' EXIT
unzip -q dist/kink-jars.zip -d "$out"
php tests/unit.php "$out/kink-jars" >/dev/null || fail "unit checks fail on the zipped copy"
echo "ok   unit checks pass on the zipped copy"
