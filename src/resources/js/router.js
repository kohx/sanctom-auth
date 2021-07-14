import Vue from 'vue'
import VueRouter from 'vue-router'

import Home from '@/pages/front/Home.vue'
import Test from '@/pages/front/Test.vue'
import Login from '@/pages/front/Login.vue'
import Register from '@/pages/front/Register.vue'

// VueRouterをVueで使う
Vue.use(VueRouter)

// パスとページの設定
const routes = [
    // Home
    {
        // ルートネーム
        name: 'home',
        // urlのパス
        path: '/',
        // インポートしたページ
        component: Home,
    },
    // Test
    {
        // ルートネーム
        name: 'test',
        // urlのパス
        path: '/test',
        // インポートしたページ
        component: Test,
    },
    // Login
    {
        // ルートネーム
        name: 'login',
        // urlのパス
        path: '/login',
        // インポートしたページ
        component: Login,
    },
    // Register
    {
        // ルートネーム
        name: 'register',
        // urlのパス
        path: '/register',
        // インポートしたページ
        component: Register,
    }
]

// VueRouterインスタンス
const router = new VueRouter({
    // いつもどうりのURLを使うために「history」モードにする
    mode: 'history',
    routes
})

// VueRouterインスタンスをエクスポート
export default router
