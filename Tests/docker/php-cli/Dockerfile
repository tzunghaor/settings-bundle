# php-cli container for development: contains composer and its dependencies

ARG PHP_VERSION=8.0

FROM php:${PHP_VERSION}-cli

RUN apt-get update && \
    apt-get install -y unzip libzip-dev && \
    docker-php-ext-install zip

RUN pecl install xdebug && \
    docker-php-ext-enable xdebug

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
