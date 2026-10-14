# PHP 8.5 FPM con las mismas extensiones que torito ("php -m" en produccion).
# Falta solo imap, que en PHP 8.5 es externa (PECL); agregar si alguna pantalla la usa.
FROM php:8.5-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libicu-dev libxml2-dev \
        libxslt1-dev libbz2-dev libgmp-dev libldap2-dev libffi-dev unixodbc-dev \
        unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-configure odbc --with-unixODBC=shared,/usr \
    && docker-php-ext-configure pdo_odbc --with-pdo-odbc=unixODBC,/usr \
    && docker-php-ext-install \
        mysqli pdo_mysql zip gd intl soap exif calendar \
        bz2 ffi gettext gmp ldap odbc pdo_odbc pcntl shmop sockets sysvmsg sysvsem sysvshm xsl \
    && docker-php-ext-enable opcache \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/ctm/contexto_unificado
