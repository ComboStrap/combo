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
use Exception;
use LibXMLError;
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
            // & is in raw url and when in a value gives an error
            // https://www.php.net/manual/en/function.htmlspecialchars.php
            // because we use it only in src attribute, we make the switch here
            if (strpos($text, "&amp;") === false) {
                $text = str_replace("&", "&amp;", $text);
            }

            /**
             * Because the load does handle HTML5tag as error
             * (ie section for instance)
             * We take over the errors and handle them after the below load
             */
            /** @noinspection PhpComposerExtensionStubsInspection */
            libxml_use_internal_errors(TRUE);

            /**
             * Loading
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
                if ($error->message != "Tag section invalid\n") {
                    throw new \RuntimeException("Error while loading HTML: " . $error->message);
                }

            }
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
