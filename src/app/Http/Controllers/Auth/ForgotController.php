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
            // ->send(new PasswordResetMail($passwordReset->token));
            ->queue(new PasswordResetMail($passwordReset->token));
    }
}
