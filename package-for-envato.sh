#!/usr/bin/env bash
#
# Assembles the CodeCanyon submission zip in the structure Envato's
# reviewers expect: a top-level folder per item, each containing its own
# purpose (documentation / main application files / licensing), so nothing
# buyer-facing is dumped loose in the project root.
#
# Run this ONCE you have already run `composer install --no-dev
# --optimize-autoloader` for real (on your own machine or a VPS with
# internet access — this repo's dev environment could not reach Packagist,
# so `vendor/` has never actually been built here). Shipping `vendor/`
# pre-built matters: most CodeCanyon buyers are on shared hosting with no
# SSH/Composer access, so if they can't run composer themselves the app
# (including the web installer) will never boot for them.
#
# Usage: ./package-for-envato.sh [version]
#   e.g. ./package-for-envato.sh 1.1.0
#
set -euo pipefail

VERSION="${1:-$(grep -m1 '^## ' CHANGELOG.md | sed 's/## //')}"
DIST="dist/restaurant-pos-${VERSION}"

echo "Packaging Restaurant POS v${VERSION} for CodeCanyon submission..."

rm -rf "$DIST"
mkdir -p "$DIST/documentation" "$DIST/licensing" "$DIST/main-files"

# 1. Documentation — must be readable outside the zip too (Envato requires
#    it to also be hosted publicly online, not just bundled here).
cp docs/documentation.html "$DIST/documentation/"

# 2. Licensing
cp LICENSE.txt "$DIST/licensing/"

# 3. Main application files — everything a buyer needs to run the app,
#    minus dev-only artifacts that have no place on a live server.
rsync -a --exclude='.git' \
         --exclude='.github' \
         --exclude='tests' \
         --exclude='docs' \
         --exclude='dist' \
         --exclude='node_modules' \
         --exclude='.env' \
         --exclude='storage/installed.lock' \
         --exclude='storage/logs/*.log' \
         --exclude='package-for-envato.sh' \
         --exclude='phpunit.xml' \
         --exclude='.editorconfig' \
         ./ "$DIST/main-files/restaurant-pos/"

if [ ! -d "$DIST/main-files/restaurant-pos/vendor" ]; then
  echo
  echo "WARNING: vendor/ was not found — run 'composer install --no-dev"
  echo "--optimize-autoloader' first, from an environment with real internet"
  echo "access, before packaging. Without it, buyers with no CLI/SSH access"
  echo "on shared hosting will not be able to run this at all."
  echo
fi

cd dist
zip -rq "restaurant-pos-${VERSION}.zip" "restaurant-pos-${VERSION}"
cd ..

echo "Done: dist/restaurant-pos-${VERSION}.zip"
echo "  documentation/documentation.html"
echo "  licensing/LICENSE.txt"
echo "  main-files/restaurant-pos/  (the app itself)"
