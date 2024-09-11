FROM dunglas/frankenphp as builder

ARG NODE_VERSION=20.9.0
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
# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install --no-dev --no-interaction --no-plugins --no-scripts --prefer-dist

FROM node:${NODE_VERSION}-alpine as static-assets
WORKDIR /app
COPY . .
COPY --from=builder --chown=9999:9999 /app /app
RUN npm install
RUN npm run build

FROM dunglas/frankenphp as server

ARG user
ARG uid
ARG TZ

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

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY --from=builder --chown=user:group /app /app
