<?php


namespace ComboStrap;


use DOMDocument;
use DOMElement;
use SimpleXMLElement;

/**
 * Class XmlUtility
 * @package ComboStrap
 * SimpleXML Utility
 *
 *
 */
class XmlUtility
{
    const OPEN = "open";
    const CLOSED = "closed";
    const NORMAL = "normal";

    const SIMPLE_XML_EXTENSION = "simplexml";


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


        if (extension_loaded(self::SIMPLE_XML_EXTENSION)) {

            $valid = true;

            /**
             * Temporary No error reporting
             * We see warning in the log
             */
            $oldLevel = error_reporting(E_ERROR);
            try {
                /** @noinspection PhpComposerExtensionStubsInspection */
                new SimpleXMLElement($text);
            } catch (\Exception $e) {
                $valid = false;
            }
            /**
             * Error reporting back
             */
            error_reporting($oldLevel);
            return $valid;

        } else {
            LogUtility::msg("The SimpleXml base php library was not detected on your custom installation. Check the following ".PluginUtility::getUrl($canonical,"page")." on how to solve this problem.",LogUtility::LVL_MSG_ERROR, $canonical);
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
     * note: Option for the loading of {@link XmlFile}
     * have also this option
     *
     * @param $text
     * @return string|string[]
     */
    public static function extractTextWithoutCdata($text)
    {
        $text = str_replace("/*<![CDATA[*/","",$text);
        $text = str_replace("/*!]]>*/","",$text);
        $text = str_replace("\/","/",$text);
        return $text;
    }

    public static function preprocessText($text)
    {
        $text = preg_replace("/\r\n\s*\r\n/","\r\n",$text);
        $text = preg_replace("/\n\s*\n/","\n",$text);
        return $text;
    }

    public static function setClass($stylingClass, $path)
    {

    }


}
