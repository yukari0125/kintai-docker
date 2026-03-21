FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libicu-dev \
    libxml2-dev \
    zip \
    curl \
    && docker-php-ext-install pdo_mysql zip intl dom xml \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
