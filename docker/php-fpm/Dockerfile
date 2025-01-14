FROM php:8.2-fpm-alpine

RUN apk update
RUN apk add --no-cache git fcgi perl wget procps shadow libzip \
    openssh freetype zip htop openjdk11-jre

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN php -r "unlink('composer-setup.php');"
RUN apk add --no-cache bash

ENV SHELL=/bin/bash
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY ./docker/php-fpm/php.ini /usr/local/etc/php/php.ini

WORKDIR /srv/app

COPY docker/php-fpm/healthcheck.sh /usr/local/bin/docker-healthcheck
RUN chmod +x /usr/local/bin/docker-healthcheck

HEALTHCHECK --interval=10s --timeout=3s --retries=3 CMD ["docker-healthcheck"]

COPY . .

RUN sed -i 's/user = www-data/user = root/g' /usr/local/etc/php-fpm.d/www.conf
RUN sed -i 's/group = www-data/user = root/g' /usr/local/etc/php-fpm.d/www.conf

CMD ["php-fpm", "-R"]
