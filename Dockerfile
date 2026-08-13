ARG PHP_VERSION=8.5
ARG PHP_EXTENSION_INSTALLER_VERSION=2.11

FROM mlocati/php-extension-installer:${PHP_EXTENSION_INSTALLER_VERSION} AS php-extension-installer

FROM php:${PHP_VERSION}-cli-alpine AS base

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions mbstring xml intl zip pcntl opcache curl pcov

RUN apk add --no-cache git unzip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

CMD ["/bin/sh"]
