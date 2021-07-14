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
     *
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
