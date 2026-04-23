FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
        git \
        unzip \
        libxml2-dev \
        libsqlite3-dev \
        libonig-dev \
    && docker-php-ext-install dom xml mbstring pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /package
