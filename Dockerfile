FROM php:8.2-cli

# Install essentials
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    sqlite3 \
    libsqlite3-dev

# Install PHP extensions
RUN docker-php-ext-install zip pdo pdo_sqlite intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

# Copy everything
COPY . .

# Show what we have
RUN ls -la && echo "=== PHP Version ===" && php -v

# Try to install - show full output
RUN composer install --no-dev --no-interaction -vvv 2>&1 | tee /tmp/composer.log || \
    (echo "=== COMPOSER FAILED - Full log: ===" && cat /tmp/composer.log && exit 1)

# Create directories
RUN mkdir -p var/cache var/log && chmod -R 777 var

EXPOSE 10000

CMD php -S 0.0.0.0:${PORT:-10000} -t public