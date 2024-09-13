FROM php:8.3-fpm-alpine AS php_upstream
FROM composer/composer:2-bin AS composer_upstream
FROM mlocati/php-extension-installer:2 AS php_extension_installer_upstream

FROM php_upstream AS php_base

WORKDIR /var/www/app

# Base packages for alpine
RUN apk add --no-cache \
        make \
        autoconf \
        acl \
		git \
        g++ \
        zip \
        libpq-dev \
        ffmpeg \
    ;

COPY --from=php_extension_installer_upstream --link /usr/bin/install-php-extensions /usr/local/bin/

# Install base extensions
RUN set -eux; \
    install-php-extensions \
		intl \
        opcache \
		exif \
    ;

# Install PDO for postgresql
RUN set -eux; \
    docker-php-ext-configure \
        pgsql -with-pgsql=/usr/local/pgsql \
    ; \
    docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
    ;


RUN pecl install redis;
RUN docker-php-ext-enable redis;

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

COPY --from=composer_upstream --link /composer /usr/bin/composer

COPY . /var/www/app