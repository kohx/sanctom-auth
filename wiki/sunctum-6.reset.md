# sanctum reset

## Resetコントローラ

基本的なメソッドは`AuthController`にあるのでエクステンドする

```bash
php artisan make:controller Auth/ResetController
```

`app\Http\Controllers\Auth\ResetController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PasswordReset;
use App\Models\User;

class ResetController extends AuthController
{
    // server\config\auth.phpで設定していない場合のデフォルト
    protected $expires = 60;

    /**
     * resetPassword
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws HttpException
     */
    public function reset(Request $request)
    {
        // already logged in
        $this->alreadyLogin($request);

        // トークンがあるかチェック
        $passwordReset = $this->getPasswordReset($request->token);
        if (!$passwordReset) {

            return $this->responseFailed('reset request not found.');
        }

        // config\auth.phpで設定した値を取得、ない場合はもとの値
        $this->expires = config('auth.reset_password_expires', $this->expires);

        // トークン期限切れチェック
        if ($this->tokenExpired($this->expires, $passwordReset->created_at)) {

            return $this->responseFailed('token expired.');
        }

        // success response
        return $this->responseSuccess('in reset password');
    }

    /**
     * resetPassword
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws HttpException
     */
    public function change(Request $request)
    {
        // already logged in
        $this->alreadyLogin($request);

        // トークンがあるかチェック
        $passwordReset = $this->getPasswordReset($request->token);
        if (!$passwordReset) {

            return $this->responseFailed('reset request not found.');
        }

        // トークン期限切れチェック
        if ($this->checkTokenExpired($passwordReset)) {

            return $this->responseFailed('token expired.');
        }

        // validate
        $this->validateReset($request);

        // change password
        $user = $this->changePassword($request, $passwordReset);

        // ログインさせる
        // auth()->loginUsingId($user->id, true);

        // success login response
        return $this->responseSuccess('password changed.', [
            // 'user' => $request->user()
        ]);
    }

    /**
     * checkTokenExpired
     *
     * @param PasswordReset $passwordReset
     * @return bool
     */
    private function checkTokenExpired(PasswordReset $passwordReset): bool
    {
        // config\auth.phpで設定した値を取得、ない場合はもとの値
        $this->expires = config('auth.reset_password_expires', $this->expires);

        // トークン期限切れチェック
        return $this->tokenExpired($this->expires, $passwordReset->created_at);
    }

    /**
     * getPasswordReset
     *
     * @param mixed $token
     * @return PasswordReset
     */
    private function getPasswordReset($token)
    {
        // トークンで仮登録ユーザデータを取得
        $passwordReset = PasswordReset::where('token', $token)->first();

        // モデルを返す
        return $passwordReset;
    }

    /**
     * changePassword
     *
     * @param Request $request
     * @param PasswordReset $passwordReset
     * @return User
     */
    private function changePassword(Request $request, PasswordReset $passwordReset) : User
    {
        // get user from passwordReset email
        $user = User::where('email', $passwordReset->email)->first();

        $user = DB::transaction(function () use ($request, $user) {

            // リセットパスワードテーブルからデータを削除
            PasswordReset::destroy($user->email);

            // password update
            $user->password = $this->passwordHash($request->password);
            $user->save();

            // ユーザを返却
            return $user;
        });

        return $user;
    }
}
```

## config追加

今回は`tokenExpired`を自前で作ったので、コンフィグに値を追加しておく

`config\auth.php`

```php
    /*
    |--------------------------------------------------------------------------
    | reset password expires
    |--------------------------------------------------------------------------
    |
    | 手動で設定
    |
    */

    'reset_password_expires' => 60,
```

## ルートの追加

`routes\api.php`

```php
//...
use App\Http\Controllers\Auth\ResetController;
//...
Route::post('/reset', [ResetController::class, 'reset']);
Route::post('/change', [ResetController::class, 'change']);
// ...
```

## vueの作成

`resources\js\pages\auth\Reset.vue`

```vue
<template>
  <div class="container">
    <h1>Reset</h1>
    <Nav />
    <Message :title="message" :contents="errors" @close="close" />

    <form @submit.prevent="reset">
      <input type="password" name="password" v-model="resetForm.password" />
      <input
        type="password"
        name="password_confirmation"
        v-model="resetForm.password_confirmation"
      />
      <button type="submit">reset</button>
    </form>
  </div>
</template>

<script>
import Nav from "@/components/Nav.vue";
import Message from "@/components/Message.vue";
export default {
  name: "Reset",
  components: {
    Nav,
    Message,
  },
  props: {
    token: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      resetForm: {
        password: "$Pw111111",
        password_confirmation: "$Pw111111",
      },
      message: null,
      errors: null,
    };
  },
  methods: {
    async reset() {
      this.resetForm.token = this.token;

      const { data, status } = await axios.post("change", this.resetForm);
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
  async created() {
    this.resetForm.token = this.token;
    const { data, status } = await axios.post("reset", {
      token: this.token,
    });
    if (status === 200) {
      this.message = data.message;
      this.errors = null;
    } else {
      this.message = data.message;
      this.errors = data.errors;
    }
  },
};
</script>
```

## vueルーター

```javascript
//...
import Reset from '@/pages/auth/Reset.vue'
//...
    // Verify
    {
        // ルートネーム
        name: 'verify',
        // urlのパス
        path: '/verify/:token',
        // インポートしたページ
        component: Verify,
        props: true,
    },
    // Reset 追加
    {
        // Reset
        name: 'reset',
        // urlのパス
        path: '/reset/:token',
        // インポートしたページ
        component: Reset,
        props: true,
    },
//...
```
