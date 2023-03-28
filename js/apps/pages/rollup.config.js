import {resolve} from "path";

export default [
    {
        // https://rollupjs.org/guide/en/#big-list-of-options
        input: {
            main: resolve(__dirname, 'src/subscribe.js'),
        },
        output: {
            // name of the output, the name is json key, not the name of the file
            entryFileNames: "[name].js",
            format: 'iife',
            dir: "dist/"
        }
    },
    {
        // https://rollupjs.org/guide/en/#big-list-of-options
        input: {
            main: resolve(__dirname, 'src/subscribe.js'),
        },
        output: {
            // name of the output, the name is json key, not the name of the file
            entryFileNames: "[name]-2.js",
            format: 'iife',
            dir: "dist/"
        }
    }
];
