FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    sqlite3 \
    libsqlite3-dev \
    libxml2-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    zip \
    pdo \
    pdo_sqlite \
    intl \
    mbstring \
    xml \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

# Copy composer files first
COPY composer.json composer.lock symfony.lock ./

# Configure Composer plugins
RUN composer config --no-plugins allow-plugins.symfony/flex true && \
    composer config --no-plugins allow-plugins.symfony/runtime true && \
    composer config --no-plugins allow-plugins.php-http/discovery true

# Install dependencies (INCLUDING symfony/runtime)
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts \
    --prefer-dist

# Copy application code
COPY . .

# Run post-install scripts and generate autoloader
RUN composer dump-autoload --optimize --classmap-authoritative

# Run Symfony scripts
RUN composer run-script auto-scripts || true

# Create directories and set permissions
RUN mkdir -p var/cache var/log db && \
    chmod -R 777 var db

# Clear and warmup cache
RUN php bin/console cache:clear --env=prod --no-debug || true
RUN php bin/console cache:warmup --env=prod --no-debug || true

EXPOSE 10000

# Start PHP server
CMD php -S 0.0.0.0:${PORT:-10000} -t public