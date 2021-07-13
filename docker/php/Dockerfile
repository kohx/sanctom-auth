FROM php:7.4-fpm

COPY php.ini /usr/local/etc/php/

# timezone
ENV TZ Asia/Tokyo
RUN echo "${TZ}" > /etc/timezone \
   && dpkg-reconfigure -f noninteractive tzdata

# apt
RUN apt-get update

# pdo
RUN apt-get install -y zlib1g-dev libzip-dev default-mysql-client \
    && docker-php-ext-install zip pdo_mysql

# mbstring
RUN apt-get install -y libonig-dev

# exif
RUN apt-get install -y exif

# GD
RUN apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# git
RUN apt-get install -y git

# phpredis
RUN git clone https://github.com/phpredis/phpredis.git /usr/src/php/ext/redis
RUN docker-php-ext-install redis

# composer
COPY --from=composer /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_HOME /composer
ENV PATH $PATH:/composer/vendor/bin

# node
RUN apt-get install -y wget git unzip libpq-dev \
    && : 'Install Node.js' \
    &&  curl -sL https://deb.nodesource.com/setup_12.x | bash - \
    && : 'Install PHP Extensions' \
    && apt-get install -y nodejs

# vim
RUN apt-get install -y vim

# workdir
WORKDIR /var/www