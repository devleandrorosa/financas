FROM php:8.3-fpm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        intl \
        bcmath \
        zip \
        opcache \
    && pecl install xdebug redis \
    && docker-php-ext-enable xdebug redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN groupmod -g ${GID} www-data \
    && usermod -u ${UID} -g ${GID} www-data

WORKDIR /var/www/backend

USER www-data
