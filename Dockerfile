FROM node:20 as frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

FROM php:8.3-fpm

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

# Install Redis extension (recommended for Horizon)
RUN pecl install redis && docker-php-ext-enable redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application directory contents
COPY . /var/www

# Copy built frontend assets
COPY --from=frontend /app/public/build /var/www/public/build
# Copy manifest.json if exists in public directory, otherwise it's just the build folder
# Note: Vite build usually puts everything in public/build

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u 1000 -d /home/laruser laruser
RUN mkdir -p /home/laruser/.composer && \
    chown -R laruser:laruser /home/laruser

# Set permissions
RUN chown -R laruser:www-data /var/www

# Copy and set permissions for entrypoint
COPY ./docker/entrypoint.sh /usr/local/bin/start-container
COPY ./docker/php.ini /usr/local/etc/php/conf.d/local.ini
RUN chmod +x /usr/local/bin/start-container

# Switch to user
USER laruser

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Expose port 9000
EXPOSE 9000

# Start PHP-FPM server via entrypoint
ENTRYPOINT ["start-container"]
