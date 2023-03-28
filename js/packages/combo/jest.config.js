/** @type {import('jest').Config} */
const config = {
    verbose: true,
    setupFilesAfterEnv: [
        "../../conf/JestExtend.js"
    ],
    testEnvironment: "jsdom",
    testEnvironmentOptions: {
        "userAgent": "Agent/007"
    },
    /**
     * To avoid
     * SyntaxError: Cannot use import statement outside a module:  node_modules\nanoid\index.prod.js:1, import { urlAlphabet } from './url-alphabet/index.js
     * We don't ignore a babel transform into es module for nanoi
     * via the transformIgnorePatterns
     */
    transformIgnorePatterns: ["/node_modules/(?!(nanoid)/)", "\\.pnp\\.[^\\\/]+$"],
    testMatch: [
        "**/__tests__/**/*.[jt]s?(x)",
        "**/_test/js/*.test.[jt]s?(x)"
    ]
};

module.exports = config;
