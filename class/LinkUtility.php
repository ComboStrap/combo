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


use Doku_Renderer_metadata;
use Doku_Renderer_xhtml;
use syntax_plugin_combo_tooltip;

/**
 * Class LinkUtility
 * @package ComboStrap
 *
 */
class LinkUtility
{

    /**
     * Link pattern
     * Found in {@link \dokuwiki\Parsing\ParserMode\Internallink}
     */
    const LINK_PATTERN = "\[\[.*?\]\](?!\])";

    /**
     * Type of link
     */
    const TYPE_INTERWIKI = 'interwiki';
    const TYPE_WINDOWS_SHARE = 'windowsShare';
    const TYPE_EXTERNAL = 'external';
    const TYPE_EMAIL = 'email';
    const TYPE_LOCAL = 'local';
    const TYPE_INTERNAL = 'internal';

    const ATTRIBUTE_ID = 'id';
    const ATTRIBUTE_TITLE = 'title';
    const ATTRIBUTE_IMAGE = 'image';
    const ATTRIBUTE_TYPE = 'type';

    /**
     * Style to cancel the dokuwiki styling
     * Is a constant to be able to use it in the test
     * background is transparent, otherwise, you may see a rectangle with a link in button
     */
    const STYLE_VALUE = ";background-color:transparent;border-color:inherit;color:inherit;background-image:unset;padding:unset";


    /**
     * Parse the match of a syntax {@link DokuWiki_Syntax_Plugin} handle function
     * @param $match
     * @return string[] - an array with the attributes constant `ATTRIBUTE_xxxx` as key
     *
     * Code adapted from  {@link Doku_Handler::internallink()}
     */
    public static function getAttributes($match)
    {

        // Strip the opening and closing markup
        $link = preg_replace(array('/^\[\[/', '/\]\]$/u'), '', $match);

        // Split title from URL
        $link = explode('|', $link, 2);

        // Id
        $attributes[self::ATTRIBUTE_ID] = trim($link[0]);

        // Text or image
        if (!isset($link[1])) {
            $attributes[self::ATTRIBUTE_TITLE] = null;
        } else {
            // An image in the title
            if (preg_match('/^\{\{[^\}]+\}\}$/', $link[1])) {
                // If the title is an image, convert it to an array containing the image details
                $attributes[self::ATTRIBUTE_IMAGE] = Doku_Handler_Parse_Media($link[1]);
            } else {
                $attributes[self::ATTRIBUTE_TITLE] = $link[1];
            }
        }

        // Type
        $attributes[self::ATTRIBUTE_TYPE] = self::getType($attributes[self::ATTRIBUTE_ID]);
        return $attributes;

    }

    /**
     * @param Doku_Renderer_xhtml $renderer
     * @param array $attributes
     * @param $lowLink
     * @return mixed
     */
    public static function renderLinkDefault($renderer, array $attributes, $lowLink = false)
    {
        $id = $attributes[self::ATTRIBUTE_ID];
        $title = $attributes[self::ATTRIBUTE_TITLE];
        $type = $attributes[self::ATTRIBUTE_TYPE];

        /**
         * To allow {@link \syntax_plugin_combo_pipeline}
         */
        if (strpos($title, "<pipeline>") !== false) {
            $title = str_replace("<pipeline>", "", $title);
            $title = str_replace("</pipeline>", "", $title);
            $title = PipelineUtility::execute($title);
        }

        // Always return the string
        $returnOnly = true;

        // The HTML created by DokuWiki
        switch ($type) {
            case self::TYPE_INTERWIKI:
                // Interwiki
                $interWiki = explode('>', $id, 2);
                $wikiName = strtolower($interWiki[0]);
                $wikiUri = $interWiki[1];
                $html = $renderer->interwikilink($id, $title, $wikiName, $wikiUri, $returnOnly);
                break;
            case self::TYPE_WINDOWS_SHARE:
                $html = $renderer->windowssharelink($id, $title);
                break;
            case self::TYPE_EXTERNAL:
                $html = $renderer->externallink($id, $title, $returnOnly);
                break;
            case self::TYPE_EMAIL:
                // E-Mail (pattern above is defined in inc/mail.php)
                $html = $renderer->emaillink($id, $title, $returnOnly);
                break;
            case self::TYPE_LOCAL:
                $html = $renderer->locallink(substr($id, 1), $title, $returnOnly);
                break;
            default:
                if ($lowLink) {
                    syntax_plugin_combo_tooltip::addToolTipSnippetIfNeeded($renderer);
                    $html = LinkUtility::renderLowQualityLink($id, $title);
                } else {
                    $urlQuery = null;
                    $html = $renderer->internallink($id, $title, $urlQuery, $returnOnly);
                }
                break;
        }

        /**
         * The html may be just a text, for instance with an interwiki that does not exist
         * or is not configured
         * if this is the case, add a span to make it xml valid
         */
        if (!XmlUtility::isXml($html)) {
            $html = "<span>$html</span>";
            if (!XmlUtility::isXml($html)) {
                LogUtility::msg("The link ($id) could not be transformed as valid XML");
            }
        }
        return $html;

    }

    /**
     * Keep track of the backlinks ie meta['relation']['references']
     * @param Doku_Renderer_metadata $metaDataRenderer
     * @param array $attributes
     */
    public
    static function handleMetadata($metaDataRenderer, array $attributes)
    {

        $id = $attributes[self::ATTRIBUTE_ID];
        $type = $attributes[self::ATTRIBUTE_TYPE];
        if ($type == self::TYPE_INTERNAL) {
            $metaDataRenderer->internallink($id);
        } else {
            $name = $attributes[self::ATTRIBUTE_TITLE];
            if ($type == self::TYPE_EXTERNAL) {
                $metaDataRenderer->externallink($id, $attributes[self::ATTRIBUTE_TITLE]);
            } else if ($type == self::TYPE_LOCAL) {
                $metaDataRenderer->locallink($id, $name);
            } else if ($type == self::TYPE_EMAIL) {
                $metaDataRenderer->emaillink($id, $name);
            } else if ($type == self::TYPE_INTERWIKI) {
                $interWikiSplit = preg_split("/>/", $id);
                $metaDataRenderer->interwikilink($id, $name, $interWikiSplit[0], $interWikiSplit[1]);
            } else {
                LogUtility::msg("The link ({$id}) with the type " . $type . " was not processed into the metadata");
            }
        }
    }

    /**
     * Return the type of link from an ID
     *
     * @param $id
     * @return string a `TYPE_xxx` constant
     * Code adapted from {@link Doku_Handler}->internallink($match,$state,$pos)
     */
    public
    static function getType($id)
    {
        /**
         * Email validation pattern
         */
        $emailRfc2822 = "0-9a-zA-Z!#$%&'*+/=?^_`{|}~-";
        $emailPattern = '[' . $emailRfc2822 . ']+(?:\.[' . $emailRfc2822 . ']+)*@(?i:[0-9a-z][0-9a-z-]*\.)+(?i:[a-z]{2,63})';

        if (link_isinterwiki($id)) {
            return self::TYPE_INTERWIKI;
        } elseif (preg_match('/^\\\\\\\\[^\\\\]+?\\\\/u', $id)) {
            return self::TYPE_WINDOWS_SHARE;
        } elseif (preg_match('#^([a-z0-9\-\.+]+?)://#i', $id)) {
            return self::TYPE_EXTERNAL;
        } elseif (preg_match('<' . $emailPattern . '>', $id)) {
            return self::TYPE_EMAIL;
        } elseif (preg_match('!^#.+!', $id)) {
            return self::TYPE_LOCAL;
        } else {
            return self::TYPE_INTERNAL;
        }

    }

    /**
     * Inherit the color of their parent and not from Dokuwiki
     * @param $htmlLink
     * @return bool|false|string
     */
    public static function inheritColorFromParent($htmlLink)
    {
        /**
         * The extra style for the link
         */
        return HtmlUtility::addAttributeValue($htmlLink, "style", self::STYLE_VALUE);

    }

    /**
     * Delete wikilink1 from the link
     * @param $htmlLink
     * @return bool|false|string
     */
    public static function deleteDokuWikiClass($htmlLink)
    {
        // only wikilink1 (wikilink2 shows a red link if the page does not exist)
        return HtmlUtility::deleteClassValue($htmlLink, "wikilink1");
    }

    /**
     * Render a link as a span element
     * This is used when a public page links to a low quality page
     * to render a span element
     * The span element is then modified as link by javascript if the user is not anonymous
     * @param string $id
     * @param string $title
     * @return string the html
     */
    public static function renderLowQualityLink($id, $title)
    {
        if (empty($title)) {
            $title = $id;
        }
        return "<span class=\"low-quality\" data-wiki-id=\"{$id}\" data-toggle=\"tooltip\" title=\"To follow this link, you need to log in (" . LowQualityPage::ACRONYM . ")\">{$title}</span>";
    }

    /**
     * @param array $attribute
     * @param array $stats
     * Calculate internal link statistics
     */
    public static function processLinkStats($attribute, array &$stats)
    {
        $id = $attribute[LinkUtility::ATTRIBUTE_ID];
        $type = $attribute[self::ATTRIBUTE_TYPE];

        if ($type == self::TYPE_INTERNAL) {
            /**
             * If this a query string, this is the same page
             */
            global $ID;
            if (strpos($id, '?') !== false) {
                $urlParts = preg_split("/\?/", $id);
                if (sizeof($urlParts) == 1) {
                    $id = $ID;
                } else {
                    $id = $urlParts[0];
                }
            }

            /**
             * Internal link count
             */
            $stats[Analytics::INTERNAL_LINKS_COUNT]++;

            /**
             * Broken link ?
             */
            resolve_pageid(getNS($ID), $id, $exists);
            if (!$exists) {
                $stats[Analytics::INTERNAL_LINKS_BROKEN_COUNT]++;
                $stats[Analytics::INFO][] = "The internal link `{$id}` does not exist";
            }


            /**
             * Calculate link distance
             */
            $a = explode(':', getNS($ID));
            $b = explode(':', getNS($id));
            while (isset($a[0]) && $a[0] == $b[0]) {
                array_shift($a);
                array_shift($b);
            }
            $length = count($a) + count($b);
            $stats[Analytics::INTERNAL_LINK_DISTANCE][] = $length;

        } else if ($type == self::TYPE_EXTERNAL) {

            $stats[Analytics::EXTERNAL_LINKS_COUNT]++;

        } else if ($type == self::TYPE_LOCAL) {

            $stats[Analytics::LOCAL_LINKS_COUNT]++;

        } else if ($type == self::TYPE_INTERWIKI) {

            $stats[Analytics::INTERWIKI_LINKS_COUNT]++;
        } else if ($type == self::TYPE_EMAIL) {

            $stats[Analytics::EMAILS_COUNT]++;

        } else {

            LogUtility::msg("The link `{$id}` with the type (" . $type . ")  is not taken into account into the statistics");

        }


    }


}
