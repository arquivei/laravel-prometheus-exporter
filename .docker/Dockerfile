ARG PHP_VERSION="8.1.5"

FROM php:${PHP_VERSION}-cli

WORKDIR /application

RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

RUN apt update; \
    apt install -y \
        git \
        libzip-dev; \
    docker-php-ext-install \
        zip; \
    pecl install \
        redis \
        apcu \
        xdebug; \
    docker-php-ext-enable \
        redis \
        zip \
        apcu; \
    echo "memory_limit=-1" > /usr/local/etc/php/conf.d/99-overrides.ini; \
    echo "apc.enable_cli=1" > /usr/local/etc/php/conf.d/99-overrides.ini; \
    echo "[Date]" >> /usr/local/etc/php/conf.d/99-overrides.ini; \
    echo "date.timezone=America/Sao_Paulo" >> /usr/local/etc/php/conf.d/99-overrides.ini; \
    echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini; \
    echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/xdebug.ini; \
    echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/xdebug.ini; \
    echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/xdebug.ini; \
    echo "xdebug.discover_client_host=1" >> /usr/local/etc/php/conf.d/xdebug.ini