import {resolve} from 'path'
import {defineConfig} from 'vite'

export default defineConfig({
    build: {
        lib: {
            // Could also be a dictionary or array of multiple entry points
            entry: resolve(__dirname, 'src/combo.ts'),
            // global name in window
            name: 'combo',
            formats: ['umd', 'es'],
            // the proper extensions are added
            fileName: 'combo',
        },
        // https://rollupjs.org/guide/en/#big-list-of-options
        rollupOptions:
            {
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
    },
    test: {
        environment: "jsdom"
    }
})
