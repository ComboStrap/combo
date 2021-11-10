Library
=======

  * Parcel to bundle
  * Babel-preset to support ES module
  * Jest for test

Configuration
=============

### Babel

`.babelrc` was written as per the [Jest documentation](https://jestjs.io/docs/getting-started#using-babel).

### Parcel

#### Transpilation problem

To avoid this warning:

```txt
@parcel/transformer-babel: @babel/preset-env does not support Parcel's targets, which will likely result in unnecessary transpilation and larger bundle sizes.
```


`.parcelrc` was written to avoid double babel transpilation as dictated in the [doc](https://parceljs.org/languages/javascript/#usage-with-other-tools)
It disable Babel transpilation in Parcel because Jest needs it also.


#### Bootstrap / Jquery

  * They have been added as dev dependency
```bash
https://parceljs.org/features/dependency-resolution/#global-aliases
```
  * Then added as alias in `package.json` [https://parceljs.org/features/dependency-resolution/#global-aliases|global-aliases]


#### Build / UMD

We are not building a library (in parcel term, this is a node package to be used by other)


The [entry](https://parceljs.org/features/targets/#entries) is defined in the `source`
[package.json script](package.json)

[UMD is not supported on Parcel 2](
getting-started/migration/#--global), we then used `window``explicitly
to set the value.

  * Old: https://en.parceljs.org/cli.html#expose-modules-as-umd
  * MIgration:
    https://github.com/parcel-bundler/parcel/issues/766
    https://github.com/parcel-bundler/parcel/discussions/6437
    https://github.com/parcel-bundler/parcel/discussions/5583


## Jest

By default, all test are started in the `jsdom` [environment](https://jestjs.io/docs/configuration#testenvironment-string)
via the `jest` [package.json](package.json) conf.

You can change it with `jsdoc`
```javascript
/**
* @jest-environment jsdom
*/
```
