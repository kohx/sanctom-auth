# sanctum login

## コントローラを作成

```bash
php artisan make:controller AuthController
php artisan make:controller Auth/LoginController
```

## ルートの作成

### apiのルート

 `routes\api.php`

```php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;

// auth 関係
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout']);

// sanctum
Route::group(['middleware' => ['auth:sanctum']], function () {

    // テスト
    Route::get('/test', function () {
        return response()->json([
            'message' => 'Authenticated',
        ], 200);
    });
});
```

### vueのルート

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

## コントローラを編集

### ベースとなるコントローラを編集

`app\Http\Controllers\AuthController.php`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\Password;

abstract class AuthController extends Controller
{
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
     * get guard
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    protected function getGuard()
    {
        return Auth::guard(config('auth.defaults.guard'));
    }

    /**
     * Get the needed authorization credentials from the request.
     * 認証に使うパラメータを取得
     *
     * @param  Request $request
     * @return Array
     */
    protected function credentials(Request $request)
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
    protected function attemptLogin(Request $request)
    {
        return $this->getGuard()->attempt(
            $this->credentials($request),
            $request->filled('remember')
        );
    }

    /**
     * password hash
     * パスワードのhash
     *
     * @param string $password
     * @return string
     */
    protected function passwordHash($password)
    {
        return Hash::make($password);
    }

    /**
     * create activation token
     * トークンを作成する
     * @return string
     */
    protected function createToken()
    {
        return hash_hmac('sha256', Str::random(40), config('app.key'));
    }

    /**
     * Determine if the token has expired.
     *
     * @param string $createdAt
     * @return bool
     */
    protected function tokenExpired($expires, $createdAt)
    {
        return Carbon::parse($createdAt)
            ->addSeconds($expires)
            ->isPast();
    }

    /**
     * alreadyLogin
     *
     * @param  Request $request
     * @param string|null $message
     * @return void
     *
     * @throws HttpException
     */
    protected function alreadyLogin(Request $request, string $message = null)
    {
        // set message
        $message = is_null($message) ? 'Already logged in.' : $message;

        // already logged in
        if (auth()->check()) {
            throw new HttpException(403, trans($message));
        }
    }

    /**
     * validateLogin
     *
     * @param  Request $request
     * @return void
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
            'remember' => 'boolean',
        ]);
    }

    /**
     * Validate the user register request.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validateRegister(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);
    }

    /**
     * Validate the forgot request.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validateForgot(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'exists:users,email'],
        ]);
    }

    /**
     * validateReset
     *
     * @param  Request $request
     * @return void
     */
    protected function validateReset(Request $request)
    {
        $request->validate([
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);
    }

    /**
     * responseSuccess
     * 成功のレスポンス
     *
     * @param string $message
     * @param array $additions
     * @return \Illuminate\Http\JsonResponse
     */
    protected function responseSuccess(string $message, array $additions = [])
    {
        return response()->json(array_merge(['message' => trans($message)], $additions), 200);
    }

    /**
     * responseFailed
     * 失敗のレスポンス
     *
     * @param string $message
     * @param array $additions
     * @return \Illuminate\Http\JsonResponse
     */
    protected function responseFailed(string $message)
    {
        return response()->json(['message' => trans($message)], 403);
    }

    /**
     * responseInvalid
     * インヴァリッドのレスポンス
     *
     * @param string $message
     * @param array $errors array in array
     * @return \Illuminate\Http\JsonResponse
     */
    protected function responseInvalid(string $message, array $errors = [])
    {
        foreach ($errors as &$error) {
            foreach ($error as &$value) {
                $value = trans($value);
            }
        }

        return response()->json([
            'message' => trans($message),
            'errors' => $errors,
        ], 422);
    }
}
```

### ログインコントローラを編集

ログインの流は /csrf-cookie で返却されたXSRFクッキーの中にあるXSRFトークンをX-XSRF-TOKENヘッダにXSRFトークン入れて送る  
これについてはAxiosが自動で行ってくれる  

基本的なメソッドは`AuthController`に書いてあるのでエクステンドする

 `app\Http\Controllers\Auth\LoginController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\ThrottlesLogins;

final class LoginController extends AuthController
{
    use ThrottlesLogins;

    // ログイン試行回数（回）
    protected $maxAttempts = 3;

    // ログインロックタイム（分）
    protected $decayMinutes = 1;

    /**
     * login
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws HttpException
     */
    public function login(Request $request)
    {
        // already logged in
        $this->alreadyLogin($request);

        // validate
        $this->validateLogin($request);

        // too many login
        if (method_exists($this, 'hasTooManyLoginAttempts') && $this->hasTooManyLoginAttempts($request)) {

            // event
            $this->fireLockoutEvent($request);

            // Lockout response
            return $this->sendLockoutResponse($request);
        }

        // check login
        if ($this->attemptLogin($request)) {

            // regenerate token
            $request->session()->regenerate();

            // ログイン失敗をリセット
            $this->clearLoginAttempts($request);

            // success login response
            return $this->responseSuccess('Logged in.', [
                'user' => $request->user()
            ]);
        }

        // ログイン試行をカウントアップ
        $this->incrementLoginAttempts($request);

        // fail login response
        return $this->responseInvalid('invalid data.', [
            $this->username() => [trans('auth.failed')],
        ]);
    }

    /**
     * logout
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // logout
        $this->getGuard()->logout();

        // session refresh
        $request->session()->invalidate();

        // regenerate token
        $request->session()->regenerateToken();

        // success login response
        return $this->responseSuccess('Logged out.');
    }
}
```

## vueの準備

VueでApiをチェックするので以下のファイルを準備する

### webpack.mix.js

`webpack.mix.js`

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
    .vue(); // vue3に対応

mix.browserSync({
    // アプリの起動アドレスを「nginx」
    proxy: "nginx",
    // ブラウザを自動で開かないようにする
    open: false
})
```

### index.blade.php

`resources\views\index.blade.php`

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

### resources\js\app.js

```javascript
import "./bootstrap"

// Vueインポート
import { createApp } from 'vue'

// ルートコンポーネントをインポート
import App from "./App.vue"

// ルーターをインポート
import router from "./router"

const app = async createApp(App)
    .use(router)
    .mount('#app')
```

### App.vue

`resources\js\App.vue`

```vue
<template>
    <div>
        <RouterView />
    </div>
</template>
```

### router.js

`resources\js\router.js`

```javascript
import Vue from 'vue'
import VueRouter from 'vue-router'

import Home from '@/pages/front/Home.vue'
import Test from '@/pages/front/Test.vue'
import Login from '@/pages/auth/Login.vue'

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

### Nav.vue

`src\resources\js\components\Nav.vue`

```php
<template>
  <nav class="nav">
    <h2 class="nav__title">menu</h2>
    <ul class="nav__list">
      <li class="nav__item">
        <router-link :to="{ name: 'login' }">Login</router-link>
      </li>
      <li class="nav__item">
        <router-link :to="{ name: 'register' }">Register</router-link>
      </li>
      <li class="nav__item">
        <router-link :to="{ name: 'test' }">Test</router-link>
      </li>
      <li class="nav__item">
        <router-link :to="{ name: 'forgot' }">Forgot</router-link>
      </li>
    </ul>
  </nav>
</template>

<script>
export default {
  name: "Nav",
};
</script>

<style scoped>
.nav {
}
.nav__title {
}
.nav__list {
    display: flex;
    list-style: none;
}
.nav__item {
    border: 1px solid gray;
}
.nav__item a {
    color: inherit;
    text-decoration: none;
    padding: 1rem;
}
</style>
```

### Message.vue

`src\resources\js\components\Message.vue`

```php
<template>
  <div class="message" v-if="title">
    <h3 class="message__title">{{ title }}</h3>
    <div class="message__content" v-for="(content, key) in contents" :key="key">
      <h4 v-if="key" class="message__content__title">{{ key }}</h4>
      <ul v-if="key" class="message__content__list">
        <li
          class="message__content__items"
          v-for="(value, index) in content"
          :key="index"
        >
          {{ value }}
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  name: "Message",
  props: {
    title: {
      type: String,
      default: null,
    },
    contents: {
      type: Object,
      default: null,
    },
    timeout: {
      type: Number,
      default: 5000,
    },
  },
  data() {
      return {
          id: null
      }
  },
  watch: {
    title: function (after, before) {
      clearTimeout(this.id);
      this.id = setTimeout(() => this.$emit("close"), this.timeout);
    },
  },
};
</script>

<style scoped>
.message {
  border: 1px solid cadetblue;
  padding: 1rem;
}
</style>
```

### Home.vue

`resources\js\pages\front\Home.vue`

```vue
<template>
  <div class="container">
    <h1>Home</h1>
    <Nav />
  </div>
</template>

<script>
import Nav from "@/components/Nav.vue";
export default {
  name: "Home",
  components: {
    Nav,
  },
};
</script>
```

#### Login.vue

`resources\js\pages\auth\Login.vue`

```vue
<template>
  <div class="container">
    <h1>Login</h1>
    <Nav />
    <Message :title="message" :contents="errors" @close="close" />

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
import Nav from "@/components/Nav.vue";
import Message from "@/components/Message.vue";
export default {
  name: "Login",
  components: {
    Nav,
    Message,
  },
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
      message: null,
      errors: null,
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
        this.message = data.message;
        this.errors = null;
      } else {
        this.message = data.message;
        this.errors = data.errors;
      }
    },
    async logout() {
      // logout
      const { data, status } = await axios.post("logout");
      if (status === 200) {
        this.user.id = null;
        this.user.name = null;
        this.user.email = null;
        this.message = data.message;
        this.errors = null;
      } else {
        this.message = data.message;
        this.errors = data.errors;
      }
    },
    close() {
      this.message = null;
      this.errors = null;
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

#### Test.vue

`resources\js\pages\front\Test.vue`

```vue
<template>
  <div class="container">
    <h1>Test</h1>
    <Nav />
    <Message :title="message" @close="close" />
  </div>
</template>

<script>
import Nav from "@/components/Nav.vue";
import Message from "@/components/Message.vue";
export default {
  name: "Test",
  components: {
    Nav,
    Message,
  },
  data() {
    return {
      message: "Unauthorized",
    };
  },
  methods: {
    close() {
      this.message = null;
    },
  },
  async created() {
    // get user
    const { data, status } = await axios.get("/test");
    if (status === 200) {
      this.message = data.message;
    } else {
      console.log(data.message);
    }
  },
};
</script>
```

## テストユーザの作成

tinkerで作成

```bash
php artisan tinker

App\Models\User::factory()->create(['email' => 'user1@example.com']);
App\Models\User::factory()->create(['email' => 'user2@example.com']);
App\Models\User::factory()->create(['email' => 'user3@example.com']);

```

## ビルドする

```bash
npm run dev
npm run dev
npm run watch
```

## npm run div or watch でエラーが出る場合

```bash
composer dump-autoload
rm -rf node_modules
rm package-lock.json yarn.lock
npm cache clean --force
npm install
```
