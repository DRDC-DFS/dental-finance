FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    git \
    unzip \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql mbstring exif bcmath zip

# Copy composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy project
COPY . .

# Install Laravel dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Fix permissions
RUN chmod -R 777 storage bootstrap/cache

# Copy nginx config
COPY docker/nginx.conf /etc/nginx/sites-available/default

# Expose Railway port
EXPOSE 8080

# Start nginx + php-fpm
CMD service nginx start && php-fpm