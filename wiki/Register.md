# Register

## サンプルリポジトリ

[Register](https://github.com/kohx/sanctom-auth/releases/tag/v1.0.1)  

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

## .envの設定

テスト用に「mailtrap.io」のメールの設定をする。

```.env

#...

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=xxxxxxxxxxxxxx
MAIL_PASSWORD=xxxxxxxxxxxxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@example.com
MAIL_FROM_NAME="${APP_NAME}"

#...

```

## モデルとテーブルの作成

```bash
php artisan make:model RegisterUser --migration
```

### マイグレーション

`database\migrations\xxxx_xx_xx_xxxxxx_create_register_users_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RegisterUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('register_users', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->string('name');
            $table->string('password');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('register_users');
    }
}
```

### モデル

`app\Models\RegisterUser.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegisterUser extends Model
{
    // テーブル名を指定
    protected $table = 'register_users';

    // プライマリキーを「email」に変更
    // デフォルトは「id」
    protected $primaryKey = 'email';

    // プライマリキーのタイプを指定
    protected $keyType = 'string';

    // タイプがストリングの場合はインクリメントを「false」にしないといけない
    public $incrementing = false;

    // モデルが以下のフィールド以外を持たないようにする
    protected $fillable = [
        'email',
        'name',
    ];

    // タイムスタンプは「created_at」のフィールドだけにしたいので、「false」を指定
    public $timestamps = false;

    // 自前で用意する
    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }
}
```

## コントローラ

```bash
php artisan make:controller Auth/RegisterController
```

`app\Http\Controllers\Auth\RegisterController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\RegisterUser;
use App\Mail\VerificationMail;

class RegisterController extends Controller
{
    /**
     * Send Register Link Email
     * 送られてきた内容をテーブルに保存して認証メールを送信
     *
     * @param Request $request
     * @return RegisterUser
     */
    public function register(Request $request)
    {
        // validation
        $this->validateRegister($request);

        // create token
        $token = $this->createToken();

        // set data
        $registerUser = $this->setRegisterUser($request, $token);

        // send email
        $this->sendVerificationMail($registerUser);

        return response()->json([
            'message' => 'send email',
        ], 200);
    }

    /**
     * Get a validator for an incoming registration request.
     * バリデーション
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validateRegister(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * create activation token
     * トークンを作成する
     * @return string
     */
    private function createToken()
    {
        return hash_hmac('sha256', Str::random(40), config('app.key'));
    }

    /**
     * delete old data and insert data ata register user table
     * 古いデータが有れば削除して新しいデータをインサート
     * @param Request $request
     */
    private function setRegisterUser(Request $request, $token)
    {
        // delete old data
        // 同じメールアドレスが残っていればテーブルから削除
        RegisterUser::destroy($request->email);

        // insert
        // RegisterUser instance
        $registerUser = new RegisterUser($request->all());

        // set token
        $registerUser->token = $token;

        // set hash password
        $registerUser->password = Hash::make($request->password);

        // RegisterUser instance save
        $registerUser->save();

        return $registerUser;
    }

    /**
     * send verification mail
     * メールクラスでメールを送信
     *
     * @param User $registerUser
     * @return void
     */
    private function sendVerificationMail($registerUser)
    {
        Mail::to($registerUser->email)
            ->send(new VerificationMail($registerUser->token));
        // ->queue(new VerificationMail($registerUser->token));
    }
}
```

## メールの作成

### メールクラス

```bash
php artisan make:mail VerificationMail
```

`app\Mail\VerificationMail.php`

```php
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $token;
    protected $verifyRoute = 'verify';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($token)
    {
        // 引数でトークンを受け取る
        $this->token = $token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // 件名
        $subject = 'Verification mail';

        // コールバックURLをルート名で取得
        //TODO: これだとホットリロードでホストがおかしくなる
        // $url = route('verification', ['token' => $this->token]);

        // .envの「APP_URL」に設定したurlを取得
        $baseUrl = config('app.url');
        $token = $this->token;
        $url = "{$baseUrl}/{$this->verifyRoute}/{$token}";

        // 送信元のアドレス
        // .envの「MAIL_FROM_ADDRESS」に設定したアドレスを取得
        $from = config('mail.from.address');

        // メール送信
        return $this
            ->from($from)
            ->subject($subject)
            // 送信メールのビュー
            ->view('mails.verification_mail')
            // ビューで使う変数を渡す
            ->with('url', $url);
    }
}

```

### メール用のレイアウトブレード

`resources\views\layouts\mail.blade.php`

```php
<html>
    <head>
        <title>@yield('title')</title>
    </head>
    <body>
        @yield('content')
    </body>
</html>
```

### VerificationMailのブレード

`resources\views\mails\verification_mail.blade.php`

```php
@extends('layouts.mail')

@section('title', __('registration certification'))

@section('content')
<div>
    <div>{{ __('registration certification') }}</div>

        <a href='{{$url}}'>{{ __('please click this link to verify your email.') }}</a>
</div>
@endsection
```

## ルートの追加

`routes\api.php`

```php

//...

use App\Http\Controllers\Auth\RegisterController;

//...

Route::post('/register', [RegisterController::class, 'register']);

// ...

```

## vue

### resources\js\pages\front\Home.vue

```vue
<template>
  <div class="container">
    <h1>Home</h1>
    <router-link :to="{ name: 'login'}">Login</router-link>
    <router-link :to="{ name: 'register'}">Register</router-link> <!-- 追加 -->
    <router-link :to="{ name: 'test'}">Test</router-link>
  </div>
</template>
```

### resources\js\pages\front\Login.vue

```vue

<!-- ... -->

    <router-link :to="{ name: 'register'}">Register</router-link>

<!-- ... -->

```

### resources\js\pages\auth\Register.vue

```vue
<template>
  <div class="container">
    <h1>Register</h1>
    <router-link :to="{ name: 'home'}">Home</router-link>
    <router-link :to="{ name: 'login'}">Login</router-link>
    <form @submit.prevent="register">
      <input
        type="name"
        name="name"
        v-model="registerForm.name"
        placeholder="name"
      />
      <input
        type="email"
        name="email"
        v-model="registerForm.email"
        placeholder="email"
      />
      <input
        type="password"
        name="password"
        v-model="registerForm.password"
        placeholder="password"
      />
      <input
        type="password"
        name="password_confirmation"
        v-model="registerForm.password_confirmation"
        placeholder="password confirmation"
      />
      <button type="submit">register</button>
    </form>
  </div>
</template>

<script>
export default {
  name: "Register",
  data() {
    return {
      registerForm: {
        name: "user0",
        email: "user0@example.com",
        password: "11111111",
        password_confirmation: "11111111",
      },
    };
  },
  methods: {
    async register() {
      const { data, status } = await axios.post("register", this.registerForm);
      if (status === 200) {
        alert(data.message);
      }
    },
  },
};
</script>
```

### resources\js\router.js

```javascript

//...

import Register from '@/pages/auth/Register.vue'

//...

    // register
    {
        path: '/register',
        name: 'register',
        meta: {
            icon: 'user-plus'
        },
        component: Register,
    },

//...

```

## queueを使用する場合

```bash
php artisan queue:work
```
