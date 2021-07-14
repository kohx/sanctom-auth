# sanctum SPA認証(クッキー認証)

SPA認証用にトークンを使用しないでLaravel標準のクッキーベースのセッション認証サービをSanctumで作成する

## サンプルリポジトリ

[sanctom-auth](https://github.com/kohx/sanctom-auth)  

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

## 参考サイト

- [Laravel Sanctum でSPA(クッキー)認証する](https://qiita.com/ucan-lab/items/3e7045e49658763a9566)
- [Laravel 8.x Laravel Sanctum](https://readouble.com/laravel/8.x/ja/sanctum.html)
- [Laravel Sanctum](https://laravel.com/docs/8.x/sanctum)

- [API開発・テスト便利ツール Postmanの使い方メモ](https://qiita.com/zaburo/items/16ac4189d0d1c35e26d1)

## index

- [Login](/wiki/Login.md)
- [Register](/wiki/Register.md)
- [verify](/wiki/verify.md)
