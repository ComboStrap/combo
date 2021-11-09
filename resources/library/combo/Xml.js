let prettifyXsltProcessor;


function initXslProcessor() {
    // describes how we want to modify the XML - indent everything
    let xsl = `
<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" >
  <xsl:strip-space elements="*"/>
   <!-- change to just text() to strip space in text nodes -->
  <xsl:template match="para[content-style][not(text())]">
    <xsl:value-of select="normalize-space(.)"/>
  </xsl:template>
  <xsl:template match="node()|@*">
    <xsl:copy><xsl:apply-templates select="node()|@*"/></xsl:copy>
  </xsl:template>
  <xsl:output indent="yes"/>
</xsl:stylesheet>`;
    /**
     * Only in the browser
     * @type {XSLTProcessor}
     */
    let xsltProcessor = new XSLTProcessor();
    let xslDocument = new DOMParser().parseFromString(xsl, 'application/xml');
    xsltProcessor.importStylesheet(xslDocument);
}

export default class Xml {

    constructor(xmlString) {
        this.xmlString = xmlString;
        this.xmlDoc = new DOMParser().parseFromString(this.xmlString, 'application/xml');
    }

    normalize() {

        /**
         * https://developer.mozilla.org/en-US/docs/Web/API/XSLTProcessor
         */
        if (typeof XSLTProcessor !== "undefined") {

            if (typeof prettifyXsltProcessor === "undefined") {
                initXslProcessor();
            }
            let resultDoc = prettifyXsltProcessor.transformToDocument(this.xmlDoc);
            return new XMLSerializer().serializeToString(resultDoc);
        } else {
            // https://github.com/beautify-web/js-beautify
            // https://beautifier.io/
            let prettify = Xml.print(this.xmlDoc)

            return `<${this.xmlDoc.documentElement.nodeName}>
    ${this.xmlDoc.documentElement.innerHTML}
<${this.xmlDoc.documentElement.nodeName}>
`
        }

    }

    static createFromString(xmlString) {
        return new Xml(xmlString)
    }

    static print(xmlDoc, output) {

        if(output===undefined){
            output = ""
        }
        output=`<`
        return undefined;
    }
}
