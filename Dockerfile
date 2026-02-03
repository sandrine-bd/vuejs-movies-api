FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    sqlite3 \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    zip \
    pdo \
    pdo_sqlite \
    intl \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

# Copy everything at once
COPY . .

# Install dependencies (will use composer.lock)
RUN composer install --no-interaction --optimize-autoloader

# Create necessary directories and set permissions
RUN mkdir -p var/cache var/log db && \
    chmod -R 777 var db

EXPOSE 10000

# Start PHP built-in server
CMD php -S 0.0.0.0:${PORT:-10000} -t public