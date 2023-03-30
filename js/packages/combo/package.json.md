# Package.json



We follow the [recommandations](https://vitejs.dev/guide/build.html#library-mode)
```javascript
let package = {
    name: "my-lib",
    type: "module",
    // path to the main types
    types: "...",
    files: ["dist"], 
    // Entry-point for `require("my-package") in CJS
    main: "./dist/my-lib.umd.cjs",
    // Entry-point for `import "my-package"` in ESM
    module: "./dist/my-lib.js",
    // node ew way to define exports
    exports: {
        ".": {
          // Entry-point for `import "my-package"` in ESM
          "import": "./dist/my-lib.js",
          // Entry-point for `require("my-package") in CJS
          "require": "./dist/my-lib.umd.cjs"
        }
  }
}
```
