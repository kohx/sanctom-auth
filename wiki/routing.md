# ルーティングの作成とURL取得

## ルーティング

* `name`をつける
* `name`は`[controller].[method]`にする

```php
use App\Http\Controllers\UserProfileController;

Route::get('/user/{id}/profile', [UserProfileController::class, 'show'])
    ->name('user_profile.show');

Route::post('/user/{id}/profile', [UserProfileController::class, 'store'])
    ->name('user_profile.store');

Route::put('/user/{id}/profile', [UserProfileController::class, 'update'])
    ->name('user_profile.update');
    
Route::delete('/user/{id}/profile', [UserProfileController::class, 'destroy'])
    ->name('user_profile.destroy');
```

### ルート名でurl取得

`name` で取得できるとベスト

```php
$url = route('user_profile.show', ['id' => 1, 'photos' => 'yes']);
dump($url); // /user/1/profile?photos=yes
```

### controller内でのパラメータ

```php
    class UserProfileController extends Controller {

        public function show(Request $request, $id)
        {
            dump($id);
        }
    }
```

## 署名付きURL

passwordのリセットなどで便利

### 署名付きURLの取得

```php
// 署名
$url = URL::signedRoute('user_profile.show', ['user_id' => 1]);
dump($url);
// /user/1/profile?signature=185eef1fe7ba8fb1eb31b1f3d96d06c896cbac3c9cfdba2f0c34c10514eabc26
```

### 時間制限付きURLの取得

```php
// 30分間有効
$expire = now()->addMinutes(30);

$url = URL::temporarySignedRoute('check', $expire, ['user_id' => 1]);
dump($url);
// /user/1/profile?expires=1628659251&user_id=1&signature=ae3682d863e83d2db0583b52dc6fed76c11658ffbd4ba562eddd4d4d1e8872d7"
```

### 署名付きURLのチェック

#### コントローラでチェック

```php
use Illuminate\Support\Facades\URL;

class UserProfileController extends Controller {

    public function show(Request $request, $id)
    {
        $flag = $request->hasValidSignature();
        // abort_unlessヘルパで返すなど
        abort_if($flag, 403, trans(''));
    }
}
```

#### ミドルウェアでチェック

403コードが返される

`app\Http\Kernel.php`  

```php
protected $routeMiddleware = [
    //...
    'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
];
```

`routes\web.php`  

```php
use App\Http\Controllers\UserProfileController;

Route::group(['middleware' => ['signed']], function () {

    Route::get('/user/{id}/profile', [UserProfileController::class, 'show'])
        ->name('user_profile.show');
});

// または
Route::get('/user/{id}/profile', [UserProfileController::class, 'show'])
        ->name('user_profile.show')
        ->middleware('signed');

```

## 現在のルートチェック

```php
$flag = $request->route()->named('profile');
dump($flag); // true false
```

## protocolを取得

```php
$protocol = $request->secure() ? 'https' : 'http';
$protocol = request()->secure() ? 'https' : 'http';
dump($protocol);
```

## urlの取得

### urlを取得

```php
$url = $request->url();
$url = request()->url();
$url = url()->current();
dump($url);
```

### クエリストリングも含めて取得

```php

$url = $request->fullUrl();
$url = request()->fullUrl();
$url = url()->full();
dump($url);
```

### ベースurl取得

```php
$url = url('');
$url = url('/');
dump($url);
```

### 直前にリクエストされたURLを取得

```php

$url = url()->previous();
dump($url);
```

## url指定

```php
$url = ('aaa/bbb/ccc');
dump($url);

$url = url('/', ['aaa', 'bbb', 'ccc']);
dump($url);

$url = url('xxx?key=value');
dump($url);
```
