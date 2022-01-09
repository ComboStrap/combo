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
    let expected = '<input type="datetime-local" name="test" class="form-control" id="1">';
    let actual = formMetaField.toHtmlControl(1);
    expect(actual).toEqualHtmlString(expected)

});

test('Boolean field test', () => {

    let formMetaField = FormMetaField.createFromName("test")
        .setType(FormMetaField.BOOLEAN);

    let actual = formMetaField.toHtmlLabel("1","col-sm-6");
    let expected = `<label for="1" class="col-sm-6 form-check-label">
Test
</label>`;
    expect(actual).toEqualHtmlString(expected);

    /**
     * No value and default should not give any error
     */
    actual = formMetaField.toHtmlControl(1);
    expected = '<input type="checkbox" name="test" class="form-check-input" id="1">';
    expect(actual).toEqualHtmlString(expected);

    /**
     * No value and default should not give any error
     * We send a value when it's not the default
     */
    actual = formMetaField.toHtmlControl(1, null, false);
    expected = '<input type="checkbox" name="test" class="form-check-input" id="1" value="false">';
    expect(actual).toEqualHtmlString(expected);

    /**
     * No value and default should not give any error
     * We send a value when it's not the default
     */
    actual = formMetaField.toHtmlControl(1, true, true);
    expected = '<input type="checkbox" name="test" class="form-check-input" id="1" value="true" checked>';
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

test('Boolean Disabled', () => {

    let formMetaField = FormMetaField.createFromName("test")
        .setType(FormMetaField.BOOLEAN)
        .setMutable(false)


    let actual = formMetaField.toHtmlControl(1, true, true);
    let expected = '<input type="checkbox" name="test" class="form-check-input" id="1" value="true" checked disabled>';
    expect(actual).toEqualHtmlString(expected);



})


test('Json field test', () => {

    let formMetaField = FormMetaField.createFromName("json-test")
        .setType(FormMetaField.JSON)
        .setMutable(false);

    /**
     * No value and default should not give any error
     */
    let actual = formMetaField.toHtmlControl(1);
    let expected = `
<textarea id="1" name="json-test" class="form-control" rows="15" placeholder="No value" disabled>
</textarea>
`;
    expect(actual).toEqualHtmlString(expected);

})

test('Select field test', () => {

    let formMetaField = FormMetaField.createFromName("test")
        .setDomainValues(["blue","sky"]);

    /**
     * Label test
     * @type {string}
     */
    let actual = formMetaField.toHtmlLabel("1","col-sm-6");
    let expected = `<label for="1" class="col-sm-6 col-form-label">
Test
</label>`;
    expect(actual).toEqualHtmlString(expected);

    /**
     * Select test
     * @type {string}
     */
    actual = formMetaField.toHtmlControl("1","col-sm-6");
    expected = `<select class="form-select" aria-label="Test" name="test" id="1">
  <option value>
  Default (null)
  </option>
  <option value="blue">
  blue
  </option>
  <option value="sky">
  sky
  </option>
</select>`;
    expect(actual).toEqualHtmlString(expected);

    /**
     * Select multiple test
     */
    formMetaField.setMultiple(true)
    actual = formMetaField.toHtmlControl("1","col-sm-6");
    expected = `<select class="form-select" aria-label="Test" name="test" id="1" multiple>
  <option value>
  Default (null)
  </option>
  <option value="blue">
  blue
  </option>
  <option value="sky">
  sky
  </option>
</select>`;
    expect(actual).toEqualHtmlString(expected);




})

