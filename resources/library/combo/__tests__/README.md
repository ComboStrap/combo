# Testing


## Jest

We are using Jest as test runner

## JsDom Execution Environment

In the `jest` [package.json](../package.json) conf, all test are started

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

## Environment

The test environment is set with the [JestExtend.js](../jest/JestExtend.js).

## Test-library

We are using test library as specialized test framework above Jest.

They:
  * have special assertion matcher for the dom
  * and can simulate [click](https://testing-library.com/docs/ecosystem-user-event#clickelement-eventinit-options)
and more.

There is also a [playground](https://testing-playground.com/)
