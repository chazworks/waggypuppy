FROM php:8.3-cli AS base

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/download/2.5.2/install-php-extensions /usr/local/bin/

RUN install-php-extensions gd intl mysqli pdo_mysql xdebug zip

RUN mkdir /app \
    && groupadd app \
    && useradd -d /app -g app -p x app \
    && chown app:app /app /var/www

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

USER app

WORKDIR /var/www
EXPOSE 8889

################
FROM base AS devbox

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

USER root
RUN apt update && apt install -y default-mysql-client git iputils-ping

USER app
RUN git config --global --add safe.directory /var/www

