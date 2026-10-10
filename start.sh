#!/bin/bash
set -e

# Ensure the persistent disk directories exist
mkdir -p /var/www/html/storage/app/public/menu-images
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/bootstrap/cache

# Fix permissions on storage (persistent disk is mounted here)
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Clear config cache so Render's env vars (CLOUDINARY_URL etc.) are always picked up fresh
php /var/www/html/artisan config:clear

# Recreate the storage symlink at runtime (persistent disk is now mounted)
rm -f /var/www/html/public/storage
php /var/www/html/artisan storage:link --force

# Run migrations
php /var/www/html/artisan migrate --force

# Start Apache
exec apache2-foreground
