# Component build


## About

This package contains code used to create independent stand-alone files
that may be imported with a script tag by Combostrap.

We can use import, export and the bundler `rollup` will
create them via the `build` command.

## Dev

  * Add your component script (ie `component.ts` in [src](src))
  * Add your pages in [pages](pages) that imports your component script
  * Add your page in the [index](index.html)
  * Run the dev server

```bash
yarn dev
```

## Build: Independent stand-alone files

To build independent stand-alone files,
we use the array form of Rollup options supported by the CLI
to run Rollup for each component.

```bash
yarn build
```
See [2935](https://github.com/rollup/rollup/issues/2935)
