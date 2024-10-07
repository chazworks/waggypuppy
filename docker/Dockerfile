FROM php:8.3-cli

RUN apt-get update && apt-get install -y libicu-dev libpng-dev libzip-dev unzip

RUN pecl install xdebug && docker-php-ext-enable xdebug

RUN /usr/local/bin/docker-php-ext-install gd intl mysqli pdo_mysql zip

# COPY docker/worker/php.local.ini /usr/local/etc/php/conf.d/

# COPY docker/xdebug.ini /usr/local/etc/php/conf.d/

EXPOSE 8889
WORKDIR /var/www
