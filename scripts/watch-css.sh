#!/bin/bash

# Watch Tailwind CSS changes
# Run this script during development for automatic CSS rebuilds

cd "$(dirname "$0")/.."

echo "Watching Tailwind CSS for changes..."
echo "Press Ctrl+C to stop"
echo ""

./scripts/tailwindcss -i ./resources/css/app.css -o ./public/css/app.css --watch
