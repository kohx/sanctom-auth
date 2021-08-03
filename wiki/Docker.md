# docker

aws amazon linux2

* nginx
* https-portal
* redis
* mysql:5.7
* Node
* php7.4
  + dg
  + mbstring
  + composer
  + etc

## docker-comose setup

### directories

```text

www ─┬─ docker ┬─ db ─ conf ─ my.conf
     │         ├─ https-portal
     │         ├─ nginx ─ default.conf
     │         └─ php ┬─ Dockerfile
     │                └─ php.ini
     ├─ src
     └─ docker-compose.yml
```

### db my.conf

```bash
$ sudo touch docker/db/conf/my.conf
$ sudo vim docker/db/conf/my.conf

  i
  ------
  [mysqld]
  max_allowed_packet = 16M
  default-time-zone = 'Asia/Tokyo'
  -----
  esk
  :wq

```

### nginx default.conf

```bash
$ sudo touch docker/nginx/default.conf
$ sudo vim docker/nginx/default.conf
```

```conf
server {
  listen 80;
    index index.php index.html;
    root /var/www/public;

  location / {
    root /var/www/public;
    index  index.html index.php;
    try_files $uri $uri/ /index.php$is_args$args;
    }

  location ~ \.php$ {
    try_files $uri =404;
    fastcgi_split_path_info ^(.+\.php)(/.+)$;
    fastcgi_pass php:9000;
    fastcgi_index index.php;
    include fastcgi_params;
      fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
      fastcgi_param PATH_INFO $fastcgi_path_info;
  }
}
```

### php.ini

```bash
$ sudo touch docker/php/php.ini
$ sudo vim docker/php/php.ini
```

```ini
[Date]
date.timezone = "Asia/Tokyo"
[mbstring]
mbstring.internal_encoding = "UTF-8"
mbstring.language = "Japanese"
[xdebug]
xdebug.remote_enable=1
xdebug.remote_autostart=1
xdebug.remote_host=host.docker.internal
xdebug.remote_port=9000
xdebug.remote_log=/tmp/xdebug.log
```

### Dockerfile

```bash
$ sudo touch docker/php/Dockerfile
$ sudo vim docker/php/Dockerfile
```

```Dockerfile
FROM php:7.4-fpm

COPY php.ini /usr/local/etc/php/

# timezone
ENV TZ Asia/Tokyo
RUN echo "${TZ}" > /etc/timezone \
  && dpkg-reconfigure -f noninteractive tzdata

RUN apt-get update

# pdo
RUN apt-get install -y zlib1g-dev libzip-dev default-mysql-client  \
  && docker-php-ext-install zip pdo_mysql

# mbstring
RUN apt-get install -y libonig-dev

# exif
RUN apt-get install -y exif

# DG
RUN apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) gd

#git
RUN apt-get install -y git

# vim
RUN apt-get install -y vim

# nmap
RUN apt-get install -y nmap

# unzip
RUN apt-get -y install unzip

# phpredis
RUN git clone https://github.com/phpredis/phpredis.git /usr/src/php/ext/redis
RUN docker-php-ext-install redis

# composer install
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
  && php -r "if (hash_file('SHA384', 'composer-setup.php') === trim(file_get_contents('https://composer.github.io/installer.sig'))) { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
  && php composer-setup.php\
  && php -r "unlink('composer-setup.php');" \
  && mv composer.phar /usr/sbin/composer

ENV COMPOSER_ALLOW_SUPERUSER 1

ENV COMPOSER_HOME /composer

ENV PATH $PATH:/composer/vendor/bin

WORKDIR /var/www

RUN composer global require "laravel/installer"
```

### docker-compose.yml

```bash
$ sudo touch docker-compose.yml
$ sudo vim docker-compose.yml
```

```yml
version: "3"

services:
  ## phpサービス
  php:
    container_name: ${NAME_PREFIX}_php
    build: ./docker/php
    volumes:
      - ./src:/var/www
    # ports:
      - "3000:3000"
      - "3001:3001"

  ## nginxサービス
  nginx:
    container_name: ${NAME_PREFIX}_nginx
    image: nginx
    ports:
      - 8080:80
    volumes:
      - ./src:/var/www
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - php

  ## https-portalサービス
  https-portal:
    container_name: ${NAME_PREFIX}_https-portal
    image: steveltn/https-portal
    ports:
      - 80:80
      - 443:443
    restart: always
    environment:
      # - for prod -
      # DOMAINS: "campget.com -> http://nginx" # <-- dimain
      # STAGE: "production"
      # - for dev -
      DOMAINS: "localhost -> http://nginx"
      STAGE: "local"
      # - 証明書 -
      FORCE_RENEW: 'false' # <-- 毎日証明書を更新
    volumes:
      - ./docker/https-portal:/var/lib/https-portal

  ## dbサービス
  db:
    container_name: ${NAME_PREFIX}_db
    image: mariadb
    ports:
      - 3306:3306
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
      MYSQL_DATABASE: ${MYSQL_DATABASE}
      MYSQL_USER: ${MYSQL_USER}
      MYSQL_PASSWORD: ${MYSQL_PASSWORD}
      TZ: "Asia/Tokyo"
    command: mysqld --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci
    volumes:
      - ./docker/db/conf:/etc/mysql/conf.d
      - ./docker/db/data:/var/lib/mysql
      - ./docker/db/init:/docker-entrypoint-initdb.d

  ## redisサービス
  redis:
    container_name: ${NAME_PREFIX}_redis
    image: redis:latest
    ports:
      - 6379:6379
    volumes:
      - ./docker/redis/data:/data
```

## docker install

### yum update

 `$ sudo yum update -y`

### yum から docker をインストール

 `$ sudo yum install -y docker`

### docker サービスの起動

 `$ sudo systemctl start docker`

 `$ sudo systemctl status docker`

### 自動起動設定

 `$ sudo systemctl enable docker`

### 自動起動設定確認

```bash
$ sudo systemctl list-unit-files | grep docker.service

    docker.service          enabled
```

### ec2-user を docker グループに追加する

 `sudo usermod -a -G docker ec2-user`

一度ログアウトし、再度ログインすると、 docker コマンドが利用可能になる。
 `$ exit`

 `$ docker info`

## docker-comose install

### 一時的にスーパーユーザーになる

 `$ sudo -i`

--->  ここから一時的にスーパーユーザー

 `$ curl -L "https://github.com/docker/compose/releases/download/1.11.2/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose`

### docker-compose コマンドに実行権限付与

 `$ chmod +x /usr/local/bin/docker-compose`

### スーパーユーザーを抜ける

 `$ exit`

<--- スーパーユーザーここまで

### create directory

 `$ cd /var`

 `$ sudo mkdir www`

 `$ cd www`

### crete docker directory

 `$ sudo mkdir docker`

 `$ sudo mkdir docker/db`

 `$ sudo mkdir docker/db/conf`

 `$ sudo mkdir docker/https-portal`

 `$ sudo mkdir docker/nginx`

 `$ sudo mkdir docker/php`

### docker-compose コマンドの実行確認

```bash
$ docker-compose --version
    docker-compose version 1.11.2, build dfed245
```

### 本番用に変更

```yml
version: "3"

services:
  ## phpサービス
  php:
    container_name: ${NAME_PREFIX}_php
    build: ./docker/php
    volumes:
      - ./src:/var/www
    # ports:
     # - "3000:3000" ## 閉じる
     # - "3001:3001"

#...

  ## https-portalサービス
  https-portal:
    container_name: ${NAME_PREFIX}_https-portal
    image: steveltn/https-portal
    ports:
      - 80:80
      - 443:443
    restart: always
    environment:
      # - for prod -
      DOMAINS: "campget.com -> http://nginx" # <-- dimain
      STAGE: "production"
      # - for dev -
      # DOMAINS: "localhost -> http://nginx"
      # STAGE: "local"
      # - 証明書 -
      FORCE_RENEW: 'false' # <-- 毎日証明書を更新
    volumes:
      - ./docker/https-portal:/var/lib/https-portal

#...
```

### docker start

```bash
docker-compose build
docker-compose up -d
docker-compose exec php bash

composer create-project "laravel/laravel=8.*" . --prefer-dist
# or
# laravelソースを持ってくる

composer install
chmod 777 -R /var/www/storage

php artisan key:generate
php artisan cache:clear
php artisan config:clear
php artisan config:cache
php artisan storage:link

php artisan migrate
php artisan db:seed
```
