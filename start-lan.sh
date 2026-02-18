#!/usr/bin/env bash
# Start the Laravel development server bound to the current LAN IP so other
# devices on the network can access it.
# Usage: ./start-lan.sh

set -e
cd "$(dirname "$0")"

PHP_BIN="${PHP:-php}"

# If PHP is not in PATH, fall back to XAMPP's PHP.
if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  if [ -x "/Applications/XAMPP/xamppfiles/bin/php" ]; then
    PHP_BIN="/Applications/XAMPP/xamppfiles/bin/php"
  else
    echo "PHP binary not found."
    echo "Install PHP or set the PHP environment variable to the PHP executable path."
    exit 1
  fi
fi

# Try common network interfaces (Wi-Fi / Ethernet) to get an IPv4 address.
LAN_IP="$(ipconfig getifaddr en0 2>/dev/null || ipconfig getifaddr en1 2>/dev/null || ipconfig getifaddr en2 2>/dev/null || true)"

if [ -z "$LAN_IP" ]; then
  echo "Could not automatically detect LAN IP address."
  echo "You can find it manually in System Settings → Network, then run:"
  echo "  $PHP_BIN artisan serve --host=YOUR_IP --port=8000"
  exit 1
fi

echo "Using PHP: $($PHP_BIN -v 2>/dev/null | head -n 1)"
echo "Detected LAN IP: $LAN_IP"
echo "Starting Laravel dev server on http://$LAN_IP:8000 ..."

$PHP_BIN artisan serve --host="$LAN_IP" --port=8000

