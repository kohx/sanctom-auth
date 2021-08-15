# docker

aws amazon linux2

* php8
  + dg
  + mbstring
  + composer
* nginx
* https-portal
* redis
* mysql:5.7
* Node

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
FROM php:8.0-fpm

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

RUN composer global require "laravel/installer"
```

### docker-compose.yml

```bash
$ sudo touch docker-compose.yml
$ sudo vim docker-compose.yml
```

```yml
# Docker Composeのバージョン
version: "3"

# 作成するコンテナを定義
services:

  ## https-portalサービス
  https-portal:
    ### コンテナの名前
    container_name: feature_https-portal # <-- any name
    ### イメージを指定
    image: steveltn/https-portal
    ### ホストPC側のプログラムソースディレクトリをマウント
    volumes:
      - ./docker/https-portal:/var/lib/https-portal
    ports:
      - 80:80
      - 443:443
    restart: always
    environment:
      # - for prod -
      # DOMAINS: "example.com -> http://nginx" # <-- dimain
      # STAGE: "production"
      # - for dev -
      DOMAINS: "localhost -> http://nginx"
      STAGE: "local"

  ## phpサービス
  php:
    ### コンテナの名前
    container_name: feature_php # <-- any name
    ### コンテナの元になるDockerfileがおいてあるパス
    build: ./docker/php
    ### ホストPC側のプログラムソースディレクトリをマウント
    volumes:
      - ./src:/var/www
    ports:
      - "3000:3000"
      - "3001:3001"

  ## nginxサービス
  nginx:
    ### Nginxコンテナのもとになるイメージを指定
    image: nginx
    ### コンテナの名前
    container_name: feature_nginx # <-- any name
    ### ホスト側の80番ポートとコンテナ側の80番ポートをつなげる
    ports:
      - "8080:80"
      
    ### ホストPC側をnginxにマウント
    volumes:
      - ./src:/var/www
      - ./docker/nginx/conf.d/default.conf:/etc/nginx/conf.d/default.conf
    ### 依存関係
    depends_on:
      - php

  ## dbサービス
  db:
    ### イメージを指定
    image: mysql:5.7
    ### コンテナの名前 -> これがホスト名になるので.envでは「DB_HOST=feature_db」とする
    container_name: feature_db # <-- any name
    ### db設定
    environment:
      MYSQL_ROOT_PASSWORD: root
      #### .envで使うDB_DATABASEの値
      MYSQL_DATABASE: database
      #### .envで使うDB_USERNAMEの値
      MYSQL_USER: docker
      #### .envで使うDB_PASSWORDの値
      MYSQL_PASSWORD: docker
      #### timezoon
      TZ: "Asia/Tokyo"
    ### コマンドで設定
    command: mysqld --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci
    ### ホスト側のポートとコンテナ側のポートをつなげる
    volumes:
      - ./docker/db/data:/var/lib/mysql
      - ./docker/db/my.conf:/etc/mysql/conf.d/my.conf
      - ./docker/db/sql:/docker-entrypoint-initdb.d
    ### ホスト側のポートとコンテナ側のポートをつなげる
    ports:
      - 3306:3306

  ## redisサービス
  redis:
    ### イメージを指定
    image: redis:latest
    ### コンテナの名前 -> これがホスト名になるので.envでは「REDIS_HOST=feature_redis」とする
    container_name: feature_redis # <-- any name
    ### ホスト側のポートとコンテナ側のポートをつなげる
    ports:
      - 6379:6379
    ### ホスト側のポートとコンテナ側のポートをつなげる
    volumes:
      - ./docker/redis/data:/data
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
      DOMAINS: "www.example.com => https://example.com" # <-- dimain
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
