# docker

- dockerを使ったlaravel環境の作り方  
- 「https-portal」を使って「Let's Encrypt」でhttps化（ローカルではなし）
- 「laravel-mix」で必要な「node」、「npm」もインストール
- 「CACHE_DRIVER」、「QUEUE_CONNECTION」、「SESSION_DRIVER」ように「redis」も準備
- 画像編集用に「DG」も準備

## reference
[https-portal](https://github.com/SteveLTN/https-portal)  
[https-portal blog](https://re-engines.com/2019/03/28/docker-https-portal/)  

## conditions

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

### create setting files

`docker/db/conf/my.conf`

```conf:docker/db/conf/my.conf
  max_allowed_packet = 16M
  default-time-zone = 'Asia/Tokyo'
```

`docker/nginx/default.conf`

```conf:docker/nginx/default.conf
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

`docker/php/php.ini`

```ini:docker/php/php.ini
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

`docker/php/Dockerfile`

```Dockerfile:docker/php/Dockerfile
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

`docker-compose.yml`

```yml:docker-compose.yml
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
      # DOMAINS: "example.com -> http://nginx, www.example.com -> http://nginx" # <-- domain
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

### laravel envの準備

`.env`

```.env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:QVRzqfg8dRym9U3B4I4PhIeDD+sOUNNyRmYis9fESgE=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=feature_db
DB_PORT=3306
DB_DATABASE=database
DB_USERNAME=docker
DB_PASSWORD=docker

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DRIVER=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=feature_redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@exmple.com
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=
AWS_URL=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1

MIX_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
MIX_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"

```

## docker start at local

### docker start

```bash
docker-compose build
docker-compose up -d
docker-compose exec php bash

composer create-project "laravel/laravel=8.*" . --prefer-dist
# or
# laravelソースを持ってくる

composer install
composer dump-autoload
chmod 777 -R /var/www/storage

php artisan key:generate
php artisan cache:clear
php artisan config:clear
php artisan config:cache
php artisan storage:link

php artisan migrate
php artisan db:seed
php artisan queue:work
```
