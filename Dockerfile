FROM php:8.2-cli

# Install system dependencies
# Install essentials
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libzip-dev \
    sqlite3 \
    libsqlite3-dev \
    libicu-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required by Symfony
RUN docker-php-ext-install \
    zip \
    pdo \
    pdo_sqlite \
    intl \
    mbstring \
    opcache
    sqlite3 \
    libsqlite3-dev

# Configure opcache for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini
# Install PHP extensions
RUN docker-php-ext-install zip pdo pdo_sqlite intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure Composer
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_NO_INTERACTION=1

# Set working directory
WORKDIR /app

# Copy composer files first (for better layer caching)
COPY composer.json composer.lock symfony.lock ./

# Install dependencies with verbose output for debugging
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --verbose

# Copy the rest of the application
# Copy everything
COPY . .

# Create and set permissions for runtime directories
RUN mkdir -p var/cache var/log db public/uploads && \
    chmod -R 777 var db public/uploads
# Show what we have
RUN ls -la && echo "=== PHP Version ===" && php -v

# Generate optimized autoloader
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev
# Try to install - show full output
RUN composer install --no-dev --no-interaction -vvv 2>&1 | tee /tmp/composer.log || \
    (echo "=== COMPOSER FAILED - Full log: ===" && cat /tmp/composer.log && exit 1)

# Clear and warmup Symfony cache
RUN APP_ENV=prod php bin/console cache:clear --no-warmup || true
RUN APP_ENV=prod php bin/console cache:warmup || true
# Create directories
RUN mkdir -p var/cache var/log && chmod -R 777 var

# Expose port
EXPOSE 10000

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s \
    CMD php -r "echo 'OK';" || exit 1

# Start the built-in PHP server
CMD php -S 0.0.0.0:${PORT:-10000} -t public