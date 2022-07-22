<?php
/**
 * Copyright (c) 2021. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Exception;
use LibXMLError;
use PhpCss;
use PHPUnit\Util\Xml;


require_once(__DIR__ . '/File.php');

class XmlDocument
{
    const HTML_TYPE = "html";
    const XML_TYPE = "xml";
    /**
     * The error that the HTML loading
     * may returns
     */
    const KNOWN_HTML_LOADING_ERRORS = [
        "Tag section invalid\n", // section is HTML5 tag
        "Tag footer invalid\n", // footer is HTML5 tag
        "error parsing attribute name\n", // name is an HTML5 attribute
        "Unexpected end tag : blockquote\n", // name is an HTML5 attribute
        "Tag bdi invalid\n",
        "Tag path invalid\n", // svg
        "Tag svg invalid\n", // svg
        "Unexpected end tag : a\n", // when the document is only a anchor
        "Unexpected end tag : p\n", // when the document is only a p
        "Unexpected end tag : button\n", // when the document is only a button
    ];

    const CANONICAL = "xml";

    /**
     * @var DOMDocument
     */
    private DOMDocument $domDocument;
    /**
     * @var DOMXPath
     */
    private DOMXPath $domXpath;

    /**
     * XmlFile constructor.
     * @param $text
     * @param string $type - HTML or not
     * @throws ExceptionBadSyntax - if the document is not valid or the lib xml is not available
     *
     * Getting the width of an error HTML document if the file was downloaded
     * from a server has no use at all
     */
    public function __construct($text, string $type = self::XML_TYPE)
    {

        if (!$this->isXmlExtensionLoaded()) {
            /**
             * If the XML module is not present
             */
            throw new ExceptionBadSyntax("The php `libxml` module was not found on your installation, the xml/svg file could not be modified / instantiated", self::CANONICAL);
        }

        // https://www.php.net/manual/en/libxml.constants.php
        $options = LIBXML_NOCDATA
            // | LIBXML_NOBLANKS // same as preserveWhiteSpace=true, not set to be able to format the output
            | LIBXML_NOXMLDECL // Drop the XML declaration when saving a document
            | LIBXML_NONET // No network during load
            | LIBXML_NSCLEAN // Remove redundant namespace declarations - for whatever reason, the formatting does not work if this is set
        ;

        // HTML
        if ($type == self::HTML_TYPE) {

            // Options that cause the process to hang if this is not for a html file
            // Empty tag option may also be used only on save
            //   at https://www.php.net/manual/en/domdocument.save.php
            //   and https://www.php.net/manual/en/domdocument.savexml.php
            $options = $options
                // | LIBXML_NOEMPTYTAG // Expand empty tags (e.g. <br/> to <br></br>)
                | LIBXML_HTML_NODEFDTD // No doctype
                | LIBXML_HTML_NOIMPLIED;


        }

        /**
         * No warning reporting
         * Load XML issue E_STRICT warning seen in the log
         */
        if (!PluginUtility::isTest()) {
            $oldLevel = error_reporting(E_ERROR);
        }

        $this->domDocument = new DOMDocument('1.0', 'UTF-8');

        $this->mandatoryFormatConfigBeforeLoading();


        $text = $this->processTextBeforeLoading($text);

        /**
         * Because the load does handle HTML5tag as error
         * (ie section for instance)
         * We take over the errors and handle them after the below load
         *
         * https://www.php.net/manual/en/function.libxml-use-internal-errors.php
         *
         */
        libxml_use_internal_errors(true);

        if ($type == self::XML_TYPE) {

            $result = $this->domDocument->loadXML($text, $options);

        } else {

            /**
             * Unlike loading XML, HTML does not have to be well-formed to load.
             * While malformed HTML should load successfully, this function may generate E_WARNING errors
             * @deprecated as we try to be XHTML compliantXML but yeah this is not always possible
             */

            /**
             * Bug: Even if we set that the document is an UTF-8
             * loadHTML treat the string as being in ISO-8859-1 if without any heading
             * (ie <xml encoding="utf-8"..>
             * https://stackoverflow.com/questions/8218230/php-domdocument-loadhtml-not-encoding-utf-8-correctly
             * Otherwise French and other language are not well loaded
             *
             * We use the trick to transform UTF-8 to HTML
             */
            $htmlEntityEncoded = mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8');
            $result = $this->domDocument->loadHTML($htmlEntityEncoded, $options);

        }
        if ($result === false) {

            /**
             * Error
             */
            $errors = libxml_get_errors();

            foreach ($errors as $error) {

                /* @var LibXMLError
                 * @noinspection PhpComposerExtensionStubsInspection
                 *
                 * Section is an html5 tag (and is invalid for libxml)
                 */
                if (!in_array($error->message, self::KNOWN_HTML_LOADING_ERRORS)) {
                    /**
                     * This error is an XML and HTML error
                     */
                    if (
                        strpos($error->message, "htmlParseEntityRef: expecting ';' in Entity") !== false
                        ||
                        $error->message == "EntityRef: expecting ';'\n"
                    ) {
                        $message = "There is big probability that there is an ampersand alone `&`. ie You forgot to call html/Xml entities in a `src` or `url` attribute.";
                    } else {
                        $message = "Error while loading HTML";
                    }
                    /**
                     * inboolean attribute XML loading error
                     */
                    if (strpos($error->message, "Specification mandates value for attribute") !== false) {
                        $message = "Xml does not allow boolean attribute (ie without any value). If you skip this error, you will get a general attribute constructing error as next error. Load as HTML.";
                    }

                    $message .= "Error: " . $error->message . ", Loaded text: " . $text;

                    /**
                     * We clean the errors, otherwise
                     * in a test series, they failed the next test
                     *
                     */
                    libxml_clear_errors();

                    // The xml dom object is null, we got NULL pointer exception everywhere
                    // just throw, the code will see it
                    throw new ExceptionBadSyntax($message, self::CANONICAL);

                }

            }
        }

        /**
         * We clean the known errors (otherwise they are added in a queue)
         */
        libxml_clear_errors();

        /**
         * Error reporting back
         */
        if (!PluginUtility::isTest() && isset($oldLevel)) {
            error_reporting($oldLevel);
        }

        // namespace error : Namespace prefix dc on format is not defined
        // missing the ns declaration in the file. example:
        // xmlns:dc="http://purl.org/dc/elements/1.1/"


    }

    /**
     * To not have a collusion with {@link FetcherSvg::createFetchImageSvgFromPath()}
     * @param Path $path
     * @return XmlDocument
     * @throws ExceptionNotFound - if the file does not exist
     * @throws ExceptionBadSyntax - if the content is not valid
     */
    public
    static function createXmlDocFromPath(Path $path): XmlDocument
    {
        $mime = XmlDocument::XML_TYPE;
        if (in_array($path->getExtension(), ["html", "htm"])) {
            $mime = XmlDocument::HTML_TYPE;
        }
        $content = FileSystems::getContent($path);
        return (new XmlDocument($content, $mime));
    }

    /**
     *
     * @throws ExceptionBadSyntax
     */
    public
    static function createXmlDocFromMarkup($string, $asHtml = false): XmlDocument
    {

        $mime = XmlDocument::XML_TYPE;
        if ($asHtml) {
            $mime = XmlDocument::HTML_TYPE;
        }
        return new XmlDocument($string, $mime);
    }

    /**
     * HTML loading is more permissive
     *
     * For instance, you would not get an error on boolean attribute
     * ```
     * Error while loading HTMLError: Specification mandates value for attribute defer
     * ```
     * In Xml, it's mandatory but not in HTML, they are known as:
     * https://html.spec.whatwg.org/multipage/common-microsyntaxes.html#boolean-attribute
     *
     *
     * @throws ExceptionBadSyntax
     */
    public static function createHtmlDocFromMarkup($markup): XmlDocument
    {
        return self::createXmlDocFromMarkup($markup, true);
    }

    public
    function &getDomDocument(): DOMDocument
    {
        return $this->domDocument;
    }

    /**
     * @param $name
     * @param $value
     * @return void
     * @deprecated use {@link XmlDocument::getElement()} instead
     */
    public function setRootAttribute($name, $value)
    {
        if ($this->isXmlExtensionLoaded()) {
            $this->domDocument->documentElement->setAttribute($name, $value);
        }
    }

    /**
     * @param $name
     * @return string null if not found
     */
    public function getRootAttributeValue($name): ?string
    {
        $value = $this->domDocument->documentElement->getAttribute($name);
        if ($value === "") {
            return null;
        }
        return $value;
    }

    public function toXhtml(DOMElement $element = null): string
    {
        return $this->toXml($element);
    }

    public function toXml(DOMElement $element = null): string
    {

        if ($element === null) {
            $element = $this->getDomDocument()->documentElement;
        }
        /**
         * LIBXML_NOXMLDECL (no xml declaration) does not work because only empty tag is recognized
         * https://www.php.net/manual/en/domdocument.savexml.php
         */
        $xmlText = $this->getDomDocument()->saveXML(
            $element,
            LIBXML_NOXMLDECL
        );
        // Delete doctype (for svg optimization)
        // php has only doctype manipulation for HTML
        $xmlText = preg_replace('/^<!DOCTYPE.+?>/', '', $xmlText);
        return trim($xmlText);

    }

    /**
     * https://www.php.net/manual/en/dom.installation.php
     *
     * Check it with
     * ```
     * php -m
     * ```
     * Install with
     * ```
     * sudo apt-get install php-xml
     * ```
     * @return bool
     */
    public function isXmlExtensionLoaded(): bool
    {
        // A suffix used in the bad message
        $suffixBadMessage = "php extension is not installed. To install it, you need to install xml. Example: `sudo apt-get install php-xml`, `yum install php-xml`";

        // https://www.php.net/manual/en/dom.requirements.php
        $loaded = extension_loaded("libxml");
        if ($loaded === false) {
            LogUtility::msg("The libxml {$suffixBadMessage}");
        } else {
            $loaded = extension_loaded("xml");
            if ($loaded === false) {
                LogUtility::msg("The xml {$suffixBadMessage}");
            } else {
                $loaded = extension_loaded("dom");
                if ($loaded === false) {
                    LogUtility::msg("The dom {$suffixBadMessage}");
                }
            }
        }
        return $loaded;
    }

    /**
     * https://stackoverflow.com/questions/30257438/how-to-completely-remove-a-namespace-using-domdocument
     * @param $namespaceUri
     */
    function removeNamespace($namespaceUri)
    {
        if (empty($namespaceUri)) {
            throw new \RuntimeException("The namespace is empty and should be specified");
        }

        if (strpos($namespaceUri, "http") === false) {
            LogUtility::msg("Internal warning: The namespaceURI ($namespaceUri) does not seems to be an URI", LogUtility::LVL_MSG_WARNING, "support");
        }

        /**
         * @var DOMNodeList $nodes
         * finds all nodes that have a namespace node called $ns where their parent node doesn't also have the same namespace.
         * @var DOMNodeList $nodes
         */
        try {
            $nodes = $this->xpath("//*[namespace-uri()='$namespaceUri']");
            foreach ($nodes as $node) {
                /** @var DOMElement $node */
                $node->parentNode->removeChild($node);
            }
        } catch (ExceptionBadSyntax $e) {
            LogUtility::error("Internal Error on xpath: {$e->getMessage()}");
        }

        try {
            $nodes = $this->xpath("//@*[namespace-uri()='$namespaceUri']");
            foreach ($nodes as $node) {
                /** @var DOMAttr $node */
                /** @var DOMElement $DOMNode */
                $DOMNode = $node->parentNode;
                $DOMNode->removeAttributeNode($node);
            }
        } catch (ExceptionBadSyntax $e) {
            LogUtility::error("Internal Error on xpath: {$e->getMessage()}");
        }


        //Node namespace can be select only from the document
        $xpath = new DOMXPath($this->getDomDocument());
        $DOMNodeList = $xpath->query("namespace::*", $this->getDomDocument()->ownerDocument);
        foreach ($DOMNodeList as $node) {
            $namespaceURI = $node->namespaceURI;
            if ($namespaceURI == $namespaceUri) {
                $parentNode = $node->parentNode;
                $parentNode->removeAttributeNS($namespaceUri, $node->localName);
            }
        }


    }

    public function getNamespaces(): array
    {
        /**
         * We can't query with the library {@link XmlDocument::xpath()} function because
         * we register in the xpath the namespace
         */
        $xpath = new DOMXPath($this->getDomDocument());
        // `namespace::*` means selects all the namespace attribute of the context node
        // namespace is an axes
        // See https://www.w3.org/TR/1999/REC-xpath-19991116/#axes
        // the namespace axis contains the namespace nodes of the context node; the axis will be empty unless the context node is an element
        $DOMNodeList = $xpath->query('namespace::*', $this->getDomDocument()->ownerDocument);
        $nameSpace = array();
        foreach ($DOMNodeList as $node) {
            /** @var DOMElement $node */

            $namespaceURI = $node->namespaceURI;
            $localName = $node->prefix;
            if ($namespaceURI != null) {
                $nameSpace[$localName] = $namespaceURI;
            }
        }
        return $nameSpace;
    }

    /**
     * A wrapper that register namespace for the query
     * with the defined prefix
     * See comment:
     * https://www.php.net/manual/en/domxpath.registernamespace.php#51480
     * @param $query
     * @param DOMElement|null $contextNode
     * @return DOMNodeList
     *
     * Note that this is possible to do evaluation to return a string instead
     * https://www.php.net/manual/en/domxpath.evaluate.php
     * @throws ExceptionBadSyntax - if the query is invalid
     */
    public
    function xpath($query, DOMElement $contextNode = null): DOMNodeList
    {
        if (!isset($this->domXpath)) {

            $this->domXpath = new DOMXPath($this->getDomDocument());

            /**
             * Prefix mapping
             * It is necessary to use xpath to handle documents which have default namespaces.
             * The xpath expression will search for items with no namespace by default.
             */
            foreach ($this->getNamespaces() as $prefix => $namespaceUri) {
                /**
                 * You can't register an empty prefix
                 * Default namespace (without a prefix) can only be accessed by the local-name() and namespace-uri() attributes.
                 */
                if (!empty($prefix)) {
                    $result = $this->domXpath->registerNamespace($prefix, $namespaceUri);
                    if (!$result) {
                        LogUtility::msg("Not able to register the prefix ($prefix) for the namespace uri ($namespaceUri)");
                    }
                }
            }
        }

        if ($contextNode === null) {
            $contextNode = $this->domDocument;
        }
        $domList = $this->domXpath->query($query, $contextNode);
        if ($domList === false) {
            throw new ExceptionBadSyntax("The query expression ($query) may be malformed");
        }
        return $domList;

    }


    public
    function removeRootAttribute($attribute)
    {

        // This function does not work
        // $result = $this->getXmlDom()->documentElement->removeAttribute($attribute);

        for ($i = 0; $i < $this->getDomDocument()->documentElement->attributes->length; $i++) {
            if ($this->getDomDocument()->documentElement->attributes[$i]->name == $attribute) {
                $result = $this->getDomDocument()->documentElement->removeAttributeNode($this->getDomDocument()->documentElement->attributes[$i]);
                if ($result === false) {
                    throw new \RuntimeException("Not able to delete the $attribute");
                }
                // There is no break here because you may find multiple version attribute for instance
            }
        }

    }

    public
    function removeRootChildNode($nodeName)
    {
        for ($i = 0; $i < $this->getDomDocument()->documentElement->childNodes->length; $i++) {
            $childNode = &$this->getDomDocument()->documentElement->childNodes[$i];
            if ($childNode->nodeName == $nodeName) {
                $result = $this->getDomDocument()->documentElement->removeChild($childNode);
                if ($result == false) {
                    throw new \RuntimeException("Not able to delete the child node $nodeName");
                }
                break;
            }
        }
    }

    /**
     *
     * Add a value to an attribute value
     * Example
     * <a class="actual">
     *
     * if you add "new"
     * <a class="actual new">
     *
     * @param $attName
     * @param $attValue
     * @param DOMElement $xml
     */
    public
    function addAttributeValue($attName, $attValue, $xml)
    {

        /**
         * Empty condition is better than {@link DOMElement::hasAttribute()}
         * because even if the dom element has the attribute, the value
         * may be empty
         */
        $value = $xml->getAttribute($attName);
        if (empty($value)) {
            $xml->setAttribute($attName, $attValue);
        } else {
            $actualAttValue = $xml->getAttribute($attName);
            $explodeArray = explode(" ", $actualAttValue);
            if (!in_array($attValue, $explodeArray)) {
                $xml->setAttribute($attName, (string)$actualAttValue . " $attValue");
            }
        }

    }

    public function diff(XmlDocument $rightDocument)
    {
        $error = "";
        XmlUtility::diffNode($this->getDomDocument(), $rightDocument->getDomDocument(), $error);
        return $error;
    }

    /**
     * @return string a XML formatted
     *
     * !!!! The parameter preserveWhiteSpace should have been set to false before loading
     * https://www.php.net/manual/en/class.domdocument.php#domdocument.props.formatoutput
     * $this->xmlDom->preserveWhiteSpace = false;
     *
     * We do it with the function {@link XmlDocument::mandatoryFormatConfigBeforeLoading()}
     *
     */
    public function toXmlFormatted(DOMElement $element = null): string
    {

        $this->domDocument->formatOutput = true;
        return $this->toXml($element);

    }

    /**
     * @return string that can be diff
     *   * EOL diff are not seen
     *   * space are
     *
     * See also {@link XmlDocument::processTextBeforeLoading()}
     * that is needed before loading
     */
    public function toXmlNormalized(DOMElement $element = null): string
    {

        /**
         * If the text was a list
         * of sibling text without parent
         * We may get a body
         * @deprecated letting the code until
         * TODO: delete this code when the test pass
         */
//        $body = $doc->getElementsByTagName("body");
//        if ($body->length != 0) {
//            $DOMNodeList = $body->item(0)->childNodes;
//            $output = "";
//            foreach ($DOMNodeList as $value) {
//                $output .= $doc->saveXML($value) . DOKU_LF;
//            }
//        }

        if ($element == null) {
            $element = $this->domDocument->documentElement;
        }
        $element->normalize();
        return $this->toXmlFormatted($element);
    }

    /**
     * Not really conventional but
     * to be able to {@link toXmlNormalized}
     * the EOL should be deleted
     * We do it before loading and not with a XML documentation
     */
    private function processTextBeforeLoading($text)
    {
        $text = str_replace(DOKU_LF, "", $text);
        $text = preg_replace("/\r\n\s*\r\n/", "\r\n", $text);
        $text = preg_replace("/\n\s*\n/", "\n", $text);
        $text = preg_replace("/\n\n/", "\n", $text);
        return $text;

    }


    /**
     * This function is called just before loading
     * in order to be able to {@link XmlDocument::toXmlFormatted() format the output }
     * https://www.php.net/manual/en/class.domdocument.php#domdocument.props.formatoutput
     * Mandatory for a a good formatting before loading
     *
     */
    private function mandatoryFormatConfigBeforeLoading()
    {
        // not that
        // the loading option: LIBXML_NOBLANKS
        // is equivalent to $this->xmlDom->preserveWhiteSpace = true;
        $this->domDocument->preserveWhiteSpace = false;
    }

    /**
     * @param string $attributeName
     * @param DOMElement $nodeElement
     * @return void
     * @deprecated use the {@link XmlElement::removeAttribute()} if possible
     */
    public function removeAttributeValue(string $attributeName, DOMElement $nodeElement)
    {
        $attr = $nodeElement->getAttributeNode($attributeName);
        if ($attr == false) {
            return;
        }
        $result = $nodeElement->removeAttributeNode($attr);
        if ($result === false) {
            LogUtility::msg("Not able to delete the attribute $attributeName of the node element $nodeElement->tagName in the Xml document");
        }
    }


    /**
     * Query via a CSS selector
     * (not that it will not work with other namespace than the default one, ie xmlns will not work)
     * @throws ExceptionBadSyntax - if the selector is not valid
     * @throws ExceptionNotFound - if the selector selects nothing
     */
    public function querySelector(string $selector): XmlElement
    {
        $domNodeList = $this->querySelectorAll($selector);
        if (sizeof($domNodeList) >= 1) {
            return $domNodeList[0];
        }
        throw new ExceptionNotFound("No element was found with the selector $selector");

    }

    /**
     * @return XmlElement[]
     * @throws ExceptionBadSyntax
     */
    public function querySelectorAll(string $selector): array
    {
        $xpath = $this->cssSelectorToXpath($selector);
        $domNodeList = $this->xpath($xpath);
        $domNodes = [];
        foreach ($domNodeList as $domNode) {
            if ($domNode instanceof DOMElement) {
                $domNodes[] = new XmlElement($domNode, $this);
            }
        }
        return $domNodes;

    }

    /**
     * @throws ExceptionBadSyntax
     */
    public function cssSelectorToXpath(string $selector): string
    {
        try {
            return PhpCss::toXpath($selector);
        } catch (PhpCss\Exception\ParserException $e) {
            throw new ExceptionBadSyntax("The selector ($selector) is not valid. Error: {$e->getMessage()}");
        }
    }

    /**
     * An utility function to know how to remove a node
     * @param \DOMNode $nodeElement
     * @deprecated use {@link XmlElement::remove} instead
     */
    public function removeNode(\DOMNode $nodeElement)
    {

        $nodeElement->parentNode->removeChild($nodeElement);

    }

    public function getElement(): XmlElement
    {
        return XmlElement::create($this->getDomDocument()->documentElement, $this);
    }

    public function toHtml()
    {
        return $this->domDocument->saveHTML();
    }

    /**
     * @throws \DOMException - if invalid local name
     */
    public function createElement(string $localName): XmlElement
    {
        $element = $this->domDocument->createElement($localName);
        return XmlElement::create($element, $this);
    }


}
