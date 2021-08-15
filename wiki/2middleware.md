# middleware

## globalに設定する場合

Ajaxでリクエストしないと404を返すミドルウェアを作成して、Apiのグローバルに設定する

### Ajaxミドルウェアを作成

```bash
php artisan make:middleware Ajax
```

### Ajaxミドルウェアを編集

`app\Http\Middleware\Ajax.php`
``

```php
namespace App\Http\Middleware;

use Closure;
use App;

class Ajax
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 非同期通信でない場合
        if (!$request->ajax()) {
            abort(404);
        }

        return $next($request);
    }
}
```

### apiに設定

`app\Http\Kernel.php`

```php
//...
    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // ここに追加
            \App\Http\Middleware\Ajax::class,
        ],
    ];
//...
```

## routeに設定する場合

sessionに`lang`が設定されていればそれを設定、ない場合はデフォルトを設定

### Languageミドルウェアを作成

```bash
php artisan make:middleware Language
```

### Languageミドルウェアを編集

`app\Http\Middleware\Language.php`
``

```php
namespace App\Http\Middleware;

use Closure;
use App;

class Language
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // sessionのlang
        $session_lang = $request->session()->get('language');

        // sessionのlangがない場合はデフォルトをセット
        $lang = is_null($session_lang) ? config('app.fallback_locale') : $session_lang;


        // 言語が変わる場合
        if (App::getLocale() !== $lang) {
            // 言語をセット
            App::setLocale($lang);
        }

        return $next($request);
    }
}

```

### routeに設定

`app\Http\Kernel.php`

```php
//...
    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        // ここに追加
        'language' => \App\Http\Middleware\Language::class,
    ];
//...
```

### routeで使用する

`routes\api.php`

```php
//...
Route::middleware(['language'])->group(function () {

    Route::post('/login', [LoginController::class, 'login']);
});
//...
```
