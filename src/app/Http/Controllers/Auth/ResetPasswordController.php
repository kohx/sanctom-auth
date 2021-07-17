<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PasswordReset;
use App\Models\User;

class ResetPasswordController extends Controller
{
    // TODO: expire
    // server\config\auth.phpで設定していない場合のデフォルト
    protected $expires = 600 * 5;

    /**
     * resetPassword
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws HttpException
     */
    public function resetPassword(Request $request, $token)
    {
        // already logged in
        $this->alreadyLogin($request);

        // トークンがあるかチェック
        $passwordReset = $this->getPasswordReset($token);
        if ($passwordReset) {

            return $this->responseFailed('reset request not found.');
        }

        // config\auth.phpで設定した値を取得、ない場合はもとの値
        $this->expires = config('auth.reset_password_expires', $this->expires);

        // トークン期限切れチェック
        if ($this->tokenExpired($this->expires, $passwordReset->createdAt)) {

            return $this->responseFailed('token expired.');
        }

        // success response
        return $this->responseSuccess('in changePassword');
    }

    /**
     * resetPassword
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws HttpException
     */
    public function changePassword(Request $request, $token)
    {
        // TODO::
        // 'password' => ['required', 'confirmed', Rules\Password::defaults()],

        // already logged in
        $this->alreadyLogin($request);

        // トークンがあるかチェック
        $passwordReset = $this->getPasswordReset($token);
        if ($passwordReset) {

            return $this->responseFailed('reset request not found.');
        }

        // config\auth.phpで設定した値を取得、ない場合はもとの値
        $this->expires = config('auth.reset_password_expires', $this->expires);

        // トークン期限切れチェック
        if ($this->tokenExpired($this->expires, $passwordReset->createdAt)) {

            return $this->responseFailed('token expired.');
        }

        // validate
        $this->validateReset($request);

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

        // fail login response
        return $this->responseInvalid('invalid data.', [
            $this->username() => ['auth.failed']
        ]);
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
}
