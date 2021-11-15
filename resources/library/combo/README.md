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

`.parcelrc` was written to avoid double babel transpilation as dictated in
the [doc](https://parceljs.org/languages/javascript/#usage-with-other-tools)
It disable Babel transpilation in Parcel because Jest needs it also.

#### Bootstrap

Bootstrap been added as in `package.json`:

* a [peer dependency](https://classic.yarnpkg.com/en/docs/dependency-types#toc-peerdependencies) (ie needed to run)
* and a [dev dependency](https://github.com/yannickcr/eslint-plugin-react/issues/2332) (needed for library dependencies
  resolution)

```bash
yarn add bootstrap --dev
yarn add bootstrap --peer
yarn add @popperjs/core --dev
yarn add @popperjs/core --peer
```

Then we set it as an [global alias](https://parceljs.org/features/dependency-resolution/#global-aliases) in `package.json`
to create a bootstrap global variable at build time
```json
{
    "alias": {
        "bootstrap": {
            "global": "bootstrap"
        }
    }
}
```
it means that all `from "bootstrap"` are replaced by the alias. ie

```javascript
import {Modal} from "bootstrap";

let bootStrapModal = new Modal(this.modalRoot, options);
```
is replaced when bundling by:
```javascript
let bootStrapModal = new bootstrap.Modal(this.modalRoot, options);
```

See also the [bootstrap doc](https://getbootstrap.com/docs/5.0/getting-started/parcel/)

#### Build / UMD

We are not building a library (in parcel term, this is a node package to be used by other)

The [entry](https://parceljs.org/features/targets/#entries) is defined in the `source`
[package.json script](package.json)

[UMD is not supported on Parcel 2](
getting-started/migration/#--global), we then used `window``explicitly to set the value.

* Old: https://en.parceljs.org/cli.html#expose-modules-as-umd
* MIgration:
  https://github.com/parcel-bundler/parcel/issues/766
  https://github.com/parcel-bundler/parcel/discussions/6437
  https://github.com/parcel-bundler/parcel/discussions/5583

## Jest JsDom Execution Environment

In the `jest` [package.json](package.json) conf, all test are started

* in the `jsdom` [environment](https://jestjs.io/docs/configuration#testenvironment-string)
* configured via the [test environment options](https://jestjs.io/docs/configuration#testenvironmentoptions-object) and
  the [possible configuration value of jsdom](https://github.com/jsdom/jsdom#customizing-jsdom)

```json
{
    "jest": {
        "testEnvironment": "jsdom",
        "testEnvironmentOptions": {
            "userAgent": "Agent/007"
        }
    }
}
```

You can change it by test with `jsdoc` annotation

```javascript
/**
 * @jest-environment jsdom
 */
```

The jsdom jest environment code can be
found [here](https://github.com/facebook/jest/blob/main/packages/jest-environment-jsdom/src/index.ts)
