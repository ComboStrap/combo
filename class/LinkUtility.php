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
use http\Exception\RuntimeException;
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

    /**
     * The key of the array for the handle cache
     */
    const ATTRIBUTE_ID = 'id';
    const ATTRIBUTE_NAME = 'name';
    const ATTRIBUTE_IMAGE = 'image';
    const ATTRIBUTE_TYPE = 'type';

    /**
     * Style to cancel the dokuwiki styling
     * Is a constant to be able to use it in the test
     * background is transparent, otherwise, you may see a rectangle with a link in button
     */
    const STYLE_VALUE = ";background-color:transparent;border-color:inherit;color:inherit;background-image:unset;padding:unset";
    /**
     * @var mixed
     */
    private $type;
    /**
     * @var mixed
     */
    private $id;
    /**
     * @var mixed
     */
    private $name;
    /**
     * @var Page the internal linked page if the link is an internal one
     */
    private $linkedPage;

    /**
     * Link constructor.
     * @param $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }


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
        $linkString = preg_replace(array('/^\[\[/', '/\]\]$/u'), '', $match);

        // Split title from URL
        $linkArray = explode('|', $linkString, 2);

        // Id
        $id = trim($linkArray[0]);
        $linkObject = new LinkUtility($id);
        $attributes[self::ATTRIBUTE_ID] = $id;

        // Type
        $attributes[self::ATTRIBUTE_TYPE] = $linkObject->getType();

        // Text or image
        if (!isset($linkArray[1])) {
            $attributes[self::ATTRIBUTE_NAME] = null;
        } else {
            // An image in the title
            if (preg_match('/^\{\{[^\}]+\}\}$/', $linkArray[1])) {
                // If the title is an image, convert it to an array containing the image details
                $attributes[self::ATTRIBUTE_IMAGE] = Doku_Handler_Parse_Media($linkArray[1]);
            } else {
                $attributes[self::ATTRIBUTE_NAME] = $linkArray[1];
            }
        }

        return $attributes;

    }

    /**
     * @param Doku_Renderer_xhtml $renderer
     * @return mixed
     */
    public function render($renderer)
    {

        /**
         * To allow {@link \syntax_plugin_combo_pipeline}
         */
        $name = $this->name;
        if (strpos($this->name, "<pipeline>") !== false) {
            $name = str_replace("<pipeline>", "", $name);
            $name = str_replace("</pipeline>", "", $name);
            $name = PipelineUtility::execute($name);
        }

        // Always return the string
        $returnOnly = true;

        // The HTML created by DokuWiki
        switch ($this->getType()) {
            case self::TYPE_INTERWIKI:
                // Interwiki
                $interWiki = explode('>', $this->id, 2);
                $wikiName = strtolower($interWiki[0]);
                $wikiUri = $interWiki[1];
                $html = $renderer->interwikilink($this->id, $name, $wikiName, $wikiUri, $returnOnly);
                break;
            case self::TYPE_WINDOWS_SHARE:
                $html = $renderer->windowssharelink($this->id, $name);
                break;
            case self::TYPE_EXTERNAL:
                $html = $renderer->externallink($this->id, $name, $returnOnly);
                break;
            case self::TYPE_EMAIL:
                // E-Mail (pattern above is defined in inc/mail.php)
                $html = $renderer->emaillink($this->id, $name, $returnOnly);
                break;
            case self::TYPE_LOCAL:
                $html = $renderer->locallink(substr($this->id, 1), $name, $returnOnly);
                break;
            case self::TYPE_INTERNAL:

                $linkedPage = $this->getInternalPage();

                /**
                 * If this is a low quality internal page,
                 * print a shallow link for the anonymous user
                 */
                $lowLink = false;
                global $conf;
                $lqppEnable = $conf['plugin'][PluginUtility::PLUGIN_BASE_NAME][LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE];
                if ($lqppEnable == 1
                    && $linkedPage->isLowQualityPage()) {
                    $lowLink = true;
                }

                if ($lowLink) {

                    LowQualityPage::addLowQualityPageHtmlSnippet($renderer);
                    $html = LowQualityPage::renderLowQualityLink($this);

                } else {
                    $urlQuery = null;
                    $html = $renderer->internallink($this->id, $name, $urlQuery, $returnOnly);
                }
                break;
            default:
                LogUtility::msg("The link ({$this->id}) with the type " . $this->type . " was not rendered because it's not taken into account");
        }

        /**
         * The html may be just a text, for instance with an interwiki that does not exist
         * or is not configured
         * if this is the case, add a span to make it xml valid
         */
        if (!XmlUtility::isXml($html)) {
            $html = "<span>$html</span>";
            if (!XmlUtility::isXml($html)) {
                LogUtility::msg("The link ($this->id) could not be transformed as valid XML");
            }
        }
        return $html;

    }

    /**
     * Keep track of the backlinks ie meta['relation']['references']
     * @param Doku_Renderer_metadata $metaDataRenderer
     */
    public function handleMetadata($metaDataRenderer)
    {
        if ($this->type == self::TYPE_INTERNAL) {
            $metaDataRenderer->internallink($this->id);
        } else {
            if ($this->type == self::TYPE_EXTERNAL) {
                $metaDataRenderer->externallink($this->id, $this->name);
            } else if ($this->type == self::TYPE_LOCAL) {
                $metaDataRenderer->locallink($this->id, $this->name);
            } else if ($this->type == self::TYPE_EMAIL) {
                $metaDataRenderer->emaillink($this->id, $this->name);
            } else if ($this->type == self::TYPE_INTERWIKI) {
                $interWikiSplit = preg_split("/>/", $this->id);
                $metaDataRenderer->interwikilink($this->id, $this->name, $interWikiSplit[0], $interWikiSplit[1]);
            } else {
                LogUtility::msg("The link ({$this->id}) with the type " . $this->type . " was not processed into the metadata");
            }
        }
    }

    /**
     * Return the type of link from an ID
     *
     * @return string a `TYPE_xxx` constant
     * Code adapted from {@link Doku_Handler}->internallink($match,$state,$pos)
     */
    public function getType()
    {
        if ($this->type == null) {
            /**
             * Email validation pattern
             */
            $emailRfc2822 = "0-9a-zA-Z!#$%&'*+/=?^_`{|}~-";
            $emailPattern = '[' . $emailRfc2822 . ']+(?:\.[' . $emailRfc2822 . ']+)*@(?i:[0-9a-z][0-9a-z-]*\.)+(?i:[a-z]{2,63})';

            if (link_isinterwiki($this->id)) {
                $this->type = self::TYPE_INTERWIKI;
            } elseif (preg_match('/^\\\\\\\\[^\\\\]+?\\\\/u', $this->id)) {
                $this->type = self::TYPE_WINDOWS_SHARE;
            } elseif (preg_match('#^([a-z0-9\-\.+]+?)://#i', $this->id)) {
                $this->type = self::TYPE_EXTERNAL;
            } elseif (preg_match('<' . $emailPattern . '>', $this->id)) {
                $this->type = self::TYPE_EMAIL;
            } elseif (preg_match('!^#.+!', $this->id)) {
                $this->type = self::TYPE_LOCAL;
            } else {
                $this->type = self::TYPE_INTERNAL;
            }
        }
        return $this->type;


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
     * @param array $stats
     * Calculate internal link statistics
     */
    public function processLinkStats(array &$stats)
    {

        if ($this->getType() == self::TYPE_INTERNAL) {
            /**
             * If this a query string, this is the same page
             */
            global $ID;
            if (strpos($this->id, '?') !== false) {
                $urlParts = preg_split("/\?/", $this->id);
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
            if (!$this->getInternalPage()->existInFs()) {
                $stats[Analytics::INTERNAL_LINKS_BROKEN_COUNT]++;
                $stats[Analytics::INFO][] = "The internal link `{$this->getInternalPage()->getId()}` does not exist";
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

        } else if ($this->getType() == self::TYPE_EXTERNAL) {

            $stats[Analytics::EXTERNAL_LINKS_COUNT]++;

        } else if ($this->getType() == self::TYPE_LOCAL) {

            $stats[Analytics::LOCAL_LINKS_COUNT]++;

        } else if ($this->getType() == self::TYPE_INTERWIKI) {

            $stats[Analytics::INTERWIKI_LINKS_COUNT]++;
        } else if ($this->getType() == self::TYPE_EMAIL) {

            $stats[Analytics::EMAILS_COUNT]++;

        } else {

            LogUtility::msg("The link `{$this->id}` with the type (" . $this->getType() . ")  is not taken into account into the statistics");

        }


    }

    /**
     * @return string - the internal absolute page id
     */
    public function getAbsoluteId()
    {
        if ($this->getType() == self::TYPE_INTERNAL) {
            global $ID;
            $qualifiedPageId = $this->id;
            resolve_pageid(getNS($ID), $qualifiedPageId, $exists);
            return $qualifiedPageId;
        } else {
            throw new \RuntimeException("You can't ask an absolute id from a link that is not an internal one");
        }
    }

    /**
     * @return Page - the internal page or an error if the link is not an internal one
     */
    public function getInternalPage()
    {
        if ($this->linkedPage == null) {
            if ($this->getType() == self::TYPE_INTERNAL) {
                /**
                 * Create the linked page object
                 */
                $qualifiedPageLinkId = $this->getAbsoluteId();
                $this->linkedPage = new Page($qualifiedPageLinkId);
            } else {
                throw new \RuntimeException("You can't ask the internal page id from a link that is not an internal one");
            }
        }
        return $this->linkedPage;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }


}
