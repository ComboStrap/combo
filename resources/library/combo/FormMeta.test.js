/**
 * @jest-environment jsdom
 * https://jestjs.io/docs/configuration#testenvironment-string
 */


import FormMeta from "./FormMeta";

test('Json to Form Object', () => {

    let formName = "my-form";
    let firstFieldName = "first";
    let firstFieldLabel = "Youpla";
    let firstFieldTab = "TheTab";
    let firstFieldType = "text";
    let firstFieldMutable = true;
    let firstFieldUrl = "https:\/\/combostrap.com\/first";
    let firstFieldDescription = "The big youpla";
    let firstFieldDefaultValue = "Meta Manager";
    let firstFieldValue = 1;
    let firstFieldDomainValues = [
        "holy",
        "median",
        "landing"
    ];
    let secondFieldName = "second";
    let firstFieldWidth = 8;
    let secondFieldValue = [
        null,
        null,
        null,
        null,
        null
    ];
    let secondFieldDefaultValue = [
        ":illustration.png",
        null,
        null,
        null,
        null
    ];
    let firstTabLabel = "label first tab";
    let firstTabWidthField = 8;
    let firstTabWidhtLabel = 4;
    let formMetadata = {
        "name": formName,
        "fields": {
            "first": {
                "name": firstFieldName,
                "label": firstFieldLabel,
                "tab": firstFieldTab,
                "type": firstFieldType,
                "mutable": firstFieldMutable,
                "url": firstFieldUrl,
                "description": firstFieldDescription,
                "default": firstFieldDefaultValue,
                "value": firstFieldValue,
                "domain-values": firstFieldDomainValues,
                "width": firstFieldWidth,
            },
            "second": {
                "name": secondFieldName,
                "value": secondFieldValue,
                "default": secondFieldDefaultValue
            }
        },
        "tabs": {
            firstFieldTab:
                {
                    "name": firstFieldTab,
                    "label": firstTabLabel,
                    "width-field": firstTabWidthField,
                    "width-label": firstTabWidhtLabel
                }
        }
    };
    let formMeta = FormMeta.createFromJson(formMetadata);
    expect(formMeta.getName()).toBe(formName);
    let fields = formMeta.getFields();
    expect(fields.length).toBe(2);

    /**
     * @type {FormMetaField}
     */
    let field = fields[0];
    expect(field.getName()).toBe(firstFieldName);
    expect(field.getLabel()).toBe(firstFieldLabel);
    expect(field.getTab()).toBe(firstFieldTab);
    expect(field.getType()).toBe(firstFieldType);
    expect(field.isMutable()).toBe(firstFieldMutable);
    expect(field.getUrl()).toBe(firstFieldUrl);
    expect(field.getDescription()).toBe(firstFieldDescription);
    expect(field.getDescription()).toBe(firstFieldDescription);
    expect(field.getDefaultValue()).toBe(firstFieldDefaultValue);
    expect(field.getValue()).toBe(firstFieldValue);
    expect(field.getDomainValues()).toBe(firstFieldDomainValues);
    expect(field.getControlWidth()).toBe(firstFieldWidth);

    let field2 = fields[1];
    expect(field2.getName()).toBe(secondFieldName);
    expect(field2.getValues()).toEqual(secondFieldValue);
    expect(field2.getDefaultValues()).toEqual(secondFieldDefaultValue);

    /**
     * Tab
     */
    let tabs = formMeta.getTabs()
    expect(tabs.length).toBe(1);
    let firstTab = tabs[0];
    expect(firstTab.getName()).toBe(firstFieldTab);
    expect(firstTab.getLabel()).toBe(firstTabLabel);
    expect(firstTab.getLabelWidth()).toBe(firstTabWidhtLabel);
    expect(firstTab.getFieldWidth()).toBe(firstTabWidthField);

    /**
     * To html
     */
    let htmlForm = formMeta.toHtmlElement("formId")
    let actual = htmlForm.outerHTML;
    expect(actual).toEqual("<form></form>");

});
