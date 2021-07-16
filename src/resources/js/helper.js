export default class Helper {

    static capitalizeFirstLetter(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    /**
     * get language
     * ブラウザから言語を取得
     *
     */
    static getLanguage() {
        const language = (window.navigator.languages && window.navigator.languages[0]) ||
            window.navigator.language ||
            window.navigator.userLanguage ||
            window.navigator.browserLanguage;
        return language.slice(0, 2);
    }

    /**
     * create default password
     */
    static createDefaultPassword() {
        const S = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        let N = 8;
        const password = Array.from(Array(N))
            .map(() => S[Math.floor(Math.random() * S.length)])
            .join("");
        return password;
    }

    /**
     * check height
     * @returns
     */
    static checkScroll() {
        const body = document.body;
        const html = document.documentElement;
        const scrollHeight = Math.max(
            body.scrollHeight,
            body.offsetHeight,
            html.clientHeight,
            html.scrollHeight,
            html.offsetHeight
        );

        var scrollPosition = window.innerHeight + window.scrollY

        return scrollHeight - scrollPosition
    }

    /**
     * first load
     * 使用しないで一度のロード数を増やした
     *
     * @param {Function} callback
     */
    static async firstLoad(callback) {
        let i = 0;
        do {
            i++;
            await callback();
        }
        while (this.checkScroll() <= 0 && i < 3);
    }

    /**
     * get bottom
     *
     * @param {Function} callback
     */
    static async getBottom(callback) {
        if (this.checkScroll() <= 0) {
            await callback();
            // 少し戻す
            window.scrollTo(0, window.pageYOffset - 10)
        }
    }

    /**
     * to top
     * @param {Number} top
     */
    static toTop(top = 0) {
        window.scrollTo({
            top: top,
            behavior: "smooth",
        });
    }

    /**
     * check image size from file
     *
     * Helper.getFileImageSize(file, (image) => {
     *   console.log(image)
     *   console.log(image.naturalWidth)
     *   console.log(image.naturalHeight)
     * });
     */
    static async getFileImageSize(url, callback) {

        // const url = URL.createObjectURL(file);

        const image = new Image();
        image.addEventListener('load', () => {
            callback(image);
        });

        image.src = url;
    }

    /**
     * メッセージの文字数を計算
     */
    static calcMessageLength(message, lineMax) {

        if (message.length === 0) {
            return 0;
        }

        lineMax = Number(lineMax);

        // divide by line
        const lines = message.split(/\n/g);
        // console.log(lines)

        // line maxで分割
        let dividedLines = [];
        for (const line of lines) {
            if (!line) {
                dividedLines.push("");
                continue;
            }

            for (let i = 0; i < line.length / lineMax; i++) {
                const fragment = line.substr(i * lineMax, lineMax);
                dividedLines.push(fragment);
            }
        }
        // console.log(dividedLines);

        // total length
        const result = dividedLines.reduce((total, value, index, arr) => {
            if (index === arr.length - 1) {
                // 最後の行が０のときは20
                total = total + (value.length === 0 ? lineMax : value.length);
            } else {
                total = total + (value.length <= lineMax ? lineMax : value.length);
            }
            return total;
        }, 0);

        return result;
    }
}
