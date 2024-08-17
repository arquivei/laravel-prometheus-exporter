ARG PHP_VERSION="8.3"

FROM php:${PHP_VERSION}-cli

WORKDIR /application

RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

RUN pecl install xdebug && docker-php-ext-enable xdebug

RUN apt-get update \
    && apt-get install -y git libzip-dev \
    && docker-php-ext-install zip \
    && pecl install redis apcu \
    && docker-php-ext-enable redis zip apcu

COPY .docker/php-ini-overrides.ini /usr/local/etc/php/conf.d/99-overrides.ini
