<?php


namespace ComboStrap\Xml;


use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotEquals;
use ComboStrap\ExceptionRuntime;
use ComboStrap\Html;
use ComboStrap\TagAttribute\StyleAttribute;
use ComboStrap\Web\Url;
use ComboStrap\Xml\XmlDocument;
use ComboStrap\Xml\XmlElement;
use DOMDocument;
use DOMElement;
use DOMNode;
use Exception;

/**
 *
 * @package ComboStrap
 * Static function around the {@link XmlDocument}
 *
 *
 */
class XmlSystems
{
    const OPEN = "open";
    const CLOSED = "closed";
    const NORMAL = "normal";


    /**
     * Get a Simple XMl Element and returns it without the XML header (ie as HTML node)
     * @param DOMDocument $linkDom
     * @return false|string
     */
    public static function asHtml($linkDom)
    {

        /**
         * ownerDocument returned the DOMElement
         */
        return $linkDom->ownerDocument->saveXML($linkDom->ownerDocument->documentElement);
    }

    /**
     * Check of the text is a valid XML
     * @param $text
     * @return bool
     */
    public static function isXml($text)
    {

        $valid = true;
        try {
            new XmlDocument($text);
        } catch (\Exception $e) {
            $valid = false;
        }
        return $valid;


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
     * @return string
     * @throws ExceptionBadSyntax
     */
    public static function normalize($text)
    {
        if (empty($text)) {
            throw new ExceptionBadSyntax("The text should not be empty");
        }
        $xmlDoc = new XmlDocument($text, XmlDocument::XML_TYPE);
        return $xmlDoc->toXmlNormalized();
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

    }


    /**
     * @param DOMNode $leftNode
     * @param DOMNode $rightNode
     * Tip: To get the text of a node:
     * $leftNode->ownerDocument->saveHTML($leftNode)
     * @param $error
     * @param string[]|null $excludedAttributes - the value of this attributes will not be checked
     */
    public static function diffNode(DOMNode $leftNode, DOMNode $rightNode, &$error, array $excludedAttributes = null)
    {

        if ($excludedAttributes === null) {
            $excludedAttributes = [];
        }
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
                    $rightAtt = $rightAttributes[$leftAttName] ?? null;
                    if ($rightAtt == null) {
                        if (!in_array($leftAttName, $excludedAttributes)) {
                            $error .= "The attribute (" . $leftAtt->getNodePath() . ") does not exist on the right side\n";
                        }
                        continue;
                    }

                    unset($rightAttributes[$leftAttName]);

                    /**
                     * Value check
                     */
                    if (in_array($leftAttName, $excludedAttributes)) {
                        continue;
                    }
                    $leftAttValue = $leftAtt->nodeValue;
                    $rightAttValue = $rightAtt->nodeValue;
                    if ($leftAttValue !== $rightAttValue) {
                        switch ($leftAtt->name) {
                            case "class":
                                $error .= Html::getDiffBetweenValuesSeparatedByBlank($leftAttValue, $rightAttValue, "left ,{$leftAtt->getNodePath()}", "right, {$leftAtt->getNodePath()}");
                                break;
                            case "srcset":
                            case "data-srcset":
                                try {
                                    Html::getDiffBetweenSrcSet($leftAttValue, $rightAttValue);
                                } catch (ExceptionBadSyntax|ExceptionNotEquals $e) {
                                    $error .= $e->getMessage();
                                }
                                break;
                            case "src":
                            case "data-src":
                            case "href":
                            case "action": // form
                                try {
                                    $leftUrl = Url::createFromString($leftAttValue);
                                    try {
                                        $rightUrl = Url::createFromString($rightAttValue);
                                        try {
                                            $leftUrl->equals($rightUrl);
                                        } catch (ExceptionNotEquals $e) {
                                            $error .= "The attribute (" . $rightAtt->getNodePath() . ") has different values. Error:{$e->getMessage()}\n";
                                        }
                                    } catch (ExceptionBadSyntax|ExceptionBadArgument $e) {
                                        $error .= "The attribute (" . $leftAtt->getNodePath() . ") has different values (" . $leftAttValue . "," . $rightAttValue . ") and the right value is not an URL. Error:{$e->getMessage()}\n";
                                    }
                                } catch (ExceptionBadSyntax|ExceptionBadArgument $e) {
                                    $error .= "The attribute (" . $leftAtt->getNodePath() . ") has different values (" . $leftAttValue . "," . $rightAttValue . ") and the left value is not an URL. Error:{$e->getMessage()}\n";
                                }
                                break;
                            case "style":
                                try {
                                    StyleAttribute::stringEquals($leftAttValue, $rightAttValue);
                                } catch (ExceptionNotEquals $e) {
                                    $error .= "The style attribute (" . $leftAtt->getNodePath() . ") has different values (" . $leftAttValue . "," . $rightAttValue . "). Error:{$e->getMessage()}\n";
                                }
                                break;
                            default:
                                $error .= "The attribute (" . $leftAtt->getNodePath() . ") have different values (" . $leftAttValue . "," . $rightAttValue . ")\n";
                                break;
                        }
                    }

                }

                ksort($rightAttributes);
                foreach ($rightAttributes as $rightAttName => $rightAtt) {
                    if (!in_array($rightAttName, $excludedAttributes)) {
                        $error .= "The attribute (" . $rightAttName . ") of the node (" . $rightAtt->getNodePath() . ") does not exist on the left side\n";
                    }
                }
            }
        } else {
            if ($rightNode->hasAttributes()) {
                for ($i = 0; $i < $rightNode->attributes->length; $i++) {
                    /** @var \DOMAttr $rightAtt */
                    $rightAtt = $rightNode->attributes->item($i);
                    $error .= "The attribute (" . $rightAtt->getNodePath() . ") does not exist on the left side\n";
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
                            self::diffNode($leftChildNode, $rightChildNode, $error, $excludedAttributes);
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
     * @throws ExceptionCompile
     */
    public
    static function diffMarkup(string $left, string $right): string
    {
        if (empty($right)) {
            throw new \RuntimeException("The right text should not be empty");
        }
        $leftDocument = new XmlDocument($left);

        if (empty($left)) {
            throw new \RuntimeException("The left text should not be empty");
        }
        $rightDocument = new XmlDocument($right);

        return $leftDocument->diff($rightDocument);

    }

    public static function deleteAllElementsByName(string $elementName, XmlDocument $xmlDocument)
    {
        $xpathQuery = "//*[local-name()='$elementName']";
        try {
            $svgElement = $xmlDocument->xpath($xpathQuery);
        } catch (ExceptionBadSyntax $e) {
            // should not happen on prod
            throw new ExceptionRuntime("xpath query error ($xpathQuery");
        }
        for ($i = 0; $i < $svgElement->length; $i++) {
            $nodeElement = XmlElement::create($svgElement[$i], $xmlDocument);
            $nodeElement->remove();
        }
    }


}
