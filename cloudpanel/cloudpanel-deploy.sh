#!/bin/bash
# ==============================================================================
# RegRun Multi-Domain Production Deployment Script for CloudPanel
# ==============================================================================
# Usage:
# 1. Place this script in /home/{{user}}/htdocs/{{domain}}/cloudpanel-deploy.sh
# 2. Or paste its contents into CloudPanel Site -> Deployment -> Script
# ==============================================================================

set -e

echo "🚀 Starting RegRun Multi-Domain Deployment on CloudPanel..."

# Ensure we are in application root
cd /home/$USER/htdocs/$SITE_NAME || cd "$(dirname "$0")/.."

# 1. Maintenance Mode
echo "🔧 Putting application into maintenance mode..."
php artisan down --render="errors::503" --secret="regrun-bypass-token" || true

# 2. Git Pull Latest Code
echo "📥 Pulling latest codebase from git..."
git pull origin main

# 3. Install/Update Composer Dependencies
echo "📦 Installing composer production dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# 4. Run Database Migrations
echo "🗄️ Running database migrations (multi-domain & schema updates)..."
php artisan migrate --force

# 5. Clear and Cache Configuration, Routes, and Views
echo "⚡ Optimizing Laravel caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Ensure Storage Permissions & Symlink
echo "📂 Verifying storage permissions..."
php artisan storage:link || true
chmod -R 775 storage bootstrap/cache

# 7. Restart Queue Worker
echo "🔄 Reloading queue workers..."
php artisan queue:restart || true

# 8. Bring Application Up
echo "✨ Bringing application back online..."
php artisan up

echo "✅ RegRun Multi-Domain Deployment Completed Successfully!"
