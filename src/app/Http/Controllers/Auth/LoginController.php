<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\ThrottlesLogins;

use Illuminate\Validation\ValidationException;

final class LoginController extends Controller
{
    use ThrottlesLogins;

    // ログイン試行回数（回）
    protected $maxAttempts = 3;

    // ログインロックタイム（分）
    protected $decayMinutes = 1;

    /**
     * @param Request $request
     * @return Json
     * @throws Exception
     */
    public function login(Request $request)
    {
        // validate
        $this->validateLogin($request);

        // too many login
        if (method_exists($this, 'hasTooManyLoginAttempts') && $this->hasTooManyLoginAttempts($request)) {

            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        // check login
        if ($this->attemptLogin($request)) {

            // success login response
            return $this->sendSuccessLoginResponse($request);
        }

        // failed login response
        $this->incrementLoginAttempts($request);
        return $this->sendFailedLoginResponse($request);
    }

    /**
     * @param Request $request
     * @return Json
     */
    public function logout(Request $request)
    {
        $this->getGuard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out'], 200);
    }

    /**
     * get guard
     *
     * @return Guard
     */
    private function getGuard()
    {
        return Auth::guard(config('auth.defaults.guard'));
    }

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
     * Validate the user login request.
     * usernameとpasswordのバリデーション
     *
     * @param  Request $request
     * @return Void
     *
     * @throws ValidationException
     */
    private function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
            'remember' => 'boolean',
        ]);
    }

    /**
     * Get the needed authorization credentials from the request.
     * 認証に使うパラメータを取得
     *
     * @param  Request $request
     * @return Array
     */
    private function credentials(Request $request)
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
    private function attemptLogin(Request $request)
    {
        return $this->getGuard()->attempt(
            $this->credentials($request),
            $request->filled('remember')
        );
    }

    /**
     * Send the response after the user was authenticated.
     * ログイン成功のレスポンス
     *
     * @param  Request $request
     * @return Json
     */
    private function sendSuccessLoginResponse(Request $request)
    {
        $request->session()->regenerate();
        $this->clearLoginAttempts($request);

        return response()->json([
            'message' => 'Logged in',
            'user' => $request->user(),
        ], 200);
    }

    /**
     * Get the failed login response instance.
     * ログイン失敗のレスポンス
     *
     * @throws ValidationException
     */
    private function sendFailedLoginResponse()
    {
        // throw new Exception('ログインに失敗しました。再度お試しください');

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }
}
