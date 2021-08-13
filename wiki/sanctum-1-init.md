# Sanctum

* Sanctumは、APIトークン認証とSPA認証がある
* Sanctumは、Laravelの組み込みのクッキーベースのセッション認証サービスを利用するので「web」認証ガードを使用
* 「web」認証ガードを使用することでCSRF保護、セッション認証、XSSを介した認証資格情報の漏洩を保護
* Sanctumは受信HTTPリクエストを調べるとき、最初に認証クッキーをチェックし、存在しない場合は、有効なAPIトークンのAuthorizationヘッダを調べる

今回はSPA認証を使用
## 参考サイト

* [Laravel Sanctum でSPA(クッキー)認証する](https://qiita.com/ucan-lab/items/3e7045e49658763a9566)
* [Laravel 8.x Laravel Sanctum](https://readouble.com/laravel/8.x/ja/sanctum.html)
* [Laravel Sanctum](https://laravel.com/docs/8.x/sanctum)
* [API開発・テスト便利ツール Postmanの使い方メモ](https://qiita.com/zaburo/items/16ac4189d0d1c35e26d1)

## 準備

### dockder

```bash
docker-compose up -d --build
docker-compose exec php bash
```

### laraveのインストールとパッケージのインストール

```bash
composer create-project --prefer-dist laravel/laravel .
composer require laravel/ui
composer require laravel/sanctum
# npm-check-updatesインストール
npm i -g npm-check-updates
# モジュールアップデート確認
ncu -u
# モジュールアップデート
npm update

# vue3
# laravel-mix < 6.0.6
npm install -save-dev laravel-mix@next vue@next
# laravel-mix >= 6.0.6
npm install -save-dev vue@next
# vue router
npm i vue-router@next
```

### .envの設定

```.env
#...
APP_URL=https://localhost:3000

MIX_URL="${APP_URL}"

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=samctum_db
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

SESSION_DOMAIN=localhost
SANCTUM_STATEFUL_DOMAINS=localhost:3000

REDIS_HOST=samctum_redis
REDIS_PASSWORD=null
REDIS_PORT=6379
#...
```

#### SANCTUM_STATEFUL_DOMAINS

SPA(Vue, React等)からリクエストを行うドメインを設定する必要があるので
`config/sanctum.php` の `stateful` を確認して以下のようになっているかを確認

`config/sanctum.php`

```php

    //...
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
    ))),
    //...

    // このような配列になる
    //     config('sanctum.stateful') => [
    //      "localhost",
    //      "localhost:3000",
    //      "127.0.0.1",
    //      "127.0.0.1:8000",
    //      "::1",
    //      "localhost",
    //    ]
```

`config('sanctum.stateful')` を設定する必要があるので各環境に合わせて .env にドメインとポート番号を設定
ローカル環境では設定は不要だが、設定する場合は `SANCTUM_STATEFUL_DOMAINS=localhost:3000` のように設定

```.env
SANCTUM_STATEFUL_DOMAINS=www.example.com:443
＃ OR
SANCTUM_STATEFUL_DOMAINS=localhost:3000

```

#### SESSION_DOMAIN

Laravelアプリケーションのセッションクッキードメイン設定するために `config/session.php` を確認

`config/session.php`

```php
    //...
    'domain' => env('SESSION_DOMAIN', null),
    //...
```

`.env` に `SESSION_DOMAIN` を追加する**ドメインの先頭に . を付けること**を忘れない  
null の場合、サブドメイン間でCookieの共有ができない

```.env
SESSION_DOMAIN=.example.com

```

#### SESSION_DRIVER

セッションドライバを`cookie`又は`redis`にする  
`cookie`は制限があるので`redis`を推奨

```.env
SESSION_DRIVER=redis
```

### マイグレート

```bash
php artisan migrate
```

### npmパッケージのインストール

```bash
npm i -g npm
npm i
npm i vue-router
```

### sanctum用の設定ファイルとマイグレーションファイルを作成

`php artisan vendor:publish` コマンドを使ってsanctumで利用する設定ファイルとsanctum用のテーブルを作成するマイグレーションファイルを作成

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

    # output
    Copied Directory [/vendor/laravel/sanctum/database/migrations] To [/database/migrations]
    Copied File [/vendor/laravel/sanctum/config/sanctum.php] To [/config/sanctum.php]
    Publishing complete.
```

以下のファイル `2019_12_14_000001_create_personal_access_tokens_table.php` と `sanctum.php` が作成される

### マイグレーションファイルの削除

`database\migrations\2019_12_14_000001_create_personal_access_tokens_table.php` は使用しないので削除する

```bash
rm database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php
```

### apiミドルウェアを追加

* SPAからの受信リクエストがLaravelのセッションクッキーを使用して認証できるようになる
* サードパーティまたはモバイルアプリケーションからのリクエストがAPIトークンを使用して認証できるようにする役割を果たす

 `app\Http\Kernel.php`

```php
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

    //...
    'api' => [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class, // 追記
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
    //...

```

### CORSとクッキー

レスポンスヘッダの Access-Control-Allow-Credentials が `true` を返すように設定

 `config\cors.php`

```php
    //...
    'paths' => ['api/*','sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // 変更
    //...
```

### axiosの設定

* ベースURLに`api`を追加
* SPA側で axios を使う場合は withCredentials オプションを有効にする

 `resources\js\bootstrap.js`

```javascript
    window._ = require('lodash');

    window.axios = require('axios');

    window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'; // 追加

    // ベースURLの設定
    const baseUrl = process.env.MIX_URL;

    // ベースURLに api を追加
    window.axios.defaults.baseURL = `${baseUrl}/api/`;

    // 自動的にクッキーをクライアントサイドに送信
    window.axios.defaults.withCredentials = true;

    // requestの設定
    window.axios.interceptors.request.use(config => {

        return config;
    });
```

### キャッシュクリア

```bash
php artisan config:cache
```
