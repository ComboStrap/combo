import {resolve} from 'path'
import {defineConfig} from 'vite'

export default defineConfig({
    build: {
        lib: {
            // Could also be a dictionary or array of multiple entry points
            entry: resolve(__dirname, 'src/combo.ts'),
            // global name in the browser window object
            name: 'combo',
            // umd for the browser and node,
            // es does not work in browser due to bootstrap (2023-03-31)
            formats: ['umd', 'es'],
            // the proper extensions are added
            fileName: (formatName, entryName) => {
                if (formatName === "umd") {
                    // umd is the node format (ie cjs)
                    // We name it js to conform to the name in the resolution of types
                    // https://www.typescriptlang.org/docs/handbook/declaration-files/dts-from-js.html
                    return `${entryName}.js`;
                }
                return `${entryName}.${formatName}.js`;
            },
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
                    exports: 'named'
                }
            }
        ,
    },
    test: {
        environment: "jsdom"
    }
})
