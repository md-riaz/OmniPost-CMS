# Stage 1: Frontend Build
FROM node:20-alpine as frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: PHP Application
FROM php:8.3-fpm as app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libicu-dev \
    supervisor

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath xml zip intl opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .
# Copy built assets from frontend stage
COPY --from=frontend /app/public/build ./public/build

# Copy PHP config
COPY ./docker/php.ini /usr/local/etc/php/conf.d/local.ini

# Set permissions
RUN useradd -G www-data,root -u 1000 -d /home/laruser laruser
RUN mkdir -p /home/laruser/.composer && \
    chown -R laruser:laruser /home/laruser /var/www

# Switch to user
USER laruser

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set entrypoint
COPY ./docker/entrypoint.sh /usr/local/bin/start-container
USER root
RUN chmod +x /usr/local/bin/start-container
USER laruser

EXPOSE 9000
ENTRYPOINT ["start-container"]

# Stage 3: Nginx
FROM nginx:alpine as nginx
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
# Copy ONLY the public folder (including built assets) from the frontend stage
COPY --from=frontend /app/public /var/www/public
WORKDIR /var/www
