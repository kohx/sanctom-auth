import "./bootstrap"

// Vueインポート
import {
    createApp
} from 'vue'

// ルートコンポーネントをインポート
import App from "./App.vue"

// ルーターをインポート
import router from "./router"

const app = createApp(App)
    .use(router)
    .mount('#app')
