FROM php:8.4-cli-alpine

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY --from=mlocati/php-extension-installer:2.9 /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions mbstring xml intl zip pcntl opcache curl pcov

RUN apk add --no-cache git unzip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

CMD ["/bin/sh"]
