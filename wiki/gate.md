# ゲートとポリシー

[reffect.co.jp/laravel](https://reffect.co.jp/laravel/laravel-gate-policy-understand#Blade)  
[laravel.com](https://laravel.com/docs/8.x/authorization#creating-policies)  
[qiita.com 1](https://qiita.com/shunpeister/items/5ff1d71aedaf86712371)  
[qiita.com 2](https://qiita.com/nunulk/items/719e1d53c455946184ac)  

## ゲート

### ゲートを作成

`Gate::define` を使用して `isAdmin` というゲートを作成する  

 `app\Providers\AuthServiceProvider.php`

```php
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // isAdminゲートを作成
        Gate::define('isAdmin', function ($user) {
            return $user->role == 'admin';
        });
    }
}
```

### ゲートをコンロトーラで使用

 `app\Http\Controllers\CheckController.php`

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\Gate;

class CheckController extends Controller
{
    public function index(Request $request, Post $post)
    {
        /*
         * `Gate::authorize`を使用して`isAdmin`というゲートを使用する
         *
         * パスしなかった場合は`403 unauthorized`を返す
         */
        Gate::authorize('isAdmin');

        /*
         * `Gate::allows`を使用して`isAdmin`というゲートを使用する
         *
         * パスしなかった場合は`false`を返す
         */
        $flag = Gate::allows('isAdmin');

        /*
         * `Gate::denies`を使用して`isAdmin`というゲートを使用する
         *
         * allowsとは反対にパスしなかった場合は`true`を返す
         */
        $flag = Gate::denies('isAdmin'); 

        /*
         * `Gate::forUser`を使用して`isAdmin`というゲートをログインしているユーザ以外で使う場合
         *
         */
        // 特定のユーザを取得
        $other_user = User::find(1);
        $flag = Gate::forUser($other_user)->allows('isAdmin');
    }
}

```

### ゲートをルートで使用

`middleware` で `can` を使って `isAdmin` を指定する

 `routes\web.php`

```php
Route::group(['middleware' => 'can:isAdmin'], function () {
    Route::get('/check/{post?}', [CheckController::class, 'index'])->name('check');
});
```

## ポリシー

`Policies` を使用して `index` アクションに使用するゲートを `index` として作成  
Postを作成した本人以外削除できないいうゲートにする  

### ポリシー作成

`Post` モデル用に `PostPolicy` を作成する

```bash
php artisan make:policy PostPolicy
```

 `app\Policies\PostPolicy.php`

```php
namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function delete(User $user, Post $post)
    {
        return $user->id == $post->user_id;
    }
}
```

### ポリシーを登録

`App\Models\Post` : `App\Policies\PostPolicy` のように `Post` ぶぶんがいっちする場合は
自動的に登録されるみたい

 `app\Providers\AuthServiceProvider.php`

```php
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

// 追加 ↓
use App\Models\Post;
use App\Policies\PostPolicy;
// 追加 ↑

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 追加 ↓
        Post::class => PostPolicy::class,
        // 追加 ↑
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
```

### ポリシーをコンロトーラで使用

`authorize('delete', $post)` , `$user->can('view', $post)` , `$user->cannot('view',$post)` の第２引数は登録したModelインスタンスを渡す  

ここでは `Post` モデルのインスタンス `$post` を渡している  

 `app\Http\Controllers\CheckController.php`

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\Gate;

class CheckController extends Controller
{
    public function destroy(Request $request, Post $post)
    {
        /*
         * `authorize`を使用して`delete`というゲートを使用する
         * 第２引数はポリシーを登録したモデルインスタンス
         */

        // パスしなかった場合は`403 unauthorized`を返す
        $this->authorize('delete', $post);

        /*
         * `can`、`cannot`を使用して`delete`というゲートを使用する
         * 第２引数はポリシーを登録したモデルインスタンス
         * auth()->user()や$request->user()などでユーザインスタンスを取得する必要がある
         */

        // ユーザ取得
        $user = $request->user();

        // パスしなかった場合は`false`を返す
        $flag = $user->can('delete', $post);

        // `can`とは逆にパスしなかった場合は`true`を返す
        $flag = $user->cannot('delete', $post);
    }
}
```

### ポリシーをルートで使用

`middleware` で `can` を使って `delete` を指定する  
ポリシーを登録したモデルインスタンスを渡す必要があるので
`can:delete, post` のようにモデルバインディングでpostインスタンスも渡す  

 `routes\web.php`

```php
Route::group(['middleware' => 'can:delete,post'], function () {
    Route::get('/check/{post?}', [CheckController::class, 'index'])->name('check');
});
```

## Policyのメソッド

※ **この部分はわかりにくいので使いたくない**

```bash
php artisan make:policy PostPolicy –model
```

このように `–model` オプションをつけると `viewAny` , `view` , `create` , `update` , `delete` , `restore` , `forceDelete` のアクションが作成される

これらのメソッドは、コントローラーのメソッドと関連をもち以下のようになている

コントローラーメソッドは、対応するポリシーメソッドにマップされまる  
リクエストが特定のコントローラーメソッドにルーティングされると、コントローラーメソッドが実行される前に、対応するポリシーメソッドが自動的に呼び出される  

|  Controller Method  |  Policy Method  |
| ---                 | ---             |
|  index              |  viewAny        |
|  show               |  view           |
|  create             |  create         |
|  store              |  create         |
|  edit               |  update         |
|  update             |  update         |
|  destroy            |  delete         |

以下はfunctionが `destroy` なので `delete` を省略しても上記対応表にある通り `delete` が実行される  

```php
//...
    public function destroy(Request $request, Post $post)
    {
        // destroyアクションの中なのでdeleteを省略できる
        // $this->authorize('delete', $post);
        $this->authorize($post);
    }
//...
```

## まとめ

* モデルやリソースに対する認可はわかりにくいのでゲートのみ使うほうがわかりやすい
* ルーティングでのミドルウェアの設定がわかりにくくなる
* モデルバインディングが必要になる
* ポリシーになにが設定されているのかわかりにくい

上記理由から  
ゲートを使用して、クラスとして設定する方法が適している  

### 方法

モデル、リソースに基づく認可、 権限に基づく認可のどちらも書くことができるので柔軟に対応できる

#### テスト用のコントローラ

 `app\Http\Controllers\PostController.php`

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;

class PostController extends Controller
{
    // 一覧
    public function index(Request $request)
    {
        dump('index');
        dump(Post::all());
    }

    // 閲覧
    public function show(Request $request, Post $post)
    {
        dump('show');
        dump($post->toArray());
    }

    // 作成
    public function store(Request $request, Post $post)
    {
        dump('store');
        dump($post->toArray());
    }

    // 編集
    public function update(Request $request, Post $post)
    {
        dump('update');
        dump($post->toArray());
    }

    // 削除
    public function destroy(Request $request, Post $post)
    {
        dump('destroy');
        dump($post->toArray());
    }
}
```

#### ゲートクラスを作成

 `app\Gates\PostGate.php`

```php
namespace App\Gates;

use App\Models\User;
use App\Models\Post;

class PostGate
{
    // adminとeditorが作成と編集可能
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'editor'], true);
    }

    // adminと作成者が削除可能
    public function destroy(User $user, Post $post): bool
    {
        return $post->user_id === $user->id || $user->role === 'admin';
    }
}
```

#### ゲートを登録

`app\Providers\AuthServiceProvider.php`

```php
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

// 作成したクラスを読み込む
use App\Gates\PostGate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // super admin 権限
        Gate::define('superAdmin', function(User $user){
            return $user->role === 'super-admin';
        });

        // post gate
        // 作成（store）と 編集（update）
        Gate::define('postCreate', [PostGate::class, 'create']);
        // 削除（destroy）
        Gate::define('postDestroy', [PostGate::class, 'destroy']);
    }
}
```

#### ルートで使用

`routes\web.php`

```php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;

// super admin だけ見れる
Route::group(['middleware' => ['can:superAdmin']], function () {
    Route::get('/check', [PostController::class, 'index'])->name('post.index');
});

// 全部見れる
Route::get('/check/{post}', [PostController::class, 'show'])->name('post.show');

// 作成（store）と 編集（update）
Route::group(['middleware' => ['can:postCreate']], function () {
    Route::post('/post/{post}', [PostController::class, 'store'])->name('post.store');
    Route::put('/post/{post}', [PostController::class, 'update'])->name('post.update');
});

// 削除（destroy）
Route::group(['middleware' => ['can:postDestroy,post']], function () {
    Route::delete('/post/{post}', [PostController::class, 'destroy'])->name('post.destroy');
});
```
