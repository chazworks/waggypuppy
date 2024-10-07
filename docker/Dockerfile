FROM php:8.3-cli

RUN apt-get update && apt-get install -y default-mysql-client git libicu-dev libpng-dev libzip-dev unzip

RUN pecl install xdebug && docker-php-ext-enable xdebug

RUN /usr/local/bin/docker-php-ext-install gd intl mysqli pdo_mysql zip

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

EXPOSE 8889
WORKDIR /var/www
