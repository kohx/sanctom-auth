const mix = require('laravel-mix');
const path = require('path');

mix.webpackConfig({
        // @のパスを作成
        resolve: {
            alias: {
                '@': path.resolve(__dirname, 'resources/js/'),
            },
        }
    })
    .js("resources/js/app.js", "public/js")
    .vue();

mix.browserSync({
    // アプリの起動アドレスを「nginx」
    proxy: "nginx",
    // ブラウザを自動で開かないようにする
    open: false
})
