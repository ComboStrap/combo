import Xml from "../Xml";


test('Xml', () => {

    let xhtml =
`
<form id="1"><div><div><span>Test</span></div></div></form>
`
    let xml = Xml.createFromXmlString(xhtml);
    let actual = xml.normalize();
    let expected = `<form id="1">
  <div>
    <div>
      <span>
      Test
      </span>
    </div>
  </div>
</form>`
    expect(actual).toBe(expected);

})

/**
 * input is a self-closing tag
 * that is no xml compliant
 * The DOM tree should then be build with HTML
 */
test('html', () => {

    let xhtml =
        `
<form id="1"><div><div><input checked></div></div></form>
`
    let xml = Xml.createFromHtmlString(xhtml);
    let actual = xml.normalize();
    let expected = `<form id="1">
  <div>
    <div>
      <input checked>
    </div>
  </div>
</form>`
    expect(actual).toBe(expected);

})
