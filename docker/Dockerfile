FROM php:8.3-cli

RUN apt update && apt install -y default-mysql-client git

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/download/2.5.2/install-php-extensions /usr/local/bin/

RUN install-php-extensions gd intl mysqli pdo_mysql xdebug zip

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

EXPOSE 8889
WORKDIR /var/www

