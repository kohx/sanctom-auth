<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\ThrottlesLogins;

final class LoginController extends AuthController
{
    use ThrottlesLogins;

    // ログイン試行回数（回）
    protected $maxAttempts = 3;

    // ログインロックタイム（分）
    protected $decayMinutes = 1;

    /**
     * login
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws HttpException
     */
    public function login(Request $request)
    {
        // already logged in
        $this->alreadyLogin($request);

        // validate
        $this->validateLogin($request);

        // too many login
        if (method_exists($this, 'hasTooManyLoginAttempts') && $this->hasTooManyLoginAttempts($request)) {

            // event
            $this->fireLockoutEvent($request);

            // Lockout response
            return $this->sendLockoutResponse($request);
        }

        // check login
        if ($this->attemptLogin($request)) {

            // regenerate token
            $request->session()->regenerate();

            // ログイン失敗をリセット
            $this->clearLoginAttempts($request);

            // success login response
            return $this->responseSuccess('Logged in.', [
                'user' => $request->user()
            ]);
        }

        // ログイン試行をカウントアップ
        $this->incrementLoginAttempts($request);

        // fail login response
        return $this->responseInvalid('invalid data.', [
            $this->username() => ['auth.failed']
        ]);
    }

    /**
     * logout
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // logout
        $this->getGuard()->logout();

        // session refresh
        $request->session()->invalidate();

        // regenerate token
        $request->session()->regenerateToken();

        // success login response
        return $this->responseSuccess('Logged out.');
    }
}
