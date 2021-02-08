FROM php:7.2-fpm
COPY php.ini /usr/local/etc/php/
COPY ./laravel /var/www/

RUN apt-get update \
  && apt-get install -y \
  zlib1g-dev mariadb-client \
  vim \
  autoconf \
  zlib1g-dev \
  && docker-php-ext-install zip pdo_mysql

RUN docker-php-ext-install sockets
RUN pecl install grpc

#Composer install
COPY --from=composer /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER 1

ENV COMPOSER_HOME /composer

ENV PATH $PATH:/composer/vendor/bin


WORKDIR /var/www

RUN composer global require "laravel/installer"

RUN composer install

RUN chmod 777 -R storage
