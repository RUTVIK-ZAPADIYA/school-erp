FROM php:8.2-apache

RUN docker-php-ext-install mysqli

RUN a2enmod rewrite \
    && sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:10000>/' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . /var/www/html/

RUN mkdir -p /var/www/html/uploads/assignments \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 10000
CMD ["apache2-foreground"]
