FROM php:8.5-fpm-bookworm

ENV NVM_DIR=/var/www/.nvm \
    NODE_VERSION=24.11.1

# ENV NODEJS_VERSION=16

# CHANGE ON OWN USER/GROUP IDS [$ id]
RUN usermod -u 1000 www-data
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
        zip \
    && apt-get clean

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
# PHP 8.5 builds OPcache into the binary, so there is no opcache.so to install.
RUN docker-php-ext-install bcmath bz2 calendar curl exif fileinfo gettext intl \
        pcntl pdo gd session shmop soap sockets sysvmsg sysvsem sysvshm xml xsl zip \
        tidy pdo_pgsql pgsql

# RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl
# RUN docker-php-ext-install imap

RUN pecl channel-update pecl.php.net

RUN pecl install apcu \
    && docker-php-ext-enable apcu
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


# nodejs
RUN install -d -m 0755 -o www-data -g www-data /var/www/.nvm \
    && install -d -m 0755 -o www-data -g www-data /var/www/.npm \
    && install -d -m 0755 -o www-data -g www-data /var/www/.cache
USER www-data
RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.5/install.sh | bash
RUN . "$NVM_DIR/nvm.sh" && nvm install "v${NODE_VERSION}"
RUN . "$NVM_DIR/nvm.sh" && nvm use "${NODE_VERSION}"
RUN . "$NVM_DIR/nvm.sh" && nvm alias default "${NODE_VERSION}"
RUN . "$NVM_DIR/nvm.sh" && npm_config_cache=/var/www/.npm npm install -g pnpm@10
USER root
ENV PATH="${NVM_DIR}/versions/node/v${NODE_VERSION}/bin:${PATH}"
RUN ln -sf "${NVM_DIR}/versions/node/v${NODE_VERSION}/bin/node" /usr/local/bin/node \
    && ln -sf "${NVM_DIR}/versions/node/v${NODE_VERSION}/bin/npm" /usr/local/bin/npm \
    && ln -sf "${NVM_DIR}/versions/node/v${NODE_VERSION}/bin/npx" /usr/local/bin/npx \
    && ln -sf "${NVM_DIR}/versions/node/v${NODE_VERSION}/bin/corepack" /usr/local/bin/corepack \
    && ln -sf "${NVM_DIR}/versions/node/v${NODE_VERSION}/bin/pnpm" /usr/local/bin/pnpm

#services
RUN apt-get install -y --no-install-recommends \
        cron \
        nano \
        supervisor \
    && apt-get clean

#python
RUN apt-get install -y --no-install-recommends \
        libgl1 \
        protobuf-compiler \
        python3-dev \
        python3-venv \
        python3-pip \
        wget \
    && apt-get clean

RUN mv /usr/lib/python3.11/EXTERNALLY-MANAGED /usr/lib/python3.11/EXTERNALLY-MANAGED.old

# composer
RUN set -eu; \
    EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"; \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"; \
    ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"; \
    if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; \
    then \
        >&2 echo 'ERROR: Invalid installer checksum'; \
        rm composer-setup.php; \
        exit 1; \
    fi; \
    php composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer; \
    RESULT=$?; \
    rm composer-setup.php; \
    exit $RESULT;

# XDebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

COPY php.ini /usr/local/etc/php/php.ini
COPY mods.d/ /usr/local/etc/php/conf.d/

# VOLUME  /data
# WORKDIR /data

# RUN mkdir /data
RUN chown www-data:www-data /var/www

#miniconda
USER www-data
RUN mkdir -p $HOME/miniconda3
RUN wget "https://repo.anaconda.com/miniconda/Miniconda3-latest-Linux-$(uname -m).sh" -O $HOME/miniconda3/miniconda.sh
RUN bash $HOME/miniconda3/miniconda.sh -b -u -p $HOME/miniconda3
RUN rm -rf $HOME/miniconda3/miniconda.sh
RUN $HOME/miniconda3/bin/conda init bash
# Accept ToS for required channels
RUN $HOME/miniconda3/bin/conda tos accept --override-channels --channel https://repo.anaconda.com/pkgs/main
RUN $HOME/miniconda3/bin/conda tos accept --override-channels --channel https://repo.anaconda.com/pkgs/r
USER root

# crontab
RUN echo "*/1 * * * * www-data . /etc/environment; /usr/local/bin/php /data/bin/console oro:cron >> /data/var/logs/cron.log" >> /etc/cron.d/www-data-cron
RUN chmod 0644 /etc/cron.d/www-data-cron && crontab -u www-data /etc/cron.d/www-data-cron

# COPY start.sh /var/www/start.sh
# RUN chmod +x /var/www/start.sh
# CMD ["/var/www/start.sh"]

# # ENV COMPOSER_ALLOW_SUPERUSER 1
# RUN install -d -m 0755 -o www-data -g www-data /var/www/.composer
# USER www-data
# RUN composer global config --no-plugins allow-plugins.symfony/flex true
# RUN composer global config --no-plugins allow-plugins.symfony/runtime true
# RUN composer global require composer/package-versions-deprecated
# RUN composer global require symfony/flex
# RUN composer global require symfony/runtime
# USER root

RUN rm -rf /var/www/html \
    && chown -R www-data:www-data /var/www/.nvm /var/www/.npm /var/www/.cache \
    && chmod 0777 /tmp/
