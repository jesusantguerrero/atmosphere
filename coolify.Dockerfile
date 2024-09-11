ARG NODE_VERSION=20.9.0
FROM node:${NODE_VERSION}-alpine as static-assets

RUN apk add --no-cache gcompat
WORKDIR /app

COPY . /app

RUN yarn install --frozen-lockfile && yarn && yarn build && npm prune --production

FROM dunglas/frankenphp as server

ARG user
ARG uid
ARG TZ

WORKDIR /app

ENV user $user
ENV uid $uid
ENV TZ $TZ
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
    default-mysql-client
# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/* && \
# Install PHP extensions
docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd && \
#install mailparse
pecl install mailparse && \
echo extension=mailparse.so > /usr/local/etc/php/conf.d/mailparse.ini && \
echo "max_execution_time=900" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

COPY . /app
# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --ignore-platform-reqs --no-dev --no-interaction --no-plugins --no-scripts --prefer-dist

COPY --from=static-assets --chown=9999:9999 /app/public/build ./public/build

# RUN php artisan route:cache
# RUN php artisan view:cache

RUN echo "alias ll='ls -al'" >>/etc/bash.bashrc
RUN echo "alias a='php artisan'" >>/etc/bash.bashrc
RUN echo "alias logs='tail -f storage/logs/laravel.log'" >>/etc/bash.bashrc
