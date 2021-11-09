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
