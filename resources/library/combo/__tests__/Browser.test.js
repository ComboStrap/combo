import Browser from "../Browser";
import Json from "../Json";


/**
 * Because of jsdom has default environment,
 * this test works
 */
test('Browser Window detected', () => {

    let element  = document.createElement("div");
    expect(Browser.hasWindow(element)).toBeTruthy();

});

test('Browser FormData to Object', () => {

    let formData = new FormData();
    formData.append('username', 'foo');
    formData.append('cat', 'blue');
    formData.append('cat', 'red');
    let json = Browser.formDataToObject(formData);
    expect(json).toEqual(
        {
            username: 'foo',
            cat: ['blue','red']
        })

});
