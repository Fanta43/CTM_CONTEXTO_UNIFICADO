# PHP 8.5 FPM con las extensiones que usan los modulos CTM
FROM php:8.5-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev libpng-dev libicu-dev unzip git \
    && docker-php-ext-install mysqli pdo_mysql zip gd intl opcache \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/ctm/contexto_unificado
