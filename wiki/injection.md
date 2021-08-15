# インジェクション(DI)関係

## コンストラクタインジェクション

コントローラー内のすべてのメソッドからnewすることなくクラスのメソッドを呼び出すことができる

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

// DIするクラス
use App\Models\User;

class CheckController extends Controller
{
    // プロパティを宣言
    private $user;

    // DIするクラスを追加
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function index(Request $request)
    {
        // このように使用する
        $user = $this->user->find(1);
        dump($user);

        // このように使用する
        $user = $this->user;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();
        dump($user->id);
    }
}
```

## メソッドインジェクション

これはいつもRequestでやっているやつ

```php
namespace App\Http\Controllers;

// DIするクラス
use Illuminate\Http\Request;
use App\Models\User;

class CheckController extends Controller
{
    // DIするクラスを追加
    public function index(Request $request, User $user)
    {
        dump($request);
        $user = $user->find(1);
        dump($user->name);
    }
}

```

## モデルバインディング

これもメソッドインジェクションのひとつになるのか？

```php
// {user}はコントローラの変数と同じにする
Route::get('/user/{user}', [CheckController::class, 'show'])->name('user.show');
```

```php
use Illuminate\Http\Request;
use App\Models\User;

class CheckController extends Controller
{
    // $userはルート「'/user/{user}'」の「 {user} 」と揃える
    public function index(Request $request, User $user, $id)
    {

    public function index(Request $request, User $user)
    {
        // このようにしなくても
        // $user = $user->find($id);
        // $userにはインスタンスが返される
        dump($user->name);
    }
    }
}
```
