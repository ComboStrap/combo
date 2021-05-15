<?php

namespace ComboStrap;


use DOMDocument;
use Exception;
use LibXMLError;

require_once(__DIR__ . '/XmlUtility.php');

/**
 * Class HtmlUtility
 * On HTML as string, if you want to work on HTML as XML, see the {@link XmlUtility} class
 * @package ComboStrap
 *
 *
 */
class HtmlUtility
{

    /**
     * The error that the HTML loading
     * may returns
     */
    const KNOWN_LOADING_ERRORS =
        [
            "Tag section invalid\n", // section is HTML5 tag
            "Tag footer invalid\n", // footer is HTML5 tag
            "error parsing attribute name\n", // name is an HTML5 attribute
            "Unexpected end tag : blockquote\n", // name is an HTML5 attribute
            "Tag bdi invalid\n",
            "Tag path invalid\n", // svg
            "Tag svg invalid\n", // svg
            "Unexpected end tag : a\n", // when the document is only a anchor
            "Unexpected end tag : p\n", // when the document is only a p
            "Unexpected end tag : button\n" // // when the document is only a button

        ];


    /**
     * Format
     * @param $text
     * @return mixed
     */
    public static function normalize($text)
    {
        return HtmlUtility::format($text);
    }

    /**
     * Return a formatted HTML
     * @param $text
     * @return mixed
     * DOMDocument supports formatted XML while SimpleXMLElement does not.
     */
    public static function format($text)
    {

        $xmlDocument = new XmlDocument($text, XmlDocument::HTML_TYPE);
        $doc = $xmlDocument->getXmlDom();

        // Preserve white space = false is important for output format
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;

        $doc->normalize();

        /**
         * If the text was a list
         * of sibling text without parent
         * We may get a body
         */
        $body = $doc->getElementsByTagName("body");
        if ($body->length != 0) {
            $DOMNodeList = $body->item(0)->childNodes;
            $output = "";
            foreach ($DOMNodeList as $value) {
                $output .= $doc->saveXML($value) . DOKU_LF;
            }
        } else {
            $output = $doc->saveHTML($doc->ownerDocument);
        }


        // Type doc can also be reach with $domNode->ownerDocument
        return $output;


    }

    /**
     * Return a diff
     * @param string $left
     * @param string $right
     * @return string
     * DOMDocument supports formatted XML while SimpleXMLElement does not.
     * @noinspection PhpComposerExtensionStubsInspection
     */
    public static function diffMarkup($left, $right, $xhtml = true)
    {
        if (empty($right)) {
            throw new \RuntimeException("The left text should not be empty");
        }
        if (empty($left)) {
            throw new \RuntimeException("The left text should not be empty");
        }

        $leftDocument = HtmlUtility::load($left, $xhtml);
        $rightDocument = HtmlUtility::load($right, $xhtml);

        $error = "";
        XmlUtility::diffNode($leftDocument, $rightDocument, $error);

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
     * @param $text
     * @param bool $xhtml - does HTML must be a valid XML
     * @return DOMDocument
     */
    private static function &load($text, $xhtml = true)
    {
        $xmlDocument = new XmlDocument($text, XmlDocument::HTML_TYPE);
        return $xmlDocument->getXmlDom();
    }
}
