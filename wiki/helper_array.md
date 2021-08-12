# 配列関係で使いたいもの

## 配列の先頭や最後がほしいとき

配列の先頭や最後がほしいとき `end($array)` , `last($array)` を使用するがこれをわかりやすくする。  
内部的にはreset($array)とend($array)を返しているだけ。  

* head()
* last()

```php
$array = [111, 222, 333];
head($array); // 111
last($array); // 333
```

## 配列の中身を取得

```php
$array = [
    'aaa' => [,
        'xxx' => '11',
        'yyy' => '12',
        'zzz' => '13',
    ],
];

// これをもっとシンプルにわかりやすくする
$value = $array['aaa']['xxx'] ?? false;
```

* Arr::get()
* data_get()

```php
// キーがない場合はエラーにならずnullが返る
$value = Arr::get($array, 'ccc'); // null
// デフォルトが指定できる
$value = Arr::get($array, 'ccc', false); // false
// dot記法が使える
$value = Arr::get($array, 'aaa.xxx', false); // 11

// シュガーシンタックスでもっとシンプル
$value = data_get($array, 'aaa.yyy', false); // 12
```

## 配列のに値をセット

```php
$array = [
    'aaa' => [
        'xxx' => '11',
        'yyy' => '12',
        'zzz' => '13',
    ],
];

// これをもっとシンプルにわかりやすくする
$array['aaa']['xxx'] = '1-1';
```

* Arr::set()
* data_set()

```php
// dot記法が使える
Arr::set($array, 'aaa.xxx', '1-1');

// シュガーシンタックスでもっとシンプル
$value = data_set($array, 'aaa.yyy', '1-2');
```

## 配列の削除

`unset` 、 `array_splice` 、 `array_shift` 、 `array_pop` などを使うので書き方がばらける  

```php
$array = [
    'aaa' => [
        'xxx' => '11',
        'yyy' => '12',
        'zzz' => '13',
    ],
    'bbb' => [
        'xxx' => '21',
        'yyy' => '22',
        'zzz' => '23',
    ],
];

// これをもっとシンプルにわかりやすくする
array_splice($array, 1);
unset($array['aaa']['zzz']);
```

* Arr::forget()

```php
// キーで指定できる
Arr::forget($array, 'bbb');
// dot記法が使える
Arr::forget($array, 'aaa.zzz');
```

## キーとバリューの配列にする

```php

$array = [
    [
        'id' => 1,
        'developer' => [
            'code' => '1a',
            'name' => 'Taylor',
            'Taylor' => ['age' => 21],
        ]
    ],
    [
        'id' => 2,
        'developer' => [
            'code' => '2a',
            'name' => 'Abigail',
            'Abigail' => ['age' => 24],
        ]
    ],
];

// これだと何してるかわかりにくい
$users = array_column($array, 'developer', 'id');
dump($users);
```

* Arr::pluck()

```php
// プルックしてるのがわかる
$users = Arr::pluck($array, 'developer', 'id');
dump($users);
// dot記法が使える
$users = Arr::pluck($array, 'developer.name', 'developer.code');
dump($users);
// nullで全部取れる
$users = Arr::pluck($array, null, 'developer.code');
dump($users);
```

## 先頭、末尾に値を追加

`array_unshift` 、 `array_merge` 、 `+` なんかでばらける

```php
$array = [
    'aaa' => [
        'xxx' => '11',
        'yyy' => '12',
        'zzz' => '13',
    ],
    'bbb' => [
        'xxx' => '21',
        'yyy' => '22',
        'zzz' => '23',
    ],
];

// キーがつかない
array_unshift($array, [
        'xxx' => '21',
        'yyy' => '22',
        'zzz' => '23',
    ]);
// わかりにくい
$array = array_merge(['ccc' => null], $array);
```

* Arr::prepend()
* Arr::add()

```php
// 先頭に追加
// キーが使える
$array = Arr::prepend($array, null, 'top');
dump($array);

// 末尾に追加
// キーとバリューが反対なので注意!
$array = Arr::add($array, 'bottom', null);
dump($array);
```

## ランダムに取得

```php
$array = [
        'user' => 'Desk',
        'user' => 'Table',
        'user' => 'Chair',
    ];

// キーが有るとできない
dump(array_rand($array, 1));
```

* Arr::random()

```php
// キーが有っても取得できる
$rand = Arr::random($array, 1);
// キーを残して取得
$rand = Arr::random($array, 1, true);
```

## 並び替え

```php
$array = [
    [
        'id'        => 3,
        'kana' => 'かきくけこ',
        'str'    => ''
    ],
    [
        'id'        => 2,
        'kana' => 'さしすせそ',
        'str'     => 1,
    ],
    [
        'id'        => 1,
        'kana' => 'あいうえお',
        'str'    => '1'
    ],
];

// わかりにくい
array_multisort(array_column($array, 'kana'), SORT_ASC, $array);
```

* Arr::sort()
* collect()

```php
// シンプルに書ける
// DESCができない！
$array = Arr::sort($array, 'kana');
dump($array);

// ヘルパーからははずれるが汎用性があるので紹介
// Arr::sortは内部的には以下となっている
$array = collect($array)->sortBy('kana')->all();
dump($array);
// Descはこうかける
$array = collect($array)->sortByDesc('kana')->all();
dump($array);
```