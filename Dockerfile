# PHP 8.5 con Apache para el Sistema de Gestión de Biblioteca Escolar
FROM php:8.5-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libicu-dev libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql intl zip \
    && rm -rf /var/lib/apt/lists/*

# Apache: DocumentRoot en public/ y cabeceras
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod headers

# PHP: zona horaria, errores y sesiones
COPY docker/php/biblioteca.ini /usr/local/etc/php/conf.d/biblioteca.ini

WORKDIR /var/www/html
COPY . /var/www/html
RUN mkdir -p /var/www/html/database/import \
    && chown -R www-data:www-data /var/www/html/database/import

EXPOSE 80
