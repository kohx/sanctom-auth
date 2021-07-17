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
