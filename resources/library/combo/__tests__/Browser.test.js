import Browser from "../Browser";


/**
 * Jsdom in
 * Jest
 * https://github.com/jsdom/jsdom
 */
test('Browser environment', () => {

    expect(window).not.toBeNull();
    expect(window.document).not.toBeNull();
    expect(document).not.toBeNull();

})

/**
 * Because of jsdom has default environment,
 * this test works
 */
test('Browser Window detected', () => {

    let element = document.createElement("div");
    expect(Browser.hasWindow(element)).toBeTruthy();

});

test('Browser FormData to Object', () => {

    let formData = new FormData();
    formData.append('username', 'foo');
    /**
     * Two times the same value should become an array of two values
     */
    formData.append('cat', 'blue');
    formData.append('cat', 'red');
    let json = Browser.formDataToObject(formData);
    expect(json).toEqual(
        {
            username: 'foo',
            cat: ['blue', 'red']
        }
    )

});
