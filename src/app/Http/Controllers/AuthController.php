<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\Password;

abstract class AuthController extends Controller
{
    /**
     * Get the login username to be used by the controller.
     * ユーザネームをemailにするかnameにするか
     *
     * @return string
     */
    protected function username()
    {
        return 'email';
    }

    /**
     * get guard
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    protected function getGuard()
    {
        return Auth::guard(config('auth.defaults.guard'));
    }

    /**
     * Get the needed authorization credentials from the request.
     * 認証に使うパラメータを取得
     *
     * @param  Request $request
     * @return Array
     */
    protected function credentials(Request $request)
    {
        return $request->only($this->username(), 'password');
    }

    /**
     * Attempt to log the user into the application.
     * ログインさせる
     *
     * @param  Request $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        return $this->getGuard()->attempt(
            $this->credentials($request),
            $request->filled('remember')
        );
    }

    /**
     * password hash
     * パスワードのhash
     *
     * @param string $password
     * @return string
     */
    protected function passwordHash($password)
    {
        return Hash::make($password);
    }

    /**
     * create activation token
     * トークンを作成する
     * @return string
     */
    protected function createToken()
    {
        return hash_hmac('sha256', Str::random(40), config('app.key'));
    }

    /**
     * Determine if the token has expired.
     *
     * @param string $createdAt
     * @return bool
     */
    protected function tokenExpired($expires, $createdAt)
    {
        return Carbon::parse($createdAt)
            ->addSeconds($expires)
            ->isPast();
    }

    /**
     * alreadyLogin
     *
     * @param  Request $request
     * @param string|null $message
     * @return void
     *
     * @throws HttpException
     */
    protected function alreadyLogin(Request $request, string $message = null)
    {
        // set message
        $message = is_null($message) ? trans('Already logged in.') : $message;

        // already logged in
        if (auth()->check()) {
            throw new HttpException(403, $message);
        }
    }

    /**
     * validateLogin
     *
     * @param  Request $request
     * @return void
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
            'remember' => 'boolean',
        ]);
    }

    /**
     * Validate the user register request.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validateRegister(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);
    }

    /**
     * Validate the forgot request.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validateForgot(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'exists:users,email'],
        ]);
    }

    /**
     * validateReset
     *
     * @param  Request $request
     * @return void
     */
    protected function validateReset(Request $request)
    {
        $request->validate([
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);
    }

    /**
     * responseSuccess
     * 成功のレスポンス
     *
     * @param string $message
     * @param array $additions
     * @return \Illuminate\Http\JsonResponse
     */
    protected function responseSuccess(string $message, array $additions = [])
    {
        return response()->json(array_merge(['message' => $message], $additions), 200);
    }

    /**
     * responseFailed
     * 失敗のレスポンス
     *
     * @param string $message
     * @param array $additions
     * @return \Illuminate\Http\JsonResponse
     */
    protected function responseFailed(string $message)
    {
        return response()->json(['message' => $message], 403);
    }

    /**
     * responseInvalid
     * インヴァリッドのレスポンス
     *
     * @param string $message
     * @param array $errors array in array
     * @return \Illuminate\Http\JsonResponse
     */
    protected function responseInvalid(string $message, array $errors = [])
    {
        return response()->json([
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }
}
