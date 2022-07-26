<?php

namespace ComboStrap;


use DOMDocument;
use Exception;
use LibXMLError;


/**
 * Class HtmlUtility
 * Static HTML utility
 *
 * On HTML as string, if you want to work on HTML as XML, see the {@link XmlSystems} class
 *
 * @package ComboStrap
 *
 * This class is based on {@link XmlDocument}
 *
 */
class XhtmlUtility
{


    /**
     * Return a diff
     * @param string $left
     * @param string $right
     * @param bool $xhtml
     * @param null $excludedAttributes
     * @return string
     * DOMDocument supports formatted XML while SimpleXMLElement does not.
     * @throws ExceptionBadSyntax
     */
    public static function diffMarkup(string $left, string $right, $xhtml = true, $excludedAttributes = null): string
    {
        if (empty($right)) {
            throw new \RuntimeException("The right text should not be empty");
        }
        if (empty($left)) {
            throw new \RuntimeException("The left text should not be empty");
        }
        $loading = XmlDocument::XML_TYPE;
        if (!$xhtml) {
            $loading = XmlDocument::HTML_TYPE;
        }
        $leftDocument = (new XmlDocument($left, $loading))->getDomDocument();
        $rightDocument = (new XmlDocument($right, $loading))->getDomDocument();

        $error = "";
        XmlSystems::diffNode(
            $leftDocument,
            $rightDocument,
            $error,
            $excludedAttributes
        );

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
     * @throws ExceptionBadSyntax
     */
    public static function normalize($htmlText)
    {
        if (empty($htmlText)) {
            throw new \RuntimeException("The text should not be empty");
        }
        $xmlDoc = new XmlDocument($htmlText, XmlDocument::HTML_TYPE);
        return $xmlDoc->toXmlNormalized();
    }




}
