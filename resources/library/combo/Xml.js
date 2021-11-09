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

    /**
     *
     * @return {string} - a pretty print of the xml string
     */
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


            return Xml.print(this.xmlDoc.documentElement);
        }

    }

    static createFromString(xmlString) {
        return new Xml(xmlString)
    }

    /**
     *
     * @param {Element} xmlElement
     * @param output
     * @param level
     * @return {string[]}
     */
    static walk(xmlElement, output = [], level = 0) {

        let prefix = "  ".repeat(level);
        let enterTag = `${prefix}<${xmlElement.nodeName}`;
        if (xmlElement.hasAttributes()) {
            for (let attribute of xmlElement.getAttributeNames()) {
                let value = xmlElement.getAttribute(attribute);
                enterTag += ` ${attribute}="${value}"`;
            }
        }
        enterTag += '>';
        output.push(enterTag)
        if (xmlElement.hasChildNodes()) {
            level++;
            for (let child of xmlElement.children) {
                Xml.walk(child, output, level);
            }
        }
        output.push(`${prefix}</${xmlElement.nodeName}>`);
        return output;
    }

    static print(xmlElement) {
        let output = this.walk(xmlElement);
        return output.join("\n")
    }
}
