FROM php:8.4-apache

# Install system packages and PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip curl libpq-dev libzip-dev libonig-dev libxml2-dev libpng-dev zip \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip mbstring xml \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module
RUN a2enmod rewrite

# Configure Apache to listen on port 10000 (Render requirement)
RUN sed -i 's/Listen 80/Listen 10000/g' /etc/apache2/ports.conf \
 && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:10000>/g' /etc/apache2/sites-available/000-default.conf

# Set document root to Laravel's public directory
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf \
 && sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/apache2.conf

# Allow .htaccess overrides for Laravel routing
RUN printf '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
 && a2enconf laravel

# Install Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
 && apt-get install -y nodejs

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files
COPY . .

# Set up .env — use example as base, override key settings for production
RUN cp .env.example .env \
 && sed -i 's/APP_ENV=local/APP_ENV=production/' .env \
 && sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env \
 && sed -i 's/SESSION_DRIVER=database/SESSION_DRIVER=file/' .env \
 && sed -i 's/CACHE_STORE=database/CACHE_STORE=file/' .env \
 && sed -i 's|DB_CONNECTION=sqlite|DB_CONNECTION=sqlite|' .env

# Ensure SQLite database file exists
RUN touch database/database.sqlite

# Install PHP dependencies (no dev, optimized)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Generate application key
RUN php artisan key:generate --force

# Install Node dependencies and build frontend assets
RUN npm install && npm run build

# Clear caches then cache for production
RUN php artisan config:clear \
 && php artisan route:clear \
 && php artisan view:clear \
 && php artisan config:cache \
 && php artisan route:cache \
 && php artisan view:cache

# Create storage symlink
RUN php artisan storage:link || true

# Set correct permissions
RUN mkdir -p storage/framework/cache storage/framework/sessions \
    storage/framework/views bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache database \
 && chmod -R 775 storage bootstrap/cache database

# Run migrations with seed
RUN php artisan migrate --force --seed || php artisan migrate --force || true

EXPOSE 10000

CMD ["apache2-foreground"]
