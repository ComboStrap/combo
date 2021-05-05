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


require_once(__DIR__ . '/File.php');

class XmlDocument
{
    const HTML_TYPE = "html";
    const XML_TYPE = "xml";

    /**
     * @var DOMDocument
     */
    private $xmlDom = null;

    /**
     * XmlFile constructor.
     * @param $text
     * @param $type - HTML or not
     */
    public function __construct($text, $type = self::XML_TYPE)
    {


        if ($this->isXmlExtensionLoaded()) {
            try {
                // https://www.php.net/manual/en/libxml.constants.php
                $options = LIBXML_NOCDATA
                    | LIBXML_NOBLANKS
                    | LIBXML_NSCLEAN // Remove redundant namespace declarations
                    | LIBXML_NOXMLDECL // Drop the XML declaration when saving a document
                    | LIBXML_NONET // No network during load
                ;

                // HTML
                if ($type == self::HTML_TYPE) {
                    // Options that cause the processus to hang if this is not for a html file
                    // Empty tag option may also be used only on save
                    //   at https://www.php.net/manual/en/domdocument.save.php
                    //   and https://www.php.net/manual/en/domdocument.savexml.php
                    $options = $options
                        | LIBXML_NOEMPTYTAG
                        | LIBXML_HTML_NOIMPLIED
                        | LIBXML_HTML_NODEFDTD // No doctype
                    ;
                }

                $this->xmlDom = new DOMDocument();
                $result = $this->xmlDom->loadXML($text, $options);
                if ($result === false) {
                    LogUtility::msg("Internal Error: Unable to load the DOM from the file ($this)", LogUtility::LVL_MSG_ERROR, "support");
                }
                // namespace error : Namespace prefix dc on format is not defined
                // missing the ns declaration in the file. example:
                // xmlns:dc="http://purl.org/dc/elements/1.1/"
            } catch (Exception $e) {
                /**
                 * Don't use {@link \ComboStrap\LogUtility::msg()}
                 * or you will get a recursion
                 * because the URL has an SVG icon that calls this file
                 */
                $msg = "The text ($text) seems to not be an XML text. It could not be loaded. Don't pass a file but a text. The error returned is $e";
                LogUtility::msg($msg, LogUtility::LVL_MSG_ERROR, "support");
                if (defined('DOKU_UNITTEST')) {
                    throw new \RuntimeException($msg);
                }
            }

        } else {

            /**
             * If the XML module is not present
             */
            LogUtility::msg("The php `libxml` module was not found on your installation, the xml/svg file could not be modified / instantiated", LogUtility::LVL_MSG_ERROR, "support");


        }

    }

    /**
     * @param File $file
     */
    public
    static function createFromPath($file)
    {
        $mime = XmlDocument::XML_TYPE;
        if (in_array($file->getExtension(), ["html", "htm"])) {
            $mime = XmlDocument::HTML_TYPE;
        }
        return new XmlDocument($file->getContent(), $mime);
    }

    public
    function &getXmlDom()
    {
        return $this->xmlDom;
    }

    public
    function setRootAttribute($string, $name)
    {
        if ($this->isXmlExtensionLoaded()) {
            $this->xmlDom->documentElement->setAttribute($string, $name);
        }
    }

    public
    function getXmlText()
    {

        $xmlText = $this->getXmlDom()->saveHTML($this->getXmlDom()->ownerDocument);
        // Delete doctype (for svg optimization)
        // php has only doctype manipulation for HTML
        $xmlText = preg_replace('/^<!DOCTYPE.+?>/', '', $xmlText);
        return trim($xmlText);


    }

    /**
     * @return bool
     */
    public
    function isXmlExtensionLoaded()
    {
        // https://www.php.net/manual/en/dom.requirements.php
        return extension_loaded("libxml");
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
        $nodes = $this->xpath("//*[namespace-uri()='$namespaceUri']");
        foreach ($nodes as $node) {
            /** @var DOMElement $node */
            $node->parentNode->removeChild($node);
        }

        $nodes = $this->xpath("//@*[namespace-uri()='$namespaceUri']");
        foreach ($nodes as $node) {
            /** @var DOMAttr $node */
            /** @var DOMElement $DOMNode */
            $DOMNode = $node->parentNode;
            $DOMNode->removeAttributeNode($node);
        }

        //Node namespace can be select only from the document
        $xpath = new DOMXPath($this->getXmlDom());
        $DOMNodeList = $xpath->query("namespace::*", $this->getXmlDom()->ownerDocument);
        foreach ($DOMNodeList as $node) {
            $namespaceURI = $node->namespaceURI;
            if ($namespaceURI == $namespaceUri) {
                $parentNode = $node->parentNode;
                $parentNode->removeAttributeNS($namespaceUri, $node->localName);
            }
        }


    }

    public
    function getDocNamespaces()
    {
        $xpath = new DOMXPath($this->getXmlDom());
        // `namespace::*` means selects all the namespace attribute of the context node
        // namespace is an axes
        // See https://www.w3.org/TR/1999/REC-xpath-19991116/#axes
        // the namespace axis contains the namespace nodes of the context node; the axis will be empty unless the context node is an element
        $DOMNodeList = $xpath->query('namespace::*', $this->getXmlDom()->ownerDocument);
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
     * @param string $defaultNamespace
     * @return DOMNodeList|false
     */
    public
    function xpath($query)
    {
        $xpath = new DOMXPath($this->getXmlDom());
        foreach ($this->getDocNamespaces() as $prefix => $namespaceUri) {
            /**
             * You can't register an empty prefix
             * Default namespace (without a prefix) can only be accessed by the local-name() and namespace-uri() attributes.
             */
            if (!empty($prefix)) {
                $result = $xpath->registerNamespace($prefix, $namespaceUri);
                if (!$result) {
                    LogUtility::msg("Not able to register the prefix ($prefix) for the namespace uri ($namespaceUri)");
                }
            }
        }

        return $xpath->query($query);

    }


    public
    function removeRootAttribute($attribute)
    {

        // This function does not work
        // $result = $this->getXmlDom()->documentElement->removeAttribute($attribute);

        for ($i = 0; $i < $this->getXmlDom()->documentElement->attributes->length; $i++) {
            if ($this->getXmlDom()->documentElement->attributes[$i]->name == $attribute) {
                $result = $this->getXmlDom()->documentElement->removeAttributeNode($this->getXmlDom()->documentElement->attributes[$i]);
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
        for ($i = 0; $i < $this->getXmlDom()->documentElement->childNodes->length; $i++) {
            $childNode = &$this->getXmlDom()->documentElement->childNodes[$i];
            if ($childNode->nodeName == $nodeName) {
                $result = $this->getXmlDom()->documentElement->removeChild($childNode);
                if ($result === false) {
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


}
