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
    actual = formMetaField.toHtmlControl(1, null, "default");
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

test('Boolean field test', () => {

    let formMetaField = FormMetaField.createFromName("test")
        .setType(FormMetaField.BOOLEAN);

    /**
     * No value and default should not give any error
     */
    let actual = formMetaField.toHtmlControl(1);
    let expected = '<input type="checkbox" name="test" class="form-check-input" id="1">';
    expect(actual).toEqualHtmlString(expected);

    /**
     * No value and default should not give any error
     */
    actual = formMetaField.toHtmlControl(1, null, false);
    expected = '<input type="checkbox" name="test" class="form-check-input" id="1" value="false">';
    expect(actual).toEqualHtmlString(expected);

    /**
     * The default value is on
     */
    actual = formMetaField.toHtmlControl(1, "on");
    expected = '<input type="checkbox" name="test" class="form-check-input" id="1" checked>';
    expect(actual).toEqualHtmlString(expected);

    /**
     * Same value and default
     */
    actual = formMetaField.toHtmlControl(1, "same", "same");
    expected = '<input type="checkbox" name="test" class="form-check-input" id="1" value="same" checked>';
    expect(actual).toEqualHtmlString(expected);



})

test('Json field test', () => {

    let formMetaField = FormMetaField.createFromName("json test")
        .setType(FormMetaField.JSON);

    /**
     * No value and default should not give any error
     */
    let actual = formMetaField.toHtmlControl(1);
    let expected = '<input type="checkbox" name="test" class="form-check-input" id="1">';
    expect(actual).toEqualHtmlString(expected);

    /**
     * No value and default should not give any error
     */
    actual = formMetaField.toHtmlControl(1, null, false);
    expected = '<input type="checkbox" name="test" class="form-check-input" id="1" value="false">';
    expect(actual).toEqualHtmlString(expected);

    /**
     * The default value is on
     */
    actual = formMetaField.toHtmlControl(1, "on");
    expected = '<input type="checkbox" name="test" class="form-check-input" id="1" checked>';
    expect(actual).toEqualHtmlString(expected);

    /**
     * Same value and default
     */
    actual = formMetaField.toHtmlControl(1, "same", "same");
    expected = '<input type="checkbox" name="test" class="form-check-input" id="1" value="same" checked>';
    expect(actual).toEqualHtmlString(expected);



})
