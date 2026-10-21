# syntax=docker/dockerfile:1.7

############################
# Base: PHP-FPM + extensions
############################
FROM php:8.4-fpm-alpine AS base

COPY --from=ghcr.io/mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        redis \
        zip \
    && apk add --no-cache fcgi

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/conf.d/app.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html

############################
# Dev: source is bind-mounted
############################
FROM base AS dev

RUN install-php-extensions xdebug \
    && apk add --no-cache git unzip

COPY docker/php/conf.d/dev.ini /usr/local/etc/php/conf.d/zz-dev.ini
COPY --chmod=755 docker/php/dev-entrypoint.sh /usr/local/bin/dev-entrypoint

ENTRYPOINT ["dev-entrypoint"]
CMD ["php-fpm"]

############################
# Build front-end assets
############################
FROM node:24-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci --no-audit --no-fund
COPY . .
RUN npm run build

############################
# Prod: immutable image
############################
FROM base AS prod

ENV APP_ENV=production APP_DEBUG=false

COPY docker/php/conf.d/prod.ini /usr/local/etc/php/conf.d/zz-prod.ini

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data
