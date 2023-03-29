import {resolve} from 'path'
import {defineConfig} from 'vite'

// build should copy
// "../../../resources/library/combo/",
export default defineConfig({
    build: {
        lib: {
            // Could also be a dictionary or array of multiple entry points
            entry: resolve(__dirname, 'src/combo.ts'),
            name: 'combo',
            // the proper extensions will be added
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
    test:{
        environment: "jsdom"
    }
})
