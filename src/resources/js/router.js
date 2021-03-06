import {
    createRouter,
    createWebHistory
} from 'vue-router'

import Home from '@/pages/front/Home.vue'
import Test from '@/pages/front/Test.vue'
import Login from '@/pages/auth/Login.vue'
import Register from '@/pages/auth/Register.vue'
import Verify from '@/pages/auth/Verify.vue'
import Forgot from '@/pages/auth/Forgot.vue'
import Reset from '@/pages/auth/Reset.vue'
import NotFound from '@/pages/errors/NotFound.vue'

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
    },
    // Verify
    {
        // ルートネーム
        name: 'verify',
        // urlのパス
        path: '/verify/:token',
        // インポートしたページ
        component: Verify,
        props: true,
    },
    // Forgot
    {
        // Forgot
        name: 'forgot',
        // urlのパス
        path: '/forgot',
        // インポートしたページ
        component: Forgot,
    },
    // Reset
    {
        // Reset
        name: 'reset',
        // urlのパス
        path: '/reset/:token',
        // インポートしたページ
        component: Reset,
        props: true,
    },
    // not found
    {
        // 定義されたルート以外のパスでのアクセスは <NotFound> が表示
        path: '/:pathMatch(.*)*',
        // ルートネーム
        name: 'not-found',
        component: NotFound
    }
]

// VueRouterインスタンス
const router = createRouter({
    // いつもどうりのURLを使うために「history」モードにする
    history: createWebHistory(),
    routes: routes,
})

// VueRouterインスタンスをエクスポート
export default router
