<?php

namespace ComboStrap;

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

use DOMDocument;
use Exception;
use LibXMLError;

require_once(__DIR__ . '/../class/XmlUtility.php');

/**
 * Class HtmlUtility
 * On HTML as string, if you want to work on HTML as XML, see the {@link XmlUtility} class
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
            "Tag bdi invalid\n",
            "Tag path invalid\n", // svg
            "Tag svg invalid\n", // svg
            "Unexpected end tag : a\n", // when the document is only a anchor
            "Unexpected end tag : p\n", // when the document is only a p
            "Unexpected end tag : button\n" // // when the document is only a button
        ];


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
     * @param $text
     * @return int the number of lines estimated
     */
    public static function countLines($text)
    {
        return count(preg_split("/<\/p>|<\/h[1-9]{1}>|<br|<\/tr>|<\/li>|<hr>|<\/pre>/", $text)) - 1;
    }

    private static function &load($text)
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        try {


            /**
             * Because the load does handle HTML5tag as error
             * (ie section for instance)
             * We take over the errors and handle them after the below load
             *
             * https://www.php.net/manual/en/function.libxml-use-internal-errors.php
             *
             * @noinspection PhpComposerExtensionStubsInspection
             */
            libxml_use_internal_errors(true);

            /**
             * Loading
             * Unlike loading XML, HTML does not have to be well-formed to load.
             * While malformed HTML should load successfully, this function may generate E_WARNING errors
             */
            $document->loadHTML($text);

            /**
             * Error
             */
            /** @noinspection PhpComposerExtensionStubsInspection */
            $errors = libxml_get_errors();

            foreach ($errors as $error) {

                /* @var $error LibXMLError
                 * @noinspection PhpComposerExtensionStubsInspection
                 *
                 * Section is an html5 tag (and is invalid for libxml)
                 */
                if (!in_array($error->message, HtmlUtility::KNOWN_LOADING_ERRORS)) {
                    throw new \RuntimeException("Error while loading HTML: " . $error->message.". Loaded text: ".$text);
                }

            }

            /** @noinspection PhpComposerExtensionStubsInspection */
            libxml_clear_errors();

        } catch (Exception $exception) {
            if (strpos($exception->getMessage(), "htmlParseEntityRef: expecting ';' in Entity") !== false) {
                throw new \RuntimeException("You forgot to call htmlentities in src, url ? Somewhere. Error: " . $exception->getMessage());
            } else {
                throw new \RuntimeException($exception);
            }
        }
        return $document;
    }
}
