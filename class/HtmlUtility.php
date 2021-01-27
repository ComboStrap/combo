<?php
/**
 * Copyright (c) 2020. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;


use DOMDocument;
use DOMNode;
use DOMNodeList;
use http\Exception\RuntimeException;
use SimpleXMLElement;

require_once(__DIR__ . '/../class/PluginUtility.php');
require_once(__DIR__ . '/../class/XmlUtility.php');

/**
 * Class HtmlUtility
 * @package ComboStrap
 * On HTML as string, if you want to work on HTML as XML, see the {@link XmlUtility} class
 */
class HtmlUtility
{

    /**
     * @param $html - An Html
     * @param $attributeName
     * @param $attributeValue
     * @return bool|false|string
     */
    public static function addAttributeValue($html, $attributeName, $attributeValue)
    {
        try {
            /** @noinspection PhpComposerExtensionStubsInspection */
            /** @noinspection PhpUndefinedVariableInspection */
            $domElement = new SimpleXMLElement ($html);
        } catch (\Exception $e) {
            LogUtility::msg("The HTML ($html) is not a valid HTML element. The error returned is $e", LogUtility::LVL_MSG_ERROR);
            return false;
        }
        XmlUtility::addAttributeValue($attributeName, $attributeValue, $domElement);

        return XmlUtility::asHtml($domElement);
    }

    /**
     * @param $html - the html of an element
     * @param $classValue - the class to delete
     * @return bool|false|string
     */
    public static function deleteClassValue($html, $classValue)
    {
        try {
            /** @noinspection PhpComposerExtensionStubsInspection */
            /** @noinspection PhpUndefinedVariableInspection */
            $domElement = new SimpleXMLElement ($html);
        } catch (\Exception $e) {
            LogUtility::msg("The HTML ($html) is not a valid HTML element. The error returned is $e", LogUtility::LVL_MSG_ERROR);
            return false;
        }
        XmlUtility::deleteClass($classValue, $domElement);

        return XmlUtility::asHtml($domElement);

    }

    /**
     * Return a formatted HTML that does take into account the {@link DOKU_LF}
     * @param $text
     * @return mixed
     */
    public static function normalize($text)
    {
        $text = str_replace(DOKU_LF, "", $text);
        return self::format($text);
    }

    /**
     * Return a formatted HTML
     * @param $text
     * @return mixed
     * DOMDocument supports formatted XML while SimpleXMLElement does not.
     */
    public static function format($text)
    {
        if (empty($text)) {
            throw new \RuntimeException("The text should not be empty");
        }
        $doc = new DOMDocument();
        /**
         * The @ is to suppress the error because of HTML5 tag such as footer
         * https://stackoverflow.com/questions/6090667/php-domdocument-errors-warnings-on-html5-tags
         */
        @$doc->loadHTML($text);
        $doc->normalize();
        $doc->formatOutput = true;
        $DOMNodeList = $doc->getElementsByTagName("body")->item(0)->childNodes;
        $output = "";
        foreach ($DOMNodeList as $value) {
            $output .= $doc->saveXML($value) . DOKU_LF;
        }
        // Type doc can also be reach with $domNode->ownerDocument
        return $output;


    }

    /**
     * Return a diff
     * @param $left
     * @param $right
     * @return mixed
     * DOMDocument supports formatted XML while SimpleXMLElement does not.
     * @noinspection PhpComposerExtensionStubsInspection
     */
    public static function diff($left, $right)
    {
        if (empty($right)) {
            throw new \RuntimeException("The left text should not be empty");
        }
        if (empty($left)) {
            throw new \RuntimeException("The left text should not be empty");
        }
        /**
         * The @ is to suppress the error because of HTML5 tag such as footer
         * https://stackoverflow.com/questions/6090667/php-domdocument-errors-warnings-on-html5-tags
         */
        $leftDocument = new DOMDocument();
        $leftDocument->loadHTML($left);
        $rightDocument = new DOMDocument();
        $rightDocument->loadHTML($right);

        $error = "";
        self::diffNode($leftDocument, $rightDocument, $error);

        return $error;


    }

    /**
     * @param $text
     * @return int the number of lines estimated
     */
    public static function countLines($text)
    {
        return count(preg_split("/<\/p>|<\/h[1-9]{1}>|<br|<\/tr>|<\/li>|<hr>|<\/pre>/", $text)) - 1;
    }

    /**
     *
     * Tip: To get the text of a node:
     * $leftNode->ownerDocument->saveHTML($leftNode)
     */
    /** @noinspection PhpComposerExtensionStubsInspection */
    private static function diffNode(DOMNode $leftNode, DOMNode $rightNode, &$error)
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
                $rightAttributesLength = $rightNodeAttributes->length;
                if ($leftAttributesLength != $rightAttributesLength) {
                    $error .= "The node (" . $rightNode->getNodePath() . ") have different number of attributes (" . $leftAttributesLength . "," . $rightAttributesLength . ")\n";
                }
                if ($leftAttributesLength != 0) {
                    for ($i = 0; $i < $leftAttributesLength; $i++) {
                        $leftAtt = $leftNode->attributes->item($i);
                        $rightAtt = $rightNodeAttributes->item($i);
                        $leftAttName = $leftAtt->nodeName;
                        $rightAttName = $rightAtt->nodeName;
                        if ($leftAttName != $rightAttName) {
                            $error .= "The attribute (" . $leftAttName . ") of the node (" . $rightNode->getNodePath() . ") have different name than the right (" . $rightAttName . ")\n";
                        }
                        $leftAttValue = $leftAtt->nodeValue;
                        $rightAttValue = $rightAtt->nodeValue;
                        if ($leftAttValue != $rightAttValue) {
                            $error .= "The attribute (" . $leftAttName . ") of the node (" . $rightNode->getNodePath() . ") have a different value (" . $leftAttValue . ") than the right (" . $rightAttValue . ")\n";
                        }
                    }
                }
            }
        }
        if ($leftNode->nodeName == "#text") {
            $leftNodeValue = trim($leftNode->nodeValue);
            $rightNodeValue = trim($rightNode->nodeValue);
            if ($leftNodeValue != $rightNodeValue) {
                $error .= "The node (" . $rightNode->getNodePath() . ") have different value (" . $leftNodeValue . "," . $rightNodeValue . ")\n";
            }
        }
        /**
         * Sub
         */
        if ($leftNode->hasChildNodes()) {

            $rightChildNodes = $rightNode->childNodes;
            $rightChildNodesCount = $rightChildNodes->length;
            if ($rightChildNodes == null || $rightChildNodesCount == 0) {
                $error .= "The left node (" . $leftNode->getNodePath() . ") have child nodes while the right has not.\n";
            } else {
                $leftChildNodeCount = $leftNode->childNodes->length;
                $leftChildIndex = 0;
                $rightChildIndex = 0;
                while ($leftChildIndex < $leftChildNodeCount && $rightChildIndex < $rightChildNodesCount) {
                    $leftChildIndex++;
                    $leftChildNode = $leftNode->childNodes->item($leftChildIndex);
                    if ($leftChildNode->nodeName == "#text") {
                        $leftChildNodeValue = trim($leftChildNode->nodeValue);
                        if (empty(trim($leftChildNodeValue))) {
                            $leftChildIndex++;
                            $leftChildNode = $leftNode->childNodes->item($leftChildIndex);
                        }
                    }

                    $rightChildIndex++;
                    $rightChildNode = $rightChildNodes->item($rightChildIndex);
                    if ($rightChildNode->nodeName == "#text") {
                        $leftChildNodeValue = trim($rightChildNode->nodeValue);
                        if (empty(trim($leftChildNodeValue))) {
                            $rightChildIndex++;
                            $rightChildNode = $rightChildNodes->item($rightChildIndex);
                        }
                    }

                    if ($rightChildNode != null) {
                        if ($leftChildNode!=null) {
                            self::diffNode($leftChildNode, $rightChildNode, $error);
                        } else {
                            $error .= "The right node (" . $rightChildNode->getNodePath() . ") does not exist in the left document.\n";
                        }
                    } else {
                        if ($leftChildNode!=null) {
                            $error .= "The left node (" . $leftChildNode->getNodePath() . ") does not exist in the right document.\n";
                        }
                    }
                }
            }
        }

    }
}
