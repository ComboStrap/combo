import {resolve} from 'path'
import {defineConfig} from 'vite'

let pages = {
    main: resolve(__dirname, 'index.html'),
    subscribe: resolve(__dirname, 'pages/subscribe.html'),
};

export default defineConfig({
    build: {
        rollupOptions:
            {
                // https://rollupjs.org/guide/en/#big-list-of-options
                input: pages,
                // make sure to externalize deps that shouldn't be bundled
                // into your library
                external: ['bootstrap'],
                output: {
                    // Provide global variables to use in the UMD build
                    // for externalized deps
                    globals: {
                        bootstrap: 'bootstrap',
                    },
                }
            }
        ,
    }
})
