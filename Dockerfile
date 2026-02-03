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

# Allow all plugins
RUN composer config allow-plugins true

# Install dependencies (with all deps now that runtime is fixed)
RUN composer install --optimize-autoloader --no-interaction

# Create directories and set permissions
RUN mkdir -p var/cache var/log db && chmod -R 777 var db

# Clear cache (ignore DB errors)
RUN php bin/console cache:clear --env=prod --no-debug 2>&1 || true

EXPOSE 10000

CMD php -S 0.0.0.0:${PORT:-10000} -t public