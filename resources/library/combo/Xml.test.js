import Xml from "./Xml";


test('Xml', () => {

    let xhtml =
`
<form id="1"><div><div><span></span></div></div></form>
`
    let xml = Xml.createFromString(xhtml);
    let actual = xml.normalize();
    expect(actual).toBe("");

})
