#!/usr/bin/env bash
# Clear Laravel caches on LIVE server (run after deploy or .env changes).
# On server: upload this file, then run:  bash clear-cache-live.sh
# Or:  cd /path/to/quizsnap && bash clear-cache-live.sh

set -e
cd "$(dirname "$0")"

# Use PHP from PATH, or set PHP=/path/to/php if needed (e.g. cPanel)
PHP="${PHP:-php}"

echo "Clearing Laravel caches..."
$PHP artisan config:clear
$PHP artisan route:clear
$PHP artisan cache:clear
$PHP artisan view:clear

echo "Done. Caches cleared."
