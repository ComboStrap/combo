import {resolve} from "path";
import * as fs from "fs";
import typescript from "@rollup/plugin-typescript";
import path from "path";
import terser from "@rollup/plugin-terser";

let srcFolder = resolve(__dirname, 'src')

let rollupExecutionConfigurations = [];
fs
    .readdirSync(srcFolder)
    .map(file => {
        let fileWithoutExtension = path.basename(file, path.extname(file))
        rollupExecutionConfigurations.push({
            // https://rollupjs.org/guide/en/#big-list-of-options
            input: {
                main: resolve(srcFolder, file),
            },
            // make sure to externalize deps that shouldn't be bundled
            // into your library
            external: ['bootstrap', 'combo'],
            output: [
                {
                    // name of the output, the name is the json key, not the name of the file
                    entryFileNames: `${fileWithoutExtension}.min.js`,
                    format: 'iife',
                    dir: "dist/",
                    // Provide global variables to use in the UMD build
                    // for externalized deps
                    globals: {
                        bootstrap: 'bootstrap',
                        combo: 'combo',
                    },
                    plugins: [terser()]
                },
                {
                    // name of the output, the name is the json key, not the name of the file
                    entryFileNames: `${fileWithoutExtension}.js`,
                    format: 'iife',
                    dir: "dist/",
                    // Provide global variables to use in the UMD build
                    // for externalized deps
                    globals: {
                        bootstrap: 'bootstrap',
                        combo: 'combo',
                    }
                }

            ],
            plugins: [typescript()]
        });
    });

export default rollupExecutionConfigurations;
