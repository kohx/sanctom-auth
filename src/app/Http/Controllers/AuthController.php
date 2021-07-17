<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
        $message = is_null($message) ? 'Already logged in.' : $message;

        // already logged in
        if (auth()->check()) {
            throw new HttpException(403, trans($message));
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
     * validateReset
     *
     * @param  Request $request
     * @return void
     */
    protected function validateReset(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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
        return response()->json(array_merge(['message' => trans($message)], $additions), 200);
    }

    /**
     * responseFailed
     * 失敗のレスポンス
     *
     * @param string $message
     * @param array $errors array in array
     * @return \Illuminate\Http\JsonResponse
     */
    protected function responseFailed(string $message, array $errors = [])
    {
        foreach ($errors as &$error) {
            foreach ($error as &$value) {
                $value = trans($value);
            }
        }

        return response()->json([
            'message' => trans($message),
            'errors' => $errors,
        ], 422);
    }
}
