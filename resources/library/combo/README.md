# Library

  * Parcel to bundle
  * Babel-preset to support ES module
  * Jest for test

## Configuration

### Babel

`.babelrc` was written as per the [Jest documentation](https://jestjs.io/docs/getting-started#using-babel).

### Parcel

`.parcelrc` was written to avoid double babel transpilation as dictated in the [doc](https://parceljs.org/languages/javascript/#usage-with-other-tools)
It disable Babel transpilation in Parcel because Jest needs it also.
