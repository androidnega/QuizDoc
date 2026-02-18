#!/bin/bash

# Build Tailwind CSS
# Run this script whenever you update Tailwind classes in your views

cd "$(dirname "$0")/.."

echo "Building Tailwind CSS..."
./scripts/tailwindcss -i ./resources/css/app.css -o ./public/css/app.css --minify

if [ $? -eq 0 ]; then
    echo "✓ Tailwind CSS built successfully!"
    echo "  Output: public/css/app.css"
else
    echo "✗ Build failed!"
    exit 1
fi
