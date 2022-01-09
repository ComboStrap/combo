import Logger from "./Logger";

/**
 * DOM Xml/Html parsing
 * https://w3c.github.io/DOM-Parsing/
 */
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


    constructor(xmlString, type) {
        this.xmlString = xmlString;
        /**
         * https://developer.mozilla.org/en-US/docs/Web/API/DOMParser/parseFromString
         * @type {Document}
         */
        this.xmlDoc = new DOMParser().parseFromString(this.xmlString, type);
        const errorNode = this.xmlDoc.querySelector('parsererror');
        if (errorNode) {
            // parsing failed
            Logger.getLogger().error(`Error (${errorNode.textContent}) while parsing the (${type}) string: ${this.xmlString}`);
        }
        if (type === "text/html") {
            this.documentElement = this.xmlDoc.body.firstChild;
        } else {
            this.documentElement = this.xmlDoc.documentElement
        }
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


            return Xml.print(this.documentElement);
        }

    }

    static createFromXmlString(xmlString) {
        return new Xml(xmlString, "application/xml")
    }

    /**
     * Used when parsing HTML element that are not XHTML compliant
     * such as input that does not close.
     *
     * @param xmlString
     * @return {Xml}
     */
    static createFromHtmlString(xmlString) {
        return new Xml(xmlString, "text/html")
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
        // ToLowercase because the HTML DOMParser create uppercase node name on Node
        let nodeNameLowerCase = xmlElement.nodeName.toLowerCase();
        let enterTag = `${prefix}<${nodeNameLowerCase}`;
        if (xmlElement.hasAttributes()) {
            for (let attribute of xmlElement.getAttributeNames()) {
                let value = xmlElement.getAttribute(attribute);
                enterTag += ` ${attribute}`;
                if (value !== null && value !== "") {
                    enterTag += `="${value}"`;
                }
            }
        }
        enterTag += '>';
        output.push(enterTag)
        if (xmlElement.hasChildNodes()) {
            level++;
            let childNodes = xmlElement.childNodes;
            for (let i = 0; i < childNodes.length; i++) {
                let child = childNodes[i];
                let type = child.nodeType;
                switch (type) {
                    case child.TEXT_NODE:
                        /**
                         * Not complete
                         * as define here:
                         * https://w3c.github.io/DOM-Parsing/#xml-serializing-a-text-node
                         * but enough for now
                         * @type {string}
                         */
                        let textContent = child.textContent.trim();
                        if (textContent) {
                            output.push(`${prefix}${textContent}`);
                        }
                        break;
                    case child.ELEMENT_NODE:
                        if (child instanceof Element) {
                            Xml.walk(child, output, level);
                        }
                        break;
                }

            }
        }
        // input is a self-closing tag
        if (nodeNameLowerCase !== "input") {
            output.push(`${prefix}</${nodeNameLowerCase}>`);
        }
        return output;
    }

    static print(xmlElement) {
        let output = this.walk(xmlElement);
        return output.join("\n")
    }
}
