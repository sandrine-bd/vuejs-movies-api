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
ENV COMPOSER_MEMORY_LIMIT=-1

WORKDIR /app

# Copy everything
COPY . .

# Show files for debug
RUN echo "=== Files in /app ===" && ls -la

# Check composer.json validity
RUN echo "=== Validating composer.json ===" && composer validate --no-check-publish || true

# Allow all plugins
RUN composer config allow-plugins true

# Try install with verbose output
RUN echo "=== Installing dependencies ===" && \
    composer install --no-interaction -vvv 2>&1 | tail -100 || \
    (echo "=== FIRST ATTEMPT FAILED, trying without --no-dev ===" && \
     composer install --ignore-platform-reqs --no-interaction) || \
    (echo "=== SECOND ATTEMPT FAILED, trying basic install ===" && \
     composer update --no-interaction)

# Verify vendor exists
RUN echo "=== Checking vendor directory ===" && ls -la vendor/ || echo "VENDOR MISSING!"

# Create directories
RUN mkdir -p var/cache var/log db && chmod -R 777 var db

# Try cache commands
RUN php bin/console cache:clear --env=prod || echo "Cache clear failed"

EXPOSE 10000

CMD php -S 0.0.0.0:${PORT:-10000} -t public