<?php


namespace ComboStrap;


use DOMDocument;
use DOMElement;
use DOMNode;
use Exception;

/**
 * Class XmlUtility
 * @package ComboStrap
 * XML Utility
 *
 *
 */
class XmlUtility
{
    const OPEN = "open";
    const CLOSED = "closed";
    const NORMAL = "normal";

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
     */
    const DOM_EXTENSION = "dom";


    /**
     * @param $attName
     * @param $newAttValue
     * @param DOMElement $xml
     */
    public static function setAttribute($attName, $newAttValue, $xml)
    {
        $attValue = (string)$xml[$attName];
        if ($attValue != "") {
            $xml[$attName] = $newAttValue;
        } else {
            $xml->setAttribute($attName, $newAttValue);
        }
    }

    /**
     * Delete the class value from the class attributes
     * @param $classValue
     * @param SimpleXMLElement $xml
     */
    public static function deleteClass($classValue, SimpleXMLElement $xml)
    {
        $class = (string)$xml["class"];
        if ($class != "") {
            $classValues = explode(" ", $class);
            if (($key = array_search($classValue, $classValues)) !== false) {
                unset($classValues[$key]);
            }
            $xml["class"] = implode(" ", $classValues);
        }
    }


    /**
     * Get a Simple XMl Element and returns it without the XML header (ie as HTML node)
     * @param SimpleXMLElement $linkDom
     * @return false|string
     */
    public static function asHtml(SimpleXMLElement $linkDom)
    {

        $domXml = dom_import_simplexml($linkDom);
        /**
         * ownerDocument returned the DOMElement
         */
        return $domXml->ownerDocument->saveXML($domXml->ownerDocument->documentElement);
    }

    /**
     * Check of the text is a valid XML
     * @param $text
     * @return bool
     */
    public static function isXml($text)
    {


        if (extension_loaded(self::DOM_EXTENSION)) {

            $valid = true;

            /**
             * Temporary No error reporting
             * We see warning in the log
             */
            $oldLevel = error_reporting(E_ERROR);
            try {
                XmlUtility::load($text);
            } catch (\Exception $e) {
                $valid = false;
            }
            /**
             * Error reporting back
             */
            error_reporting($oldLevel);
            return $valid;

        } else {
            LogUtility::msg("The SimpleXml base php library was not detected on your custom installation. Check the following " . PluginUtility::getUrl($canonical, "page") . " on how to solve this problem.", LogUtility::LVL_MSG_ERROR, $canonical);
            return false;
        }
    }

    /**
     * Return a formatted HTML
     * @param $text
     * @return mixed
     * DOMDocument supports formatted XML while SimpleXMLElement does not.
     * @throws \Exception if empty
     */
    public static function format($text)
    {
        if (empty($text)) {
            throw new \Exception("The text should not be empty");
        }
        $doc = new DOMDocument();
        $doc->loadXML($text);
        $doc->normalize();
        $doc->formatOutput = true;
        // Type doc can also be reach with $domNode->ownerDocument
        return $doc->saveXML();


    }

    /**
     * @param $text
     * @return mixed
     */
    public static function normalize($text)
    {
        if (empty($text)) {
            throw new \RuntimeException("The text should not be empty");
        }
        $doc = new DOMDocument();
        $text = XmlUtility::preprocessText($text);
        $doc->loadXML($text);
        $doc->normalize();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        // Type doc can also be reach with $domNode->ownerDocument
        return $doc->saveXML($doc->documentElement) . DOKU_LF;
    }

    /**
     * note: Option for the loading of {@link XmlDocument}
     * have also this option
     *
     * @param $text
     * @return string|string[]
     */
    public static function extractTextWithoutCdata($text)
    {
        $text = str_replace("/*<![CDATA[*/", "", $text);
        $text = str_replace("/*!]]>*/", "", $text);
        $text = str_replace("\/", "/", $text);
        return $text;
    }

    public static function preprocessText($text)
    {
        $text = preg_replace("/\r\n\s*\r\n/", "\r\n", $text);
        $text = preg_replace("/\n\s*\n/", "\n", $text);
        return $text;
    }

    public static function setClass($stylingClass, $path)
    {

    }

    /**
     * @noinspection PhpComposerExtensionStubsInspection
     * @param DOMNode $leftNode
     * @param DOMNode $rightNode
     * Tip: To get the text of a node:
     * $leftNode->ownerDocument->saveHTML($leftNode)
     * @param $error
     */
    public static function diffNode(DOMNode $leftNode, DOMNode $rightNode, &$error)
    {

        $leftNodeName = $leftNode->localName;
        $rightNodeName = $rightNode->localName;
        if ($leftNodeName != $rightNodeName) {
            $error .= "The node (" . $rightNode->getNodePath() . ") are different (" . $leftNodeName . "," . $rightNodeName . ")\n";
        }
        if ($leftNode->hasAttributes()) {
            $leftAttributesLength = $leftNode->attributes->length;
            $rightNodeAttributes = $rightNode->attributes;
            if ($rightNodeAttributes == null) {
                $error .= "The node (" . $rightNode->getNodePath() . ") have no attributes while the left node has.\n";
            } else {

                /**
                 * Collect the attributes by name
                 */
                $leftAttributes = array();
                for ($i = 0; $i < $leftAttributesLength; $i++) {
                    $leftAtt = $leftNode->attributes->item($i);
                    $leftAttributes[$leftAtt->nodeName] = $leftAtt;
                }
                ksort($leftAttributes);
                $rightAttributes = array();
                for ($i = 0; $i < $rightNodeAttributes->length; $i++) {
                    $rightAtt = $rightNodeAttributes->item($i);
                    $rightAttributes[$rightAtt->nodeName] = $rightAtt;
                }

                foreach ($leftAttributes as $leftAttName => $leftAtt) {
                    /** @var \DOMAttr $leftAtt */
                    $rightAtt = $rightAttributes[$leftAttName];
                    if ($rightAtt == null) {
                        $error .= "The attribute (" . $leftAtt->getNodePath() . ") does not exist on the right side\n";
                    } else {
                        unset($rightAttributes[$leftAttName]);
                        $leftAttValue = $leftAtt->nodeValue;
                        $rightAttValue = $rightAtt->nodeValue;
                        if ($leftAttValue != $rightAttValue) {
                            if ($leftAtt->name === "class") {
                                $leftClasses = preg_split("/\s/", $leftAttValue);
                                $rightClasses = preg_split("/\s/", $rightAttValue);
                                foreach ($leftClasses as $leftClass) {
                                    if (!in_array($leftClass, $rightClasses)) {
                                        $error .= "The left class attribute (" . $leftAtt->getNodePath() . ") has the value (" . $leftClass . ") that is not present in the right node)\n";
                                    } else {
                                        // Delete the value
                                        $key = array_search($leftClass, $rightClasses);
                                        unset($rightClasses[$key]);
                                    }
                                }
                                foreach ($rightClasses as $rightClass) {
                                    $error .= "The right class attribute (" . $leftAtt->getNodePath() . ") has the value (" . $rightClass . ") that is not present in the left node)\n";
                                }
                            } else {
                                $error .= "The attribute (" . $leftAtt->getNodePath() . ") have different values (" . $leftAttValue . "," . $rightAttValue . ")\n";
                            }
                        }
                    }
                }

                ksort($rightAttributes);
                foreach ($rightAttributes as $rightAttName => $rightAtt) {
                    $error .= "The attribute (" . $rightAttName . ") of the node (" . $rightAtt->getNodePath() . ") does not exist on the left side\n";
                }
            }
        }
        if ($leftNode->nodeName == "#text") {
            $leftNodeValue = trim($leftNode->nodeValue);
            $rightNodeValue = trim($rightNode->nodeValue);
            if ($leftNodeValue != $rightNodeValue) {
                $error .= "The node (" . $rightNode->getNodePath() . ") have different values (" . $leftNodeValue . "," . $rightNodeValue . ")\n";
            }
        }
        /**
         * Sub
         */
        if ($leftNode->hasChildNodes()) {

            $rightChildNodes = $rightNode->childNodes;
            $rightChildNodesCount = $rightChildNodes->length;
            if ($rightChildNodes == null || $rightChildNodesCount == 0) {
                $firstNode = $leftNode->childNodes->item(0);
                $firstNodeName = $firstNode->nodeName;
                $firstValue = $firstNode->nodeValue;
                $error .= "The left node (" . $leftNode->getNodePath() . ") have child nodes while the right has not (First Left Node: $firstNodeName, value: $firstValue) \n";
            } else {
                $leftChildNodeCount = $leftNode->childNodes->length;
                $leftChildIndex = 0;
                $rightChildIndex = 0;
                while ($leftChildIndex < $leftChildNodeCount && $rightChildIndex < $rightChildNodesCount) {

                    $leftChildNode = $leftNode->childNodes->item($leftChildIndex);
                    if ($leftChildNode->nodeName == "#text") {
                        $leftChildNodeValue = trim($leftChildNode->nodeValue);
                        if (empty(trim($leftChildNodeValue))) {
                            $leftChildIndex++;
                            $leftChildNode = $leftNode->childNodes->item($leftChildIndex);
                        }
                    }

                    $rightChildNode = $rightChildNodes->item($rightChildIndex);
                    if ($rightChildNode->nodeName == "#text") {
                        $leftChildNodeValue = trim($rightChildNode->nodeValue);
                        if (empty(trim($leftChildNodeValue))) {
                            $rightChildIndex++;
                            $rightChildNode = $rightChildNodes->item($rightChildIndex);
                        }
                    }

                    if ($rightChildNode != null) {
                        if ($leftChildNode != null) {
                            self::diffNode($leftChildNode, $rightChildNode, $error);
                        } else {
                            $error .= "The right node (" . $rightChildNode->getNodePath() . ") does not exist in the left document.\n";
                        }
                    } else {
                        if ($leftChildNode != null) {
                            $error .= "The left node (" . $leftChildNode->getNodePath() . ") does not exist in the right document.\n";
                        }
                    }

                    /**
                     * 0 based index
                     */
                    $leftChildIndex++;
                    $rightChildIndex++;
                }
            }
        }

    }

    /**
     * Return a diff
     * @param string $left
     * @param string $right
     * @return string
     * DOMDocument supports formatted XML while SimpleXMLElement does not.
     * @noinspection PhpComposerExtensionStubsInspection
     */
    public static function diffMarkup($left, $right)
    {
        if (empty($right)) {
            throw new \RuntimeException("The left text should not be empty");
        }
        if (empty($left)) {
            throw new \RuntimeException("The left text should not be empty");
        }

        $leftDocument = self::load($left);
        $rightDocument = self::load($right);

        $error = "";
        XmlUtility::diffNode($leftDocument, $rightDocument, $error);

        return $error;

    }

    /**
     * Laad an XML, Svg
     * @param string $markup
     * @return DOMDocument
     * If you want to load an HTML, use {@link HtmlUtility::load()}
     */
    private static function load($markup)
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        try {
            $document->load($markup);
        } catch (Exception $exception) {
            throw new \RuntimeException($exception);
        }
        return $document;
    }


}
