import "./bootstrap"
import Vue from "vue"
// ルートコンポーネントをインポート
import App from "./App.vue"
// ルーターをインポート
import router from "./router"

const createApp = async () => {

    new Vue({
        el: "#app",
        router,
        components: {
            App
        },
        template: "<App />"
    })
}

createApp()
