FROM php:8.5-fpm-bookworm

# ENV NODEJS_VERSION=16

# CHANGE ON OWN USER/GROUP IDS [$ id]
RUN usermod -u 1000 www-data
RUN groupmod -g 1000 www-data
RUN usermod -G staff www-data

# Woraround in latest images in order to install php-pear
# https://github.com/docker-library/php/blob/master/Dockerfile-debian.template#L3-L10
RUN rm /etc/apt/preferences.d/no-debian-php

# install core extension
RUN apt-get update \
    && apt-get install -y --no-install-recommends apt-utils \
        curl \
        git \
        bzip2 \
        unzip \
        zip \
        libcurl4-openssl-dev \
        php-pear \
        ghostscript \
        libmcrypt-dev \
        libssl-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libxml2-dev \
        zlib1g-dev \
        libicu-dev \
        libpq-dev \
        libxslt-dev \
        libc-client-dev \
        libkrb5-dev \
        libxslt1-dev \
        libmemcached-dev \
        libldap2-dev \
        geoip-bin \
        geoip-database \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libgeoip-dev \
        gettext \
        default-mysql-client \
        libmagickwand-dev \
        libicu-dev \
        libtidy-dev \
        libwebp-dev \
        libusb-1.0-0 \
        jpegoptim \
        pngquant \
        acl \
        procps \
    && apt-get clean

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
RUN docker-php-ext-install bcmath bz2 calendar curl exif fileinfo gettext intl \
        pcntl pdo gd session shmop soap sockets sysvmsg sysvsem sysvshm xml xsl zip \
        tidy pdo_pgsql pgsql

RUN pecl channel-update pecl.php.net

RUN pecl install apcu \
    && docker-php-ext-enable apcu

# RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl
# RUN docker-php-ext-install imap
RUN pecl install imap \
    && docker-php-ext-enable imap

# RUN pecl install redis http://pecl.php.net/get/geoip-1.1.1.tgz \
#     && docker-php-ext-enable redis geoip

# # Set locale
# RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y locales && apt-get clean
# RUN locale-gen en_US.UTF-8
# RUN sed -i -e 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen && \
#     dpkg-reconfigure --frontend=noninteractive locales && \
#     update-locale LANG=en_US.UTF-8
# ENV LANG en_US.UTF-8
# ENV LANGUAGE en_US:en
# ENV LC_ALL en_US.UTF-8
# ENV EDITOR nano

# XDebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

COPY conf/php.ini /usr/local/etc/php/php.ini
COPY mods.d/ /usr/local/etc/php/conf.d/

# VOLUME  /data
# WORKDIR /data
 
RUN chown www-data:www-data /var/www

RUN rm -rf /var/www/html
