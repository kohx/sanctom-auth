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

            return $this->responseFailed(trans('Reset request not found.'));
        }

        // config\auth.phpで設定した値を取得、ない場合はもとの値
        $this->expires = config('auth.reset_password_expires', $this->expires);

        // トークン期限切れチェック
        if ($this->tokenExpired($this->expires, $passwordReset->created_at)) {

            return $this->responseFailed(trans('Token expired.'));
        }

        // success response
        return $this->responseSuccess(trans('Please change password.'));
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

            return $this->responseFailed(trans('Reset request not found.'));
        }

        // トークン期限切れチェック
        if ($this->checkTokenExpired($passwordReset)) {

            return $this->responseFailed(trans('Token expired.'));
        }

        // validate
        $this->validateReset($request);

        // change password
        $user = $this->changePassword($request, $passwordReset);

        // ログインさせる
        // auth()->loginUsingId($user->id, true);

        // success login response
        return $this->responseSuccess(trans('Password changed.'), [
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
