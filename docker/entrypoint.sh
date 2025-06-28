#!/bin/bash

set -e

echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

echo "🔐 Generating app key..."
php artisan key:generate || true

echo "🧬 Running migrations..."
php artisan migrate || true

echo "⚙️ Building assets with Vite..."
npm install --legacy-peer-deps
npm run build
