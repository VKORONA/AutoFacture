FROM php:8.4-fpm-bookworm

ARG user=autofacture
ARG uid=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libfreetype6-dev \
    libicu-dev \
    libjpeg62-turbo-dev \
    libmagickwand-dev \
    libonig-dev \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    mariadb-client \
    unzip \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        pcntl \
        pdo_mysql \
        zip \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

RUN useradd -G www-data,root -u ${uid} -d /home/${user} -m ${user} \
    && mkdir -p /home/${user}/.composer \
    && chown -R ${user}:${user} /home/${user}

WORKDIR /var/www
USER ${user}
