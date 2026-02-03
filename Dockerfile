FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libicu-dev sqlite3 libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install zip pdo pdo_sqlite intl opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1

WORKDIR /app

COPY . .

# Allow ALL plugins
RUN composer config allow-plugins true

# Install with maximum compatibility
RUN composer install --ignore-platform-reqs --no-interaction || \
    composer install --no-dev --no-interaction

RUN mkdir -p var && chmod -R 777 var

EXPOSE 10000
CMD php -S 0.0.0.0:${PORT:-10000} -t public
```
