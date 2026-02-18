#!/usr/bin/env bash
# Clear Laravel config, route, and cache (run after deploy or .env changes).
# Usage: ./clear-cache.sh   or   bash clear-cache.sh

set -e
cd "$(dirname "$0")"

PHP="${PHP:-php}"
echo "Using: $($PHP -v 2>/dev/null | head -1)"

echo "Clearing config cache..."
$PHP artisan config:clear

echo "Clearing route cache..."
$PHP artisan route:clear

echo "Clearing application cache..."
$PHP artisan cache:clear

echo "Done."
