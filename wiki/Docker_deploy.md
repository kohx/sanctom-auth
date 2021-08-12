# docker deploy

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

```bash
sudo yum update -y
```

### dockerをインストール

```bash
sudo yum install -y docker
```

### docker サービスの起動

```bash
sudo systemctl start docker
sudo systemctl status docker
```

### 自動起動設定

```bash
sudo systemctl enable docker
```

### 自動起動設定確認

```bash
sudo systemctl list-unit-files | grep docker.service

# output
    docker.service          enabled
```

### ec2-user を docker グループに追加する

```bash
sudo usermod -a -G docker ec2-user
```

一度ログアウトし、再度ログインすると、 docker コマンドが利用可能になる。

```bash
exit
```

```bash
docker info

# output
Client:
 Context:    default
 Debug Mode: false
 Plugins:
  buildx: Build with BuildKit (Docker Inc., v0.5.1-docker)
  compose: Docker Compose (Docker Inc., v2.0.0-beta.6)
  scan: Docker Scan (Docker Inc., v0.8.0)

Server:
 Containers: 5
  Running: 5
  Paused: 0
  Stopped: 0
 Images: 5
 Server Version: 20.10.7
 Storage Driver: overlay2
  Backing Filesystem: extfs
  Supports d_type: true
  Native Overlay Diff: true
  userxattr: false
 Logging Driver: json-file
 Cgroup Driver: cgroupfs
 Cgroup Version: 1
 Plugins:
  Volume: local
  Network: bridge host ipvlan macvlan null overlay
  Log: awslogs fluentd gcplogs gelf journald json-file local logentries splunk syslog
 Swarm: inactive
 Runtimes: io.containerd.runc.v2 io.containerd.runtime.v1.linux runc
 Default Runtime: runc
 Init Binary: docker-init
 containerd version: d71fcd7d8303cbf684402823e425e9dd2e99285d
 runc version: b9ee9c6314599f1b4a7f497e1f1f856fe433d3b7
 init version: de40ad0
 Security Options:
  seccomp
   Profile: default
 Kernel Version: 5.10.25-linuxkit
 Operating System: Docker Desktop
 OSType: linux
 Architecture: x86_64
 CPUs: 2
 Total Memory: 1.941GiB
 Name: docker-desktop
 ID: TD62:O4WJ:EGLJ:DJXX:AZ2A:ATWW:ZCHK:J2XX:4EJK:VIPS:4S46:ITPU
 Docker Root Dir: /var/lib/docker
 Debug Mode: true
  File Descriptors: 81
  Goroutines: 75
  System Time: 2021-08-05T05:01:02.9228589Z
  EventsListeners: 3
 Registry: https://index.docker.io/v1/
 Labels:
 Experimental: false
 Insecure Registries:
  127.0.0.0/8
 Live Restore Enabled: false
```

### 一時的にスーパーユーザーになる

```bash
 sudo -i
```

--->  ここから一時的にスーパーユーザー

### docker-comoseをインストール

```bash
curl -L "https://github.com/docker/compose/releases/download/1.11.2/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose`
```

### docker-compose コマンドに実行権限付与

```bash
chmod +x /usr/local/bin/docker-compose
```

### スーパーユーザーを抜ける

```bash
exit
```

<--- スーパーユーザーここまで

### docker-compose コマンドの実行確認

```bash
docker-compose --version
    docker-compose version 1.11.2, build dfed245
```

## docker-comoseの設定

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

### ディレクトリの作成

```bash
cd /var
sudo mkdir www
cd www
```

### dockerディレクトリの作成

```bash
sudo mkdir docker
sudo mkdir docker/db
sudo mkdir docker/db/conf
sudo mkdir docker/https-portal
sudo mkdir docker/nginx
sudo mkdir docker/php
```

### db my.conf

```bash
sudo touch docker/db/conf/my.conf
sudo vim docker/db/conf/my.conf
```

```conf
  [mysqld]
  max_allowed_packet = 16M
  default-time-zone = 'Asia/Tokyo'
```

### nginx default.conf

```bash
sudo touch docker/nginx/default.conf
sudo vim docker/nginx/default.conf
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
sudo touch docker/php/php.ini
sudo vim docker/php/php.ini
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
sudo touch docker/php/Dockerfile
sudo vim docker/php/Dockerfile
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
sudo touch docker-compose.yml
sudo vim docker-compose.yml
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
      DOMAINS: "example.com -> http://nginx, www.example.com -> http://nginx" # <-- domain
      STAGE: "production"
      # - for dev -
      # DOMAINS: "localhost -> http://nginx"
      # STAGE: "local"

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
      # - "3000:3000" # 閉じる
      # - "3001:3001" # 閉じる

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

### docker start

```bash
docker-compose build
docker-compose up -d
docker-compose exec php bash

composer create-project "laravel/laravel=8.*" . --prefer-dist
# or laravelソースを持ってくる

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
