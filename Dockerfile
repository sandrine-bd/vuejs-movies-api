FROM dunglas/frankenphp:latest

WORKDIR /app

RUN install-php-extensions \
	pdo_mysql \
	gd \
	intl \
	zip \
	opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .
RUN chmod +x bin/console

RUN composer install

# FrankenPHP avec Symfony
ENV FRANKENPHP_CONFIG="worker /app/public/index.php"
ENV APP_RUNTIME="Runtime\\FrankenPhpSymfony\\Runtime"
#ENV SERVER_NAME=localhost
ENV SERVER_NAME=":8000"

#EXPOSE 80 443
EXPOSE 8000
