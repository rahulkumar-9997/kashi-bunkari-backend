#!/bin/bash

set -e

PROJECT="/home/jyddjslu/public_html/kasi.wizards.co.in"

echo "===================================="
echo "Laravel Deployment Started"
echo "===================================="

cd $PROJECT

echo "Pulling latest code..."
git pull origin main

echo "Installing composer dependencies..."
composer install --no-dev --prefer-dist --optimize-autoloader

echo "Running migrations..."
php artisan migrate --force

echo "Updating Asset Version..."

VERSION=$(git rev-parse --short HEAD)

if grep -q "^ASSET_VERSION=" .env; then
    sed -i "s/^ASSET_VERSION=.*/ASSET_VERSION=$VERSION/" .env
else
    echo "ASSET_VERSION=$VERSION" >> .env
fi

echo "Clearing caches..."

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Deployment Completed"
echo "Current Version : $VERSION"