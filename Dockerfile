FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libicu-dev sqlite3 libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install zip pdo pdo_sqlite intl opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

# Copy EVERYTHING first
COPY . .

# Configure and install
RUN composer config allow-plugins true && \
    composer install --no-dev --optimize-autoloader --no-interaction

# Permissions
RUN mkdir -p var && chmod -R 777 var

# Clear cache
RUN APP_ENV=prod php bin/console cache:clear || true

EXPOSE 10000
CMD php -S 0.0.0.0:${PORT:-10000} -t public