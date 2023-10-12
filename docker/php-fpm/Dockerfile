FROM php:8.2.11-fpm

RUN apt-get update && apt-get install -y \
    git \
    zip \
    curl

WORKDIR /app

RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer