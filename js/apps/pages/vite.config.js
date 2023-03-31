import {resolve} from 'path'
import {defineConfig} from 'vite'
import fs from "fs";
import * as path from "path";


let pages = {
    main: resolve(__dirname, 'index.html')
};

// Add the pages in the pages subdirectory
let pagesFolder = resolve(__dirname, 'pages')
fs
    .readdirSync(pagesFolder)
    .map(file => {
        let fileWithoutExtension = path.basename(file, path.extname(file))
        pages[fileWithoutExtension] = resolve(pagesFolder, file)
    });

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
