# 文字列関係で使いたいもの

ほとんどがphpだけでできるものだけど、書き方の統一、マルチバイト文字を気にせずかけるので
つかたほうが良いと思う

## 置換

```php
// 配列で文字列置換
$value = Str::replaceArray('?', ['no1', 'no2'], 'text1 ? text2 ? text.');
dump($value);

// 最初に登場する文字列を置換
$value = Str::replaceFirst('xxx', 'first', 'xxx xxx xxx xxx xxx');
dump($value);

// 最後に登場する文字列を置換
$value = Str::replaceLast('xxx', 'last', 'xxx xxx xxx xxx xxx');
dump($value);

// 正規表現で検索して配列で置換
// preg_replaceの拡張なので独自ヘルパ
$value = preg_replace_array('/:[a-z_]+/', ['no1', 'no2'], 'text1 :aaa text2 :bbb text.');
dump($value);
```

## 取得

```php
// 指定した文字より前の文字列を取得
$value = Str::before('abcdefghi', 'd');
dump($value);

// 指定した文字より後の文字列を取得
$value = Str::after('abcdefghi', 'f');
dump($value);

// ２つの文字列の間にある部分を取得する
$value = Str::between('abcdefghi', 'd', 'h');
dump($value);

// 指定した文字で始まる文字列を取得

// スラッシュなし
$value = Str::start('public/images', '/');
dump($value);
// スラッシュあり
$value = Str::start('/public/images', '/');
dump($value);

// 指定した文字で終わる文字列を取得

// スラッシュなし
$value = Str::finish('public/images', '/');
dump($value);
// スラッシュあり
$value = Str::finish('public/images/', '/');
dump($value);
```

## 文字数

```php
// 文字数を取得
$value = Str::length('1234567');
dump($value);
$value = Str::length('一二三四五六七');
dump($value);
```

## 単数形、複数形

```php
// 複数形の取得
$value = Str::plural('item');
dump($value);
$value = Str::plural('woman');
dump($value);

// 数によって単数形、複数形の取得
$value = Str::plural('item', 1);
dump($value);
$value = Str::plural('woman', 2);
dump($value);

// 単数形の取得
$value = Str::singular('items');
dump($value);
$value = Str::singular('women');
dump($value);
```

## 文字列の操作

```php
// 3番目から後ろ2文字切り出し
$value = Str::substr('一二三四五六七', 3, 2);
dump($value);
```

## 文字の変換

```php
// 小文字に変換
$value = Str::lower('ONE TWO THREE');
dump($value);

// 大文字に変換
$value = Str::upper('one two three');
dump($value);

// キャメルケースに変換
$value = Str::camel('one_two_three');
dump($value);
$value = Str::camel('one two three');
dump($value);

// ケバブケースに変換
$value = Str::kebab('oneTwoThree');
dump($value);
$value = Str::kebab('one two three');
dump($value);

// スネークケースに変換
$value = Str::snake('oneTwoThree');
dump($value);
$value = Str::snake('one two three');
dump($value);

// スタディ(アッパーキャメル)ケースに変換
$value = Str::studly('oneTwoThree');
dump($value);
$value = Str::studly('one two three');
dump($value);
```

## 表示用

```php
// 最初の文字を大文字
$value = Str::ucfirst('one two three.');
dump($value);

// タイトルようにすべて大文字
$value = Str::title('one two three.');
dump($value);
```

## 文字列の作成

```php
// 値を指定してスラッグケースを作成
$value = Str::slug('Hop Step Jump', '*');
dump($value);

// ランダムな文字列を取得する
echo Str::random(25);

// uuidを作成
$value = Str::uuid();
dump($value);

// ソートできるuuidを作成
$value = Str::orderedUuid();
dump($value);
```

## チェック

```php
// uuidかチェック
$flag = Str::isUuid('cc194f9b-4650-4838-bafa-b166c973a3a7');
dump($flag);

// 指定する文字で始まるかチェック
$flag = Str::startsWith('abcdefghi', 'abc');
dump($flag);

// 指定する文字で終わるかチェック
$flag = Str::endsWith('abcdefghi', 'ghi');
dump($flag);

// 指定する文字を含むかチェック
$flag = Str::contains('abcdefghi', 'def');
dump($flag);
$flag = Str::contains('abcdefghi', ['bc', 'mn']);
dump($flag);

// 指定する文字をすべて含むかチェック
$flag = Str::containsAll('abcdefghi', ['bcd', 'fgh']);
dump($flag);

// ワイルドカードを使ってマッチするかチェック
$flag = Str::is('ab*hi', 'abcdefghi');
dump($flag);
```

## 空かどうかをチェック

```php
// true
$flag = blank(' ');
dump($flag);
$flag = blank(null);
dump($flag);
$flag = blank(collect());
dump($flag);

// false
$flag = blank(0);
dump($flag);
$flag = blank(true);
dump($flag);
$flag = blank(false);
dump($flag);
$flag = blank(-1);
dd($flag);
```
