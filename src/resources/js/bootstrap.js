window._ = require('lodash');

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// ベースURLの設定
const baseUrl = process.env.MIX_URL;

// ベースURLに api を追加
window.axios.defaults.baseURL = `${baseUrl}/api/`;

// 自動的にクッキーをクライアントサイドに送信
window.axios.defaults.withCredentials = true;

// requestの設定
window.axios.interceptors.request.use(config => {

    return config;
});

// responseの設定
// API通信の成功、失敗でresponseの形が変わるので、どちらとも response にレスポンスオブジェクトを代入
window.axios.interceptors.response.use(
    // 成功時の処理
    response => {
        // ローディングストアのステータスをFALSE
        return response;
    },
    // 失敗時の処理
    error => {
        return error.response || error;
    }
);

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// window.Pusher = require('pusher-js');

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: process.env.MIX_PUSHER_APP_KEY,
//     cluster: process.env.MIX_PUSHER_APP_CLUSTER,
//     forceTLS: true
// });
