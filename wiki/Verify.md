# Verify

## サンプルリポジトリ

[basic(Login)](https://github.com/kohx/sanctom-auth/releases/tag/v1.0.1)  

### サンプルから始める場合

```bash
composer install
npm i -g npm
npm i
php artisan key:generate
php artisan config:clear
php artisan config:cache
npm run watch
```

## コントローラ

```bash
php artisan make:controller Auth/VerifyController
php artisan make:controller AuthController
```

`routes\web.php`
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Auth\VerifyController; // 追加

// verify callback
Route::get('/verify', [VerifyController::class, 'verify'])->name('verify'); // 追加

// API以外はindexを返すようにして、VueRouterで制御
Route::get('/{any?}', function () {
    return view('index');
})->where('any', '.+');
```
