let combo = require("../combo.js");

/**
 * @jest-environment jsdom
 * https://jestjs.io/docs/configuration#testenvironment-string
 */
test('first test', () => {

    let formMetadata = {
        "name": "my-form",
        "fields": {
            "first": {
                "name": "first",
                "label": "Youpla",
                "tab": "TheTab",
                "type": "text",
                "mutable": true,
                "link": "<a href=\"https:\/\/combostrap.com\/first\" title=\"The big youpla\" data-bs-toggle=\"tooltip\" style=\"text-decoration:none;\">Youpla<\/a>"
            }
        },
        "tabs": {"TheTab": {"name": "TheTab"}}
    };
    let form = combo.toForm("formId", formMetadata);
    expect(form).toBe("");

});

test('adds 1 + 2 to equal 3', () => {
    let a = 1;
    let b = 1;
    expect(a).toBe(b);
});
