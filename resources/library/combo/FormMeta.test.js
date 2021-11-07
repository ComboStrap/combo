/**
 * @jest-environment jsdom
 * https://jestjs.io/docs/configuration#testenvironment-string
 */



import FormMeta from "./FormMeta";

test('Json to Form Object', () => {

    let formName = "my-form";
    let formMetadata = {
        "name": formName,
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
    let form = FormMeta.createFromJson(formMetadata);
    expect(form.getName()).toBe(formName);
    let fields = form.getFields();
    expect(fields.length).toBe(1);

});
