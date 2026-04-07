FROM php:8.3-fpm

WORKDIR /var/www

# System packages + Node.js
RUN apt-get update && apt-get install -y \
    nginx \
    git \
    unzip \
    curl \
    ca-certificates \
    gnupg \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip pdo pdo_mysql mbstring exif bcmath

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy app
COPY . .

# Ensure Laravel writable dirs exist before any artisan/composer script usage
RUN mkdir -p /var/www/bootstrap/cache \
    /var/www/storage/framework/cache \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /tmp/views \
    && chmod -R 777 /var/www/bootstrap /var/www/storage /tmp/views

# PHP deps
# IMPORTANT: use --no-scripts so Laravel package discovery does not run during image build
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Frontend deps + Vite build
RUN npm install && npm run build

# Nginx config
COPY docker/nginx.conf /etc/nginx/sites-available/default

EXPOSE 8080

CMD php artisan optimize:clear && \
    php artisan package:discover --ansi && \
    php artisan config:clear && \
    php artisan cache:clear && \
    php artisan route:clear && \
    php artisan view:clear && \
    php artisan migrate --force && \
    service nginx start && php-fpm -F