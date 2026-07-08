#!/bin/bash

set -e

PROJECT_DIR="/home/jyddjslu/public_html/kasi.wizards.co.in"

echo "========== Deployment Started =========="

cd $PROJECT_DIR

echo "Pull latest code..."
git pull origin main

echo "Install Composer..."
composer install --no-dev --prefer-dist --optimize-autoloader

echo "Run migrations..."
php artisan migrate --force

echo "Update asset version..."
VERSION=$(git rev-parse --short HEAD)

if grep -q "^ASSET_VERSION=" .env; then
    sed -i "s/^ASSET_VERSION=.*/ASSET_VERSION=$VERSION/" .env
else
    echo "ASSET_VERSION=$VERSION" >> .env
fi

echo "Clear cache..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Deployment completed successfully."
echo "Version: $VERSION"