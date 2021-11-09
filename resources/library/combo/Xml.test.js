import Xml from "./Xml";


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
