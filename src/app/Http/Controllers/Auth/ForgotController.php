<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Models\PasswordReset;
use App\Mail\ResetPasswordMail;

class ForgotController extends AuthController
{
    /**
     * forget
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws HttpException
     */
    public function forget(Request $request)
    {
        // already logged in
        $this->alreadyLogin($request);

        // validate
        $this->validateReset($request);

        // create token
        $token = $this->createToken();

        // set data
        $resetPassword = $this->setResetPassword($request, $token);

        // send email
        $this->sendResetPasswordMail($resetPassword);

        // success response
        return $this->responseSuccess('sent email.');
    }

    /**
     * setRegisterUser
     * 古いデータが有れば削除して新しいデータをインサート
     *
     * @param Request $request
     * @param string $token
     * @return ResetPassword
     */
    private function setRegisterUser(Request $request, string $token)
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
    private function sendResetPasswordMail(PasswordReset $passwordReset)
    {
        Mail::to($passwordReset->email)
            // ->send(new ResetPasswordMail($passwordReset->token));
            ->queue(new ResetPasswordMail($passwordReset->token));
    }
}
