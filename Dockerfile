FROM php:8.3-apache

RUN docker-php-ext-install mysqli

WORKDIR /var/www/html
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

ENV APP_ENV=demo

EXPOSE 80
