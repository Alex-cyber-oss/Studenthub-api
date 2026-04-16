#!/bin/bash
set -e

echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader

echo "Running migrations..."
php artisan migrate --force

echo "Generating app key if needed..."
php artisan key:generate --force

echo "Optimizing application..."
php artisan config:cache
php artisan route:cache

echo "Build completed successfully!"
