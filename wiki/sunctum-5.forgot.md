# sanctum forgot

## モデルの作成

PasswordResetモデルの作成

```bash
php artisan make:model PasswordReset
```

`app\Models\PasswordReset.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    use HasFactory;

    // テーブル名を指定
    protected $table = 'password_resets';

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
        'token',
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
php artisan make:controller Auth/ForgotController
```

`app\Http\Controllers\Auth\ForgotController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Models\PasswordReset;
use App\Mail\PasswordResetMail;

class ForgotController extends AuthController
{
    /**
     * forgot
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws HttpException
     */
    public function forgot(Request $request)
    {
        // already logged in
        $this->alreadyLogin($request);

        // validate
        $this->validateForgot($request);

        // create token
        $token = $this->createToken();

        // set data
        $passwordReset = $this->setPasswordReset($request, $token);

        // send email
        $this->sendPasswordResetMail($passwordReset);

        // success response
        return $this->responseSuccess('sent email.');
    }

    /**
     * setRegisterUser
     * 古いデータが有れば削除して新しいデータをインサート
     *
     * @param Request $request
     * @param string $token
     * @return PasswordReset
     */
    private function setPasswordReset(Request $request, string $token)
    {
        // delete old data
        // 同じメールアドレスが残っていればテーブルから削除
        PasswordReset::destroy($request->email);

        // insert
        // RegisterUser instance
        $passwordReset = new PasswordReset($request->all());
        $passwordReset->token = $token;
        $passwordReset->save();

        // reset password
        return $passwordReset;
    }

    /**
     * sendResetPasswordMail
     *
     * @param PasswordReset $passwordReset
     * @return void
     */
    private function sendPasswordResetMail(PasswordReset $passwordReset)
    {
        Mail::to($passwordReset->email)
            ->send(new PasswordResetMail($passwordReset->token));
            // ->queue(new PasswordResetMail($passwordReset->token));
    }
}
```

## メールの作成

### メールクラス

```bash
php artisan make:mail PasswordResetMail
```

`app\Mail\PasswordResetMail.php`

```php
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $token;
    protected $resetRoute = 'reset';

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
        $subject = __('reset password mail');

        // VueへのコールバックURLをルート名で取得
        $baseUrl = config('app.url');
        $token = $this->token;
        $url = "{$baseUrl}/{$this->resetRoute}/{$token}";

        return $this->subject($subject)
            // 送信メールのビュー
            ->view('mails.reset_password_mail')
            // ビューで使う変数を渡す
            ->with('url', $url);
    }
}
```

### reset passwordメール用のブレード

`resources\views\mails\reset_password_mail.blade.php`

```php
@extends('layouts.mail')

@section('title', __('password reset'))

@section('content')
<div>
    <div>{{ __('password reset') }}</div>

    <a href='{{$url}}'>{{ __('click this link to go to password reset.') }}</a>
</div>
@endsection
```

## ルートの追加

`routes\api.php`

```php
//...
use App\Http\Controllers\Auth\ForgotController;
//...
Route::post('/forgot', [ForgotController::class, 'forgot']);
// ...
```

## vueの作成

`resources\js\pages\auth\Forgot.vue`

```vue
<template>
  <div class="container">
    <h1>Forgot</h1>
    <Nav />
    <Message :title="message" :contents="errors" @close="close" />

    <form @submit.prevent="forgot">
      <input type="email" name="email" v-model="forgotForm.email" />
      <button type="submit">forgot</button>
    </form>
  </div>
</template>

<script>
import Nav from "@/components/Nav.vue";
import Message from "@/components/Message.vue";
export default {
  name: "Forgot",
  components: {
    Nav,
    Message,
  },
  data() {
    return {
      forgotForm: {
        email: "user1@example.com",
      },
      message: null,
      errors: null,
    };
  },
  methods: {
    async forgot() {
      const { data, status } = await axios.post("forgot", this.forgotForm);
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

## vueのルート

```javascript
//...
import Register from '@/pages/auth/Register.vue'
//...
    // Register
    {
        name: 'register',
        path: '/register',
        component: Register,
    },
    // Forgot 追加
    {
        // Forgot
        name: 'forgot',
        // urlのパス
        path: '/forgot',
        // インポートしたページ
        component: Forgot,
    },
//...
```
