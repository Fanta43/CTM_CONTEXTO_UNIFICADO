# PHP 8.5 FPM con las extensiones que usan los modulos CTM (igualar con "php -m" de torito)
FROM php:8.5-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libicu-dev libxml2-dev \
        libonig-dev libcurl4-openssl-dev unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli pdo_mysql zip gd intl soap bcmath exif calendar \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/ctm/contexto_unificado
