import "./bootstrap"

// Vueインポート
import {
    createApp
} from 'vue'

// ルートコンポーネントをインポート
import App from "./App.vue"

// ルーターをインポート
import router from "./router"


const funcApp = async () => {

    return createApp(App)
        .use(router)
        .mount('#app')
}

const app = funcApp();
