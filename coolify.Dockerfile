ARG NODE_VERSION=20.9.0
ARG PHP_VERSION=8.3

FROM serversideup/php:${PHP_VERSION}-fpm-nginx as base
WORKDIR /var/www/html

RUN PHP_OPCACHE_ENABLE=1

COPY composer.json composer.lock ./
RUN composer install --ignore-platform-reqs --no-dev --no-interaction --no-plugins --no-scripts --prefer-dist

FROM node:${NODE_VERSION}-alpine as asset-files

RUN apk add --no-cache gcompat
WORKDIR /app

COPY . .
COPY --from=base --chown=9999:9999 /var/www/html .

RUN yarn install && yarn && yarn build

FROM base as runner

ARG user
ARG uid
ARG TZ

WORKDIR /var/www/html

USER root
# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    cron \
    default-mysql-client \
    vim

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/* && \
# Install PHP extensions
    docker-php-ext-install pdo_mysql mbstring calendar exif pcntl bcmath gd && \
#install mailparse
    pecl install mailparse && \
    echo extension=mailparse.so > /usr/local/etc/php/conf.d/mailparse.ini

# Get latest Composer
COPY --from=base --chown=9999:9999 /var/www/html .
COPY --chown=9999:9999 . .
RUN composer update --ignore-platform-reqs
RUN composer dump-autoload
RUN chown -R www-data:www-data .

COPY --from=asset-files --chown=www-data:www-data /app/public/build ./public/build

RUN chown -R www-data:www-data /var/www

# RUN php artisan route:cache
# RUN php artisan view:cache

RUN echo "alias ll='ls -al'" >>/etc/bash.bashrc
RUN echo "alias a='php artisan'" >>/etc/bash.bashrc
RUN echo "alias logs='tail -f storage/logs/laravel.log'" >>/etc/bash.bashrc

USER www-data
