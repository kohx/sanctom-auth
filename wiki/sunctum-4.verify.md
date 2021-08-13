# sanctum verify

## Verifyコントローラ

基本的なメソッドは`AuthController`にあるのでエクステンドする

```bash
php artisan make:controller Auth/VerifyController
```

`app\Http\Controllers\Auth\VerifyController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use App\Models\RegisterUser;
use App\Models\User;

class VerifyController extends AuthController
{
    /**
     * Complete registration
     * 登録を完了させる
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function verify(Request $request)
    {
        // already logged in
        $this->alreadyLogin($request);

        // 仮登録のデータをトークンで取得
        $registerUser = $this->getRegisterUser($request->token);

        // 取得できなかった場合
        if (!$registerUser) {

            return $this->responseFailed('register not found.');
        }

        // 仮登録のデータでユーザを作成
        $user = $this->createUser($registerUser->toArray());

        // event
        event(new Registered($user));

        // 作成したユーザをログインさせる
        auth()->loginUsingId($user->id, true);

        // success login response
        return $this->responseSuccess('Logged in.', [
            'user' => $request->user()
        ]);
    }

    /**
     * getRegisterUser
     *
     * @param mixed $token
     * @return RegisterUser
     */
    private function getRegisterUser($token)
    {
        // トークンで仮登録ユーザデータを取得
        $registerUser = RegisterUser::where('token', $token)->first();

        // 取得できた場合は仮登録データを削除
        if ($registerUser) {

            RegisterUser::destroy($registerUser->email);
        }

        // モデルを返す
        return $registerUser;
    }

    /**
     * createUser
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function createUser(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'email_verified_at' => now(),
            'password' => $data['password'],
        ]);

        return $user;
    }
}
```

## ルートの追加

`routes\api.php`

```php
//...
use App\Http\Controllers\Auth\VerifyController;
//...
Route::post('/verify', [VerifyController::class, 'verify']);
// ...
```

## vueの作成

`resources\js\pages\auth\Verify.vue`

```vue
<template>
  <div class="container">
    <h1>Verify</h1>
    <Nav />
    <Message :title="message" :contents="errors" @close="close" />
  </div>
</template>

<script>
import Nav from "@/components/Nav.vue";
import Message from "@/components/Message.vue";
export default {
  name: "Verify",
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
      message: null,
      errors: null,
    };
  },
  methods: {
    close() {
      this.message = null;
      this.errors = null;
    },
  },
  async created() {
    const { data, status } = await axios.post("verify", {
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
import Register from '@/pages/auth/Verify.vue'
//...
    // Register
    {
        name: 'register',
        path: '/register',
        component: Register,
    },
    // Verify 追加
    {
        // ルートネーム
        name: 'verify',
        // urlのパス
        path: '/verify/:token',
        // インポートしたページ
        component: Verify,
        props: true,
    },
//...
```
