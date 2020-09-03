FROM php:7.2.0-fpm-alpine

ENV BUILD_DEPS \
    autoconf \
    make \
    gcc \
    g++ \
    re2c \
    bash \
    lz4-dev \
    musl-dev \
    cyrus-sasl-dev \
    python \
    gettext \
    git

RUN apk update && apk add --no-cache --virtual .build-deps ${BUILD_DEPS} \
    && pecl install \
        apcu \
        apcu_bc-1.0.3 \
        xdebug \
        redis \
    && docker-php-ext-enable apcu --ini-name 10-docker-php-ext-apcu.ini \
    && docker-php-ext-enable apc --ini-name 20-docker-php-ext-apc.ini \
    && docker-php-ext-enable redis \
    && docker-php-ext-install bcmath \
    && docker-php-ext-install pdo_mysql

RUN apk add --no-cache --update libmemcached-libs zlib
RUN set -xe && \
    cd /tmp/ && \
    apk add --no-cache --update --virtual .phpize-deps $PHPIZE_DEPS && \
    apk add --no-cache --update --virtual .memcached-deps zlib-dev libmemcached-dev cyrus-sasl-dev && \
# Install igbinary (memcached's deps)
    pecl install igbinary && \
# Install memcached
    ( \
        pecl install --nobuild memcached && \
        cd "$(pecl config-get temp_dir)/memcached" && \
        phpize && \
        ./configure --enable-memcached-igbinary && \
        make -j$(nproc) && \
        make install && \
        cd /tmp/ \
    ) && \
# Enable PHP extensions
    docker-php-ext-enable igbinary memcached && \
    rm -rf /tmp/* && \
    apk del .memcached-deps .phpize-deps

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
