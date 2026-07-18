#!/bin/bash
# Run on Hostinger SSH from anywhere:
#   bash ~/domains/vprint.gr/talent-show/hostinger/setup-public-html.sh
#
# Layout:
#   ~/domains/vprint.gr/talent-show   Laravel app
#   ~/domains/vprint.gr/public_html   web document root

set -euo pipefail

DOMAIN_ROOT="${DOMAIN_ROOT:-${HOME}/domains/vprint.gr}"
APP_DIR="${APP_DIR:-${DOMAIN_ROOT}/talent-show}"
WEB_DIR="${WEB_DIR:-${DOMAIN_ROOT}/public_html}"
PHP_BIN="${PHP_BIN:-/opt/alt/php84/usr/bin/php}"

if [ ! -d "$APP_DIR" ]; then
  echo "ERROR: App not found at $APP_DIR"
  echo "Set APP_DIR=/path/to/talent-show and WEB_DIR=/path/to/public_html"
  exit 1
fi

if [ ! -d "$WEB_DIR" ]; then
  echo "ERROR: public_html not found at $WEB_DIR"
  echo "Set WEB_DIR=/path/to/public_html"
  exit 1
fi

cd "$APP_DIR"
git fetch origin || true
git reset --hard origin/main || true

if [ ! -f "$APP_DIR/public/build/manifest.json" ]; then
  echo "ERROR: missing $APP_DIR/public/build/manifest.json — pull latest main first"
  exit 1
fi

# Backup current public_html once
if [ ! -d "${WEB_DIR}.bak-talent-show" ]; then
  cp -a "$WEB_DIR" "${WEB_DIR}.bak-talent-show"
  echo "Backup: ${WEB_DIR}.bak-talent-show"
fi

# Clear web root (keep hidden backup marker files if any)
find "$WEB_DIR" -mindepth 1 -maxdepth 1 ! -name '.bak*' -exec rm -rf {} +

# Copy Laravel public assets into public_html
cp -a "$APP_DIR/public/." "$WEB_DIR/"

# Overlay Hostinger entrypoint + hardened htaccess
cp -f "$APP_DIR/hostinger/public_html/index.php" "$WEB_DIR/index.php"
cp -f "$APP_DIR/hostinger/public_html/.htaccess" "$WEB_DIR/.htaccess"

# PHP upload limits (must live in document root on Hostinger)
cp -f "$APP_DIR/public/.user.ini" "$WEB_DIR/.user.ini"

# Critical: storage must be a symlink into the Laravel app (not a copied folder).
# Otherwise uploaded videos appear in admin DB but 404 on the monitor.
rm -rf "$WEB_DIR/storage"
ln -sfn "$APP_DIR/storage/app/public" "$WEB_DIR/storage"

rm -rf "$APP_DIR/public/storage"
ln -sfn "$APP_DIR/storage/app/public" "$APP_DIR/public/storage"

# Writable paths for Livewire temp uploads + media
mkdir -p \
  "$APP_DIR/storage/app/public" \
  "$APP_DIR/storage/app/private/livewire-tmp" \
  "$APP_DIR/storage/framework/cache" \
  "$APP_DIR/storage/framework/sessions" \
  "$APP_DIR/storage/framework/views" \
  "$APP_DIR/bootstrap/cache"

chmod 644 "$WEB_DIR/index.php" "$WEB_DIR/.htaccess" "$WEB_DIR/.user.ini" || true
chmod -R u+rwX "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" || true

if [ -x "$PHP_BIN" ]; then
  "$PHP_BIN" artisan storage:link --force || true
  "$PHP_BIN" artisan view:clear || true
  "$PHP_BIN" artisan config:clear || true
else
  echo "WARN: PHP binary not found at $PHP_BIN — skipped artisan cache clear"
fi

echo "OK"
echo "App:  $APP_DIR"
echo "Web:  $WEB_DIR"
echo "Check: $WEB_DIR/index.php"
echo "Check: $WEB_DIR/build/manifest.json"
echo "Check storage symlink:"
ls -la "$WEB_DIR/storage"
echo
echo "Open: https://vprint.gr/admin/login"
echo "Upload tip: hPanel → PHP Configuration → PHP 8.4 + raise upload limits if needed"
