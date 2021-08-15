# sanctum register

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

register_usersテーブルの作成と、
RegisterUserモデルの作成

```bash
php artisan make:model RegisterUser --migration
```

### マイグレーション作成

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

### Registerモデルの作成

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

## Registerコントローラ

基本的なメソッドは`AuthController`にあるのでエクステンドする

```bash
php artisan make:controller Auth/RegisterController
```

`app\Http\Controllers\Auth\RegisterController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Models\RegisterUser;
use App\Mail\VerificationMail;

class RegisterController extends AuthController
{
    /**
     * Register
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws HttpException
     */
    public function register(Request $request)
    {
        // already logged in
        $this->alreadyLogin($request);

        // validation
        $this->validateRegister($request);

        // create token
        $token = $this->createToken();

        // set data
        $registerUser = $this->setRegisterUser($request, $token);

        // send email
        $this->sendVerificationMail($registerUser);

        // success response
        return $this->responseSuccess('sent email.');
    }

    /**
     * setRegisterUser
     * 古いデータが有れば削除して新しいデータをインサート
     *
     * @param Request $request
     * @param string $token
     * @return RegisterUser
     */
    private function setRegisterUser(Request $request, string $token)
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
        $registerUser->password = $this->passwordHash($request->password);

        // RegisterUser instance save
        $registerUser->save();

        // registered user
        return $registerUser;
    }

    /**
     * sendVerificationMail
     *
     * @param RegisterUser $registerUser
     * @return void
     */
    private function sendVerificationMail(RegisterUser $registerUser)
    {
        Mail::to($registerUser->email)
            // ->send(new VerificationMail($registerUser->token));
            ->queue(new VerificationMail($registerUser->token));
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

        // VueへのコールバックURLをルート名で取得
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

### verificationメール用のブレード

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

## vueの作成

`resources\js\pages\auth\Register.vue`

```vue
<template>
  <div class="container">
    <h1>Register</h1>
    <Nav />
    <Message :title="message" :contents="errors" @close="close" />

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
import Nav from "@/components/Nav.vue";
import Message from "@/components/Message.vue";
export default {
  name: "Register",
  components: {
    Nav,
    Message,
  },
  data() {
    return {
      registerForm: {
        name: "asdf",
        email: "asdf@example.com",
        password: "Aa@111111",
        password_confirmation: "Aa@111111",
      },
      message: null,
      errors: null,
    };
  },
  methods: {
    async register() {
      const { data, status } = await axios.post("register", this.registerForm);
      if (status === 200) {
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
};
</script>
```

## vueルーター

```javascript
//...
import Register from '@/pages/auth/Register.vue'
//...
    // Login
    {
        // ルートネーム
        name: 'login',
        // urlのパス
        path: '/login',
        // インポートしたページ
        component: Login,
    },
    // Register 追加
    {
        name: 'register',
        path: '/register',
        component: Register,
    },
//...
```

## queueを使用する場合

```bash
php artisan queue:work
```
