#!/bin/bash
# Run on Hostinger SSH from anywhere:
#   bash ~/domains/vprint.gr/talent-show/hostinger/setup-public-html.sh

set -euo pipefail

DOMAIN_ROOT="${HOME}/domains/vprint.gr"
APP_DIR="${DOMAIN_ROOT}/talent-show"
WEB_DIR="${DOMAIN_ROOT}/public_html"

if [ ! -d "$APP_DIR" ]; then
  echo "ERROR: App not found at $APP_DIR"
  exit 1
fi

if [ ! -d "$WEB_DIR" ]; then
  echo "ERROR: public_html not found at $WEB_DIR"
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

# Overlay Hostinger entrypoint
cp -f "$APP_DIR/hostinger/public_html/index.php" "$WEB_DIR/index.php"
cp -f "$APP_DIR/hostinger/public_html/.htaccess" "$WEB_DIR/.htaccess"

# Permissions
chmod 644 "$WEB_DIR/index.php" "$WEB_DIR/.htaccess" || true
chmod -R u+rwX "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" || true

echo "OK"
echo "Check: $WEB_DIR/index.php"
echo "Check: $WEB_DIR/build/manifest.json"
ls -la "$WEB_DIR/index.php" "$WEB_DIR/.htaccess" "$WEB_DIR/build/manifest.json"
echo
echo "Open: https://vprint.gr/admin/login"
