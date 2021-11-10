import FormMetaField from "../FormMetaField";
import Xml from "../Xml";


test('Text field test', () => {

    let formMetaField = FormMetaField.createFromName("test")

    /**
     * No value and default should not give any error
     */
    let actual = formMetaField.toHtmlControl(1);
    let expected = '<input type="text" name="test" class="form-control" id="1" placeholder="Enter a Test">';
    let actualNormalized = Xml.createFromHtmlString(actual).normalize();
    expect(actualNormalized).toBe(expected);

    /**
     * No value and default should not give any error
     */
    actual = formMetaField.toHtmlControl(1,null,"default");
    expected = '<input type="text" name="test" class="form-control" id="1" placeholder="default">';
    actualNormalized = Xml.createFromHtmlString(actual).normalize();
    expect(actualNormalized).toBe(expected);

});

test('Datetime test', () => {

    /**
     * No value
     * @type {FormMetaField}
     */
    let formMetaField = FormMetaField.createFromName("test")
        .setType(FormMetaField.DATE_TIME);
    let expected = '<input type="datetime-local" name="test" class="form-control" id="1" placeholder="Enter a Test">';
    let actual = formMetaField.toHtmlControl(1);
    let actualNormalized = Xml.createFromHtmlString(actual).normalize();
    expect(actualNormalized).toBe(expected)

});
