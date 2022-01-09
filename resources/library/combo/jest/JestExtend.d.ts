declare namespace jest {
    interface Matchers<R, T = {}> {
        /**
         * Check that a HTML string is an HTML string
         * It will normalize the string before making a string equality
         * See file `JestExtends.js` for the implementation
         * @param expected
         */
        toEqualHtmlString<E = any>(expected: E): R;
    }
}
