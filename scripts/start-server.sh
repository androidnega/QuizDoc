#!/usr/bin/env bash
# Start Laravel development server from Mac terminal.
# Run from project root: ./scripts/start-server.sh
# Or from anywhere: /path/to/QuizSnap/scripts/start-server.sh

cd "$(dirname "$0")/.." || exit 1

# Use PHP from PATH, or XAMPP's PHP on Mac
if command -v php &>/dev/null; then
  PHP=php
elif [ -x /Applications/XAMPP/xamppfiles/bin/php ]; then
  PHP=/Applications/XAMPP/xamppfiles/bin/php
else
  echo "PHP not found. Install PHP or set PATH to include XAMPP's php (e.g. /Applications/XAMPP/xamppfiles/bin)."
  exit 1
fi

echo "Starting Laravel development server (PHP: $PHP)..."
echo "Stop with Ctrl+C."
echo ""

exec "$PHP" artisan serve
