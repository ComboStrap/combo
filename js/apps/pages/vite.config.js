import {resolve} from 'path'
import {defineConfig} from 'vite'
import {chunkSplitPlugin} from 'vite-plugin-chunk-split';

export default defineConfig({
    build: {
        rollupOptions: [
            {
                // https://rollupjs.org/guide/en/#big-list-of-options
                input: {
                    main: resolve(__dirname, 'index.html'),
                },
                output: {
                    // name of the output, the name is json key, not the name of the file
                    entryFileNames: "[name].js",
                    format: 'iife',
                }
            },
            {
                // https://rollupjs.org/guide/en/#big-list-of-options
                input: {
                    pages: resolve(__dirname, 'pages/index.html'),
                },
                output: {
                    // name of the output, the name is json key, not the name of the file
                    entryFileNames: "[name].js",
                    format: 'iife',
                }
            }
        ],
    },
    plugins: [
        // chunkSplitPlugin({
        //     strategy: 'unbundle',
        // })
    ]
})
