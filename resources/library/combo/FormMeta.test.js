/**
 * @jest-environment jsdom
 * https://jestjs.io/docs/configuration#testenvironment-string
 */



import FormMeta from "./FormMeta";

test('Json to Form Object', () => {

    let formName = "my-form";
    let firstFiedlName = "first";
    let firstFieldLabel = "Youpla";
    let firstFieldTab = "TheTab";
    let firstFieldType = "text";
    let firstFieldMutable = true;
    let formMetadata = {
        "name": formName,
        "fields": {
            "first": {
                "name": firstFiedlName,
                "label": firstFieldLabel,
                "tab": firstFieldTab,
                "type": firstFieldType,
                "mutable": firstFieldMutable,
                "link": "<a href=\"https:\/\/combostrap.com\/first\" title=\"The big youpla\" data-bs-toggle=\"tooltip\" style=\"text-decoration:none;\">Youpla<\/a>"
            }
        },
        "tabs": {"TheTab": {"name": "TheTab"}}
    };
    let form = FormMeta.createFromJson(formMetadata);
    expect(form.getName()).toBe(formName);
    let fields = form.getFields();
    expect(fields.length).toBe(1);
    let field = fields[0];
    expect(field.getName()).toBe(firstFiedlName);
    expect(field.getLabel()).toBe(firstFieldLabel);
    expect(field.getTab()).toBe(firstFieldTab);
    expect(field.getType()).toBe(firstFieldType);
    expect(field.isMutable()).toBe(firstFieldMutable);

});
