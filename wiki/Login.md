# Login

## サンプルリポジトリ

[basic(Login)](https://github.com/kohx/sanctom-auth/releases/tag/v1.0)  

### サンプルから始める場合

```bash
composer install
npm i -g npm
npm i
php artisan key:generate
php artisan config:clear
php artisan config:cache
npm run watch
```

## 参考サイト

- [Laravel Sanctum でSPA(クッキー)認証する](https://qiita.com/ucan-lab/items/3e7045e49658763a9566)
- [Laravel 8.x Laravel Sanctum](https://readouble.com/laravel/8.x/ja/sanctum.html)
- [Laravel Sanctum](https://laravel.com/docs/8.x/sanctum)

- [API開発・テスト便利ツール Postmanの使い方メモ](https://qiita.com/zaburo/items/16ac4189d0d1c35e26d1)

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
```

### .envの設定

```.env
#...
APP_URL=https://localhost:3000

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
FILESYSTEM_DRIVER=redis
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
ローカル環境では設定は不要だが、設定する場合は`SANCTUM_STATEFUL_DOMAINS=localhost:3000`のように設定

```.env
SANCTUM_STATEFUL_DOMAINS=api.example.com:443
＃ OR
SANCTUM_STATEFUL_DOMAINS=api.example.com:443
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

- ベースURLに`api`を追加
- SPA側で axios を使う場合は withCredentials オプションを有効にする

 `resources\js\bootstrap.js`

```javascript
    window._ = require('lodash');

    window.axios = require('axios');

    window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'; // 追加

    // ベースURLに api を追加
    window.axios.defaults.baseURL = `api`; // 追加

    // 自動的にクッキーをクライアントサイドに送信
    window.axios.defaults.withCredentials = true; // 追加
```

### キャッシュクリア

```bash
php artisan config:cache
```

## 認証機能を作成

### コントローラを作成

```bash
php artisan make:controller Auth/LoginController
```

### ルートの作成

#### apiのルート

 `routes\api.php`

```php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;

Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout']);


Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::post('/user', function (Request $request) {
        return response()->json([
            'message' => 'Logged in',
            'user' => $request->user(),
        ], 200);
    });

    Route::get('/test', function () {
        return response()->json([
            'message' => 'Authenticated',
        ], 200);
    });
});
```

#### vueのルート

 `routes\web.php`

```php
<?php

use Illuminate\Support\Facades\Route;

// API以外はindexを返すようにして、VueRouterで制御
Route::get('/{any?}', function () {
        return view('index');
})->where('any', '.+');
```

#### routeの確認

```bash
php artisan route:list

# output
+--------+----------+---------------------+------+------------------------------------------------------------+------------------------------------------+
| Domain | Method   | URI                 | Name | Action                                                     | Middleware                               |
+--------+----------+---------------------+------+------------------------------------------------------------+------------------------------------------+
|        | POST     | api/login           |      | App\Http\Controllers\Auth\LoginController@login            | api                                      |
|        | POST     | api/logout          |      | App\Http\Controllers\Auth\LoginController@logout           | api                                      |
|        | GET|HEAD | api/test            |      | Closure                                                    | api                                      |
|        |          |                     |      |                                                            | App\Http\Middleware\Authenticate:sanctum |
|        | POST     | api/user            |      | Closure                                                    | api                                      |
|        |          |                     |      |                                                            | App\Http\Middleware\Authenticate:sanctum |
|        | GET|HEAD | sanctum/csrf-cookie |      | Laravel\Sanctum\Http\Controllers\CsrfCookieController@show | web                                      |
|        | GET|HEAD | {any?}              |      | Closure                                                    | web                                      |
+--------+----------+---------------------+------+------------------------------------------------------------+------------------------------------------+
```

### コントローラを編集

ログインの流は /csrf-cookie で返却されたXSRFクッキーの中にあるXSRFトークンをX-XSRF-TOKENヘッダにXSRFトークン入れて送る  
これについてはAxiosが自動で行ってくれる

`app\Http\Controllers\Auth\LoginController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\ThrottlesLogins;

use Illuminate\Validation\ValidationException;

final class LoginController extends Controller
{
    use ThrottlesLogins;

    // ログイン試行回数（回）
    protected $maxAttempts = 3;

    // ログインロックタイム（分）
    protected $decayMinutes = 1;

    /**
     * @param Request $request
     * @return Json
     * @throws Exception
     */
    public function login(Request $request)
    {
        // validate
        $this->validateLogin($request);

        // too many login
        if (method_exists($this, 'hasTooManyLoginAttempts') && $this->hasTooManyLoginAttempts($request)) {

            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        // check login
        if ($this->attemptLogin($request)) {

            // success login response
            return $this->sendSuccessLoginResponse($request);
        }

        // failed login response
        $this->incrementLoginAttempts($request);
        return $this->sendFailedLoginResponse($request);
    }

    /**
     * @param Request $request
     * @return Json
     */
    public function logout(Request $request)
    {
        $this->getGuard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out'], 200);
    }

    /**
     * get guard
     *
     * @return Guard
     */
    private function getGuard()
    {
        return Auth::guard(config('auth.defaults.guard'));
    }

    /**
     * Get the login username to be used by the controller.
     * ユーザネームをemailにするかnameにするか
     *
     * @return string
     */
    protected function username()
    {
        return 'email';
    }

    /**
     * Validate the user login request.
     * usernameとpasswordのバリデーション
     *
     * @param  Request $request
     * @return Void
     *
     * @throws ValidationException
     */
    private function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
            'remember' => 'boolean',
        ]);
    }

    /**
     * Get the needed authorization credentials from the request.
     * 認証に使うパラメータを取得
     *
     * @param  Request $request
     * @return Array
     */
    private function credentials(Request $request)
    {
        return $request->only($this->username(), 'password');
    }

    /**
     * Attempt to log the user into the application.
     * ログインさせる
     *
     * @param  Request $request
     * @return bool
     */
    private function attemptLogin(Request $request)
    {
        return $this->getGuard()->attempt(
            $this->credentials($request),
            $request->filled('remember')
        );
    }

    /**
     * Send the response after the user was authenticated.
     * ログイン成功のレスポンス
     *
     * @param  Request $request
     * @return Json
     */
    private function sendSuccessLoginResponse(Request $request)
    {
        $request->session()->regenerate();
        $this->clearLoginAttempts($request);

        return response()->json([
            'message' => 'Logged in',
            'user' => $request->user(),
        ], 200);
    }

    /**
     * Get the failed login response instance.
     * ログイン失敗のレスポンス
     *
     * @throws ValidationException
     */
    private function sendFailedLoginResponse()
    {
        // throw new Exception('ログインに失敗しました。再度お試しください');

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }
}
```

### vueの準備

VueでApiをチェックするので以下のファイルを準備する

#### webpack.mix.js

```javascript
const mix = require('laravel-mix');
const path = require('path');

mix.webpackConfig({
        // @のパスを作成
        resolve: {
            alias: {
                '@': path.resolve(__dirname, 'resources/js/'),
            },
        }
    })
    .js("resources/js/app.js", "public/js")
    .vue();

mix.browserSync({
    // アプリの起動アドレスを「nginx」
    proxy: "nginx",
    // ブラウザを自動で開かないようにする
    open: false
})
```

#### resources\views\index.blade.php

```php
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>{{ config('app.name') }}</title>
  <meta name="Keywords" content="sanctum" />
  <meta name="description" content="sanctum" />

  <!-- js -->
  <script src="{{ mix('js/app.js') }}" defer></script>
</head>
<body>
  <div id="app"></div>
</body>
</html>

```

#### resources\js\app.js

```javascript
import "./bootstrap"
import Vue from "vue"
// ルートコンポーネントをインポート
import App from "./App.vue"
// ルーターをインポート
import router from "./router"

const createApp = async () => {

    new Vue({
        el: "#app",
        router,
        components: {
            App
        },
        template: "<App />"
    })
}

createApp()
```

#### resources\js\App.vue

```vue
<template>
    <div>
        <RouterView />
    </div>
</template>
```

#### resources\js\router.js

```javascript
import Vue from 'vue'
import VueRouter from 'vue-router'

import Home from '@/pages/front/Home.vue'
import Test from '@/pages/front/Test.vue'
import Login from '@/pages/front/Login.vue'

// VueRouterをVueで使う
Vue.use(VueRouter)

// パスとページの設定
const routes = [
    // Home
    {
        // ルートネーム
        name: 'home',
        // urlのパス
        path: '/',
        // インポートしたページ
        component: Home,
    },
    // Test
    {
        // ルートネーム
        name: 'test',
        // urlのパス
        path: '/test',
        // インポートしたページ
        component: Test,
    },
    // Login
    {
        // ルートネーム
        name: 'login',
        // urlのパス
        path: '/Login',
        // インポートしたページ
        component: Login,
    }
]

// VueRouterインスタンス
const router = new VueRouter({
    // いつもどうりのURLを使うために「history」モードにする
    mode: 'history',
    routes
})

// VueRouterインスタンスをエクスポート
export default router
```

#### resources\js\pages\front\Home.vue

```vue
<template>
  <div class="container">
    <h1>Home</h1>
    <router-link :to="{ name: 'login'}">Login</router-link>
    <router-link :to="{ name: 'test'}">Test</router-link>
  </div>
</template>
```

#### resources\js\pages\front\Login.vue

```vue
<template>
  <div class="container">
    <h1>Login</h1>
    <router-link :to="{ name: 'home'}">Home</router-link>
    <router-link :to="{ name: 'test'}">Test</router-link>
    <form @submit.prevent="login">
      <input type="email" name="email" v-model="loginForm.email" />
      <input type="password" name="password" v-model="loginForm.password" />
      <button type="submit">login</button>
    </form>
    <div>{{ user.id }}</div>
    <div>{{ user.name }}</div>
    <div>{{ user.email }}</div>
    <button @click="logout">logout</button>
  </div>
</template>

<script>
export default {
  name: "Home",
  data() {
    return {
      user: {
        id: null,
        name: null,
        email: null,
      },
      loginForm: {
        email: "user1@example.com",
        password: "password",
        remember: true,
      },
    };
  },
  methods: {
    async login() {
      // get token
      await axios.get("csrf-cookie");

      // login
      const { data, status } = await axios.post("login", this.loginForm);
      if (status === 200) {
        this.user.id = data.user.id;
        this.user.name = data.user.name;
        this.user.email = data.user.email;
        alert(data.message);
      }
    },
    async logout() {
      // logout
      const { data, status } = await axios.post("/logout");
      if (status === 200) {
        this.user.id = null;
        this.user.name = null;
        this.user.email = null;
        alert(data.message);
      }
    },
  },
  async created() {
    // get user
    const { data, status } = await axios.post("/user");
    if (status === 200) {
      this.user.id = data.user.id;
      this.user.name = data.user.name;
      this.user.email = data.user.email;
    } else {
      this.user.id = null;
      this.user.name = null;
      this.user.email = null;
    }
  },
};
</script>
```

#### resources\js\pages\front\Test.vue

```vue
<template>
  <div class="container">
    <h1>Test</h1>
    <div>{{message}}</div>
    <router-link :to="{ name: 'home'}">Home</router-link>
    <router-link :to="{ name: 'login'}">Login</router-link>
  </div>
</template>

<script>
export default {
  name: "Home",
  data() {
    return {
      message: 'Unauthorized'
    };
  },
  async created() {
    // get user
    const { data, status } = await axios.get("/test");
    if (status === 200) {
      this.message = data.message;
    }
  },
};
</script>
```

### テストユーザの作成

tinkerで作成

```bash
php artisan tinker

App\Models\User::factory()->create(['email' => 'user1@example.com']);
App\Models\User::factory()->create(['email' => 'user2@example.com']);
App\Models\User::factory()->create(['email' => 'user3@example.com']);

```

### ビルドする

```bash
npm run dev
npm run dev
npm run watch
```

### npm run div or watch でエラーが出る場合

```bash
composer dump-autoload
rm -rf node_modules
rm package-lock.json yarn.lock
npm cache clean --force
npm install
```
