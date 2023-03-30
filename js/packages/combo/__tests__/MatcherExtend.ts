/**
 *
 * A custom extension
 *
 *
 *
 * As explained [here](https://vitest.dev/guide/extending-matchers.html)
 *
 *
 * Intellij parse it and helps the diff
 *
 *
 */
import {diff} from 'jest-diff';
import Xml from "../src/Xml";

interface CustomMatchers<R = unknown> {
    toEqualHtmlString(expected): R
}

declare global {
    namespace Vi {
        interface Assertion extends CustomMatchers {}
        interface AsymmetricMatchersContaining extends CustomMatchers {}
    }
}

export default {

    /**
     * Check that a HTML string is an HTML string
     * It will normalize the string before making a string equality
     *

     * Matchers should return an object (or a Promise of an object) with two keys.
     *   * pass indicates whether there was a match or not,
     *   * message provides a function with no arguments that returns an error message in case of failure.
     * Then:
     * When pass is false, message should return the error message for when expect(x).yourMatcher() fails.
     * When pass is true, message should return the error message for when expect(x).not.yourMatcher() fails.
     *
     * @param actual
     * @param expected
     * @return {{actual: string, pass: *, expected, message: {(): string, (): string}}}
     *
     * Note that there is already
     * https://github.com/testing-library/jest-dom/blob/main/src/to-contain-html.js
     * but it does not format by trimming whitespace from the start and end of text,
     * and collapsing multiple adjacent whitespace characters into a single space.
     * https://testing-library.com/docs/queries/about#normalization
     *
     */
    toEqualHtmlString(actual, expected) {
        actual = Xml.createFromHtmlString(actual).normalize();
        expected = expected.trim();
        let pass = this.equals(actual, expected);

        const options = {
            comment: 'Html String equality',
            isNot: this.isNot,
            promise: this.promise,
        };

        const message = pass
            ? () =>
                this.utils.matcherHint('toEqualHtmlString', undefined, undefined, options) +
                '\n\n' +
                `Expected: not ${this.utils.printExpected(expected)}\n` +
                `Received: ${this.utils.printReceived(actual)}`
            : () => {
                const diffString = diff(expected, actual, {
                    expand: this.expand,
                });
                return (
                    this.utils.matcherHint('toEqualHtmlString', undefined, undefined, options) +
                    '\n\n' +
                    (diffString && diffString.includes('- Expect')
                        ? `Difference:\n\n${diffString}`
                        : `Expected: ${this.utils.printExpected(expected)}\n` +
                        `Received: ${this.utils.printReceived(actual)}`)
                );
            };

        return {actual: actual, expected: expected, message, pass};


    },
};
