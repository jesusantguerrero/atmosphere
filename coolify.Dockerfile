ARG NODE_VERSION=20.9.0
ARG PHP_VERSION=8.1.2
FROM php:${PHP_VERSION}-fpm as server

ARG user
ARG uid
ARG TZ

# Set working directory
WORKDIR /var/www

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
docker-php-ext-install pdo_mysql calendar mbstring exif pcntl bcmath gd && \
#install mailparse
pecl install mailparse && \
echo extension=mailparse.so > /usr/local/etc/php/conf.d/mailparse.ini && \
echo "max_execution_time=900" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

COPY . .
# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
# Create system user to run Composer and Artisan Commands
# RUN useradd -G www-data,root,crontab -u $uid -d /home/$user $user
# RUN mkdir -p /home/$user/.composer && \
#     chown -R $user:$user /home/$user && \
#     chown -R $user:$user /var/www && \
RUN chown -R www-data:www-data /var/www

RUN composer install --ignore-platform-reqs --no-dev --no-interaction --no-plugins --no-scripts --prefer-dist

# RUN php artisan route:cache
# RUN php artisan view:cache

RUN echo "alias ll='ls -al'" >>/etc/bash.bashrc
RUN echo "alias a='php artisan'" >>/etc/bash.bashrc
RUN echo "alias logs='tail -f storage/logs/laravel.log'" >>/etc/bash.bashrc

USER www-data
