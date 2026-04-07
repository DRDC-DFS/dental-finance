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

# 🔥 HARD FIX (INI KUNCI UTAMA)
RUN mkdir -p /var/www/bootstrap/cache \
    && chmod -R 777 /var/www/bootstrap \
    && chmod -R 777 /var/www/storage

# 🔥 TAMBAHAN (antisipasi Laravel)
RUN touch /var/www/bootstrap/cache/packages.php \
    && touch /var/www/bootstrap/cache/services.php

# PHP deps
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Frontend deps
RUN npm install && npm run build

# Permissions
RUN chmod -R 777 /var/www/storage /var/www/bootstrap/cache

# Nginx config
COPY docker/nginx.conf /etc/nginx/sites-available/default

EXPOSE 8080

CMD php artisan optimize:clear && \
    php artisan config:clear && \
    php artisan cache:clear && \
    php artisan route:clear && \
    php artisan view:clear && \
    php artisan migrate --force && \
    service nginx start && php-fpm -F