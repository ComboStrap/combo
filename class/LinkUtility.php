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
use dokuwiki\Extension\PluginTrait;

require_once(__DIR__ . '/../../combo/class/' . 'TemplateUtility.php');
require_once(__DIR__ . '/../../combo/class/' . 'Publication.php');

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
    const SPECIAL_PATTERN = "\[\[.*?\]\](?!\])";

    /**
     * A link may have a title or not
     * ie
     * [[path:page]]
     * [[path:page|title]]
     * are valid
     *
     * Get the content until | or ]
     */
    const ENTRY_PATTERN = "\[\[[^\|\]]*(?=.*\]\])";
    const EXIT_PATTERN = "\]\]";

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
    const ATTRIBUTE_REF = 'ref';
    const ATTRIBUTE_NAME = 'name';
    const ATTRIBUTE_IMAGE = 'image';


    /**
     * Style to cancel the dokuwiki styling
     * when the page linked exists
     * Is a constant to be able to use it in the test
     * background is transparent, otherwise, you may see a rectangle with a link in button
     */
    const STYLE_VALUE_WHEN_EXIST =
        array(
            "background-color" => "transparent",
            "border-color" => "inherit",
            "color" => "inherit",
            "background-image" => "unset",
            "padding" => "unset"
        );


    /**
     * Class added to the type of link
     * Class have styling rule conflict, they are by default not set
     * but this configuration permits to turn it back
     */
    const CONF_USE_DOKUWIKI_CLASS_NAME = "useDokuwikiLinkClassName";

    /**
     * @var mixed
     */
    private $type;
    /**
     * @var mixed
     */
    private $ref;
    /**
     * @var mixed
     */
    private $name;
    /**
     * @var Page the internal linked page if the link is an internal one
     */
    private $linkedPage;

    /**
     * @var string The value of the title attribute of an anchor
     */
    private $title;
    /**
     * @var mixed|string
     */
    private $id;
    /**
     * @var mixed|string
     */
    private $parameters;
    /**
     * @var false|string
     */
    private $fragment;

    private $attributes = array();
    /**
     * The name of the wiki for an inter wiki link
     * @var string
     */
    private $wiki;

    /**
     * @var Doku_Renderer_xhtml
     */
    private $renderer;

    /**
     *
     * @var false|string
     */
    private $schemeUri;

    /**
     * The uri scheme that can be used inside a page
     * @var array
     */
    private $authorizedSchemes;

    /**
     * Link constructor.
     * @param $ref
     */
    public function __construct($ref)
    {


        /**
         * Windows share link
         */
        if ($this->type == null) {
            if (preg_match('/^\\\\\\\\[^\\\\]+?\\\\/u', $ref)) {
                $this->type = self::TYPE_WINDOWS_SHARE;
                $this->ref = $ref;
                return;
            }
        }

        /**
         * URI like links section with query and fragment
         */

        /**
         * Local
         */
        if ($this->type == null) {
            if (preg_match('!^#.+!', $ref)) {
                $this->type = self::TYPE_LOCAL;
                $this->ref = $ref;
            }
        }

        /**
         * Email validation pattern
         * E-Mail (pattern below is defined in inc/mail.php)
         *
         * Example:
         * [[support@combostrap.com?subject=hallo]]
         * [[support@combostrap.com]]
         */
        if ($this->type == null) {
            $emailRfc2822 = "0-9a-zA-Z!#$%&'*+/=?^_`{|}~-";
            $emailPattern = '[' . $emailRfc2822 . ']+(?:\.[' . $emailRfc2822 . ']+)*@(?i:[0-9a-z][0-9a-z-]*\.)+(?i:[a-z]{2,63})';
            if (preg_match('<' . $emailPattern . '>', $ref)) {
                $this->type = self::TYPE_EMAIL;
                $this->ref = $ref;
                // we don't return. The query part is parsed afterwards
            }
        }


        /**
         * External
         */
        if ($this->type == null) {
            if (preg_match('#^([a-z0-9\-\.+]+?)://#i', $ref)) {
                $this->type = self::TYPE_EXTERNAL;
                $this->schemeUri = strtolower(substr($ref, 0, strpos($ref, "://")));
                $this->ref = $ref;
            }
        }

        /**
         * interwiki ?
         */
        $refProcessing = $ref;
        if ($this->type == null) {
            $interwikiPosition = strpos($refProcessing, ">");
            if ($interwikiPosition !== false) {
                $this->wiki = strtolower(substr($refProcessing, 0, $interwikiPosition));
                $refProcessing = substr($refProcessing, $interwikiPosition + 1);
                $this->ref = $ref;
                $this->type = self::TYPE_INTERWIKI;
            }
        }

        /**
         * Internal then
         */
        if ($this->type == null) {
            $this->type = self::TYPE_INTERNAL;
            $this->ref = $ref;
        }


        /**
         *
         */
        $position = strpos($refProcessing, "?");
        if ($position !== false) {

            $this->id = substr($refProcessing, 0, $position);
            $secondPart = substr($refProcessing, $position + 1);
            $anchorPosition = strpos($secondPart, "#");
            if ($anchorPosition !== false) {
                $this->parameters = substr($secondPart, 0, $anchorPosition);
                $this->fragment = substr($secondPart, $anchorPosition + 1);
            } else {
                $this->parameters = $secondPart;
            }
        } else {

            $anchorPosition = strpos($refProcessing, "#");
            if ($anchorPosition !== false) {
                $this->id = substr($refProcessing, 0, $anchorPosition);
                $this->fragment = substr($refProcessing, $anchorPosition + 1);
            } else {
                $this->id = $refProcessing;
            }
        }


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
    public static function parse($match)
    {

        // Strip the opening and closing markup
        $linkString = preg_replace(array('/^\[\[/', '/\]\]$/u'), '', $match);

        // Split title from URL
        $linkArray = explode('|', $linkString, 2);

        // Id
        $attributes[self::ATTRIBUTE_REF] = trim($linkArray[0]);


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
     *
     * Derived from {@link Doku_Renderer_xhtml::internallink()}
     * and others
     *
     */
    public function renderOpenTag($renderer)
    {
        /**
         * Keep a reference to the renderer
         * The {@link LinkUtility::getUrl()} depends on it
         */
        $this->renderer = $renderer;

        global $conf;

        /**
         * Get the url
         */
        $url = $this->getUrl();
        if (!empty($url)) {
            PluginUtility::addAttributeValue("href", $url, $this->attributes);
        }

        /**
         * Processing by type
         */
        switch ($this->getType()) {
            case self::TYPE_INTERWIKI:

                /**
                 * Target
                 */
                $interwikiConf = $conf['target']['interwiki'];
                if ($interwikiConf) {

                    PluginUtility::addAttributeValue('target', $interwikiConf, $this->attributes);
                    PluginUtility::addAttributeValue('rel', 'noopener', $this->attributes);
                }
                PluginUtility::addClass2Attributes("interwiki", $this->attributes);
                $wikiClass = "iw_" . preg_replace('/[^_\-a-z0-9]+/i', '_', $this->getWiki());
                PluginUtility::addClass2Attributes($wikiClass, $this->attributes);

                break;
            case self::TYPE_INTERNAL:

                /**
                 * Internal Page
                 */
                $linkedPage = $this->getInternalPage();
                $this->attributes["data-wiki-id"] = $this->toAbsoluteId();

                /**
                 * If this is a low quality internal page,
                 * print a shallow link for the anonymous user
                 */
                $lowLink = $this->isProtectedLink();
                if ($lowLink) {

                    PageProtection::addPageProtectionSnippet();
                    PluginUtility::addClass2Attributes(PageProtection::PROTECTED_LINK_CLASS, $this->attributes);
                    $protectionSourceAcronym = "";
                    if ($this->getInternalPage()->isLowQualityPage()) {
                        $protectionSourceAcronym = LowQualityPage::LOW_QUALITY_PROTECTION_ACRONYM;
                    } else if ($this->getInternalPage()->isLatePublication()) {
                        $protectionSourceAcronym = Publication::LATE_PUBLICATION_PROTECTION_ACRONYM;
                    }
                    PluginUtility::addAttributeValue(PageProtection::HTML_DATA_ATTRIBUTES, $protectionSourceAcronym, $this->attributes);
                    unset($this->attributes["href"]);
                    $this->attributes["data-toggle"] = "tooltip";
                    $this->attributes["title"] = "To follow this link ({$this->toAbsoluteId()}), you need to log in (" . $protectionSourceAcronym . ")";

                } else {

                    if (!$linkedPage->existInFs()) {

                        /**
                         * Red color
                         */
                        PluginUtility::addClass2Attributes(self::getHtmlClassNotExist(), $this->attributes);
                        PluginUtility::addAttributeValue("rel", 'nofollow', $this->attributes);

                    } else {

                        PluginUtility::addClass2Attributes(self::getHtmlClassInternalLink(), $this->attributes);

                    }

                    $this->attributes["title"] = $linkedPage->getTitle();

                }
                break;
            case self::TYPE_EXTERNAL:
                if ($conf['relnofollow']) {
                    PluginUtility::addAttributeValue("rel", 'nofollow', $this->attributes);
                    PluginUtility::addAttributeValue("rel", 'ugc', $this->attributes);
                }
                if ($conf['target']['extern']) {
                    PluginUtility::addAttributeValue("rel", 'noopener', $this->attributes);
                }
                PluginUtility::addClass2Attributes(self::getHtmlClassExternalLink(), $this->attributes);
                break;
            case self::TYPE_WINDOWS_SHARE:
                PluginUtility::addClass2Attributes("windows", $this->attributes);
                break;
            case self::TYPE_LOCAL:
                break;
            case self::TYPE_EMAIL:
                PluginUtility::addClass2Attributes(self::getHtmlClassEmailLink(), $this->attributes);
                break;
            default:
                LogUtility::msg("The type (" . $this->getType() . ") is unknown", LogUtility::LVL_MSG_ERROR, \syntax_plugin_combo_link::TAG);

        }

        /**
         * Title settings
         */
        if (!key_exists("title", $this->attributes)) {
            $title = $this->getTitle();
            if (!empty($title)) {
                $this->attributes["title"] = $title;
            }
        }

        /**
         * Return
         */
        $tag = $this->getHTMLTag();
        $returnedHTML = "<$tag";

        /**
         * An email URL and title
         * may be already encoded because of the vanguard configuration
         *
         * The url is not treated as an attribute
         * because the function array2HTMLAttributes encodes the value
         * to mitigate XSS
         *
         */
        if ($this->getType() == self::TYPE_EMAIL) {
            $emailAddress = $this->emailObfuscation($this->getId());
            $returnedHTML .= " href=\"$url\"";
            $returnedHTML .= " title=\"$emailAddress\"";
            unset($this->attributes["href"]);
            unset($this->attributes["title"]);
        }
        if (sizeof($this->attributes) > 0) {
            $returnedHTML .= " " . PluginUtility::array2HTMLAttributes($this->attributes);
        }
        return $returnedHTML . ">";


    }

    /**
     * Keep track of the backlinks ie meta['relation']['references']
     * @param Doku_Renderer_metadata $metaDataRenderer
     */
    public function handleMetadata($metaDataRenderer)
    {

        switch ($this->getType()) {
            case self::TYPE_INTERNAL:
                /**
                 * The relative link should be passed (ie the original)
                 */
                $metaDataRenderer->internallink($this->ref);
                break;
            case self::TYPE_EXTERNAL:
                $metaDataRenderer->externallink($this->ref, $this->name);
                break;
            case self::TYPE_LOCAL:
                $metaDataRenderer->locallink($this->ref, $this->name);
                break;
            case self::TYPE_EMAIL:
                $metaDataRenderer->emaillink($this->ref, $this->name);
                break;
            case self::TYPE_INTERWIKI:
                $interWikiSplit = preg_split("/>/", $this->ref);
                $metaDataRenderer->interwikilink($this->ref, $this->name, $interWikiSplit[0], $interWikiSplit[1]);
                break;
            case self::TYPE_WINDOWS_SHARE:
                $metaDataRenderer->windowssharelink($this->ref, $this->name);
                break;
            default:
                LogUtility::msg("The link ({$this->ref}) with the type " . $this->type . " was not processed into the metadata");
        }
    }

    /**
     * Return the type of link from an ID
     *
     * @return string a `TYPE_xxx` constant
     */
    public
    function getType()
    {
        return $this->type;
    }

    /**
     * Inherit the color of their parent and not from Dokuwiki
     * @param $htmlLink
     * @return bool|false|string
     * @deprecated as we have taken the link creation over from dokuwiki
     */
    public
    static function inheritColorFromParent($htmlLink)
    {
        /**
         * The extra style for the link
         */
        $inlineStyle = StyleUtility::createInlineValue(self::STYLE_VALUE_WHEN_EXIST);
        return HtmlUtility::addAttributeValue($htmlLink, "style", $inlineStyle);

    }

    /**
     * Delete wikilink1 from the link
     * @param $htmlLink
     * @return bool|false|string
     */
    public
    static function deleteDokuWikiClass($htmlLink)
    {
        // only wikilink1 (wikilink2 shows a red link if the page does not exist)
        return HtmlUtility::deleteClassValue($htmlLink, self::CLASS_DOES_NOT_EXIST);
    }


    /**
     * @param array $stats
     * Calculate internal link statistics
     */
    public
    function processLinkStats(array &$stats)
    {

        if ($this->getType() == self::TYPE_INTERNAL) {


            /**
             * Internal link count
             */
            if (!array_key_exists(Analytics::INTERNAL_LINKS_COUNT, $stats)) {
                $stats[Analytics::INTERNAL_LINKS_COUNT] = 0;
            }
            $stats[Analytics::INTERNAL_LINKS_COUNT]++;


            /**
             * Broken link ?
             */
            $id = $this->getInternalPage()->getId();
            if (!$this->getInternalPage()->existInFs()) {
                $stats[Analytics::INTERNAL_LINKS_BROKEN_COUNT]++;
                $stats[Analytics::INFO][] = "The internal link `{$id}` does not exist";
            }

            /**
             * Calculate link distance
             */
            global $ID;
            $a = explode(':', getNS($ID));
            $b = explode(':', getNS($id));
            while (isset($a[0]) && $a[0] == $b[0]) {
                array_shift($a);
                array_shift($b);
            }
            $length = count($a) + count($b);
            $stats[Analytics::INTERNAL_LINK_DISTANCE][] = $length;

        } else if ($this->getType() == self::TYPE_EXTERNAL) {

            if (!array_key_exists(Analytics::EXTERNAL_LINKS_COUNT, $stats)) {
                $stats[Analytics::EXTERNAL_LINKS_COUNT] = 0;
            }
            $stats[Analytics::EXTERNAL_LINKS_COUNT]++;

        } else if ($this->getType() == self::TYPE_LOCAL) {

            if (!array_key_exists(Analytics::LOCAL_LINKS_COUNT, $stats)) {
                $stats[Analytics::LOCAL_LINKS_COUNT] = 0;
            }
            $stats[Analytics::LOCAL_LINKS_COUNT]++;

        } else if ($this->getType() == self::TYPE_INTERWIKI) {

            if (!array_key_exists(Analytics::INTERWIKI_LINKS_COUNT, $stats)) {
                $stats[Analytics::INTERWIKI_LINKS_COUNT] = 0;
            }
            $stats[Analytics::INTERWIKI_LINKS_COUNT]++;

        } else if ($this->getType() == self::TYPE_EMAIL) {

            if (!array_key_exists(Analytics::EMAILS_COUNT, $stats)) {
                $stats[Analytics::EMAILS_COUNT] = 0;
            }
            $stats[Analytics::EMAILS_COUNT]++;

        } else if ($this->getType() == self::TYPE_WINDOWS_SHARE) {

            if (!array_key_exists(Analytics::WINDOWS_SHARE_COUNT, $stats)) {
                $stats[Analytics::WINDOWS_SHARE_COUNT] = 0;
            }
            $stats[Analytics::WINDOWS_SHARE_COUNT]++;

        } else {

            LogUtility::msg("The link `{$this->ref}` with the type (" . $this->getType() . ")  is not taken into account into the statistics");

        }


    }

    /**
     * @return string - the internal absolute page id
     */
    public
    function toAbsoluteId()
    {
        if ($this->getType() == self::TYPE_INTERNAL) {

            $absoluteId = $this->id;
            if (strpos($absoluteId, ':') !== 0) {
                // Relative
                global $ID;
                resolve_pageid(getNS($ID), $absoluteId, $exists);
            }
            // https://www.dokuwiki.org/config:useslash
            global $conf;
            if ($conf['useslash']) {
                $absoluteId = str_replace(":", "/", $absoluteId);
            }

            return cleanID($absoluteId);
        } else {
            throw new \RuntimeException("You can't ask an absolute id from a link that is not an internal one");
        }
    }

    /**
     * @return Page - the internal page or an error if the link is not an internal one
     */
    public
    function getInternalPage()
    {
        if ($this->linkedPage == null) {
            if ($this->getType() == self::TYPE_INTERNAL) {
                /**
                 * Create the linked page object
                 */
                $qualifiedPageLinkId = $this->toAbsoluteId();
                $this->linkedPage = new Page($qualifiedPageLinkId);
            } else {
                throw new \RuntimeException("You can't ask the internal page id from a link that is not an internal one");
            }
        }
        return $this->linkedPage;
    }

    public
    function getRef()
    {
        return $this->ref;
    }

    public
    function getName()
    {
        $name = $this->name;

        /**
         * Templating
         */
        switch ($this->getType()) {
            case self::TYPE_INTERNAL:
                if (!empty($name)) {
                    /**
                     * With the new link syntax class, this is no more possible
                     * because there is an enter and exit state
                     * TODO: create a function to render on DOKU_LEXER_UNMATCHED ?
                     */
                    $name = TemplateUtility::render($name, $this->toAbsoluteId());
                }
                if (empty($name)) {
                    $name = $this->ref;
                    if (useHeading('content')) {
                        $page = $this->getInternalPage();
                        $h1 = $page->getH1();
                        if (!empty($h1)) {
                            $name = $h1;
                        } else {
                            /**
                             * In dokuwiki by default, title = h1
                             * If there is no h1, we take title
                             * for backward compatibility
                             */
                            $title = $page->getTitle();
                            if (!empty($title)) {
                                $name = $title;
                            }
                        }
                    }
                }
                break;
            case self::TYPE_EMAIL:
                if (empty($name)) {
                    global $conf;
                    $email = $this->getId();
                    switch ($conf['mailguard']) {
                        case 'none' :
                            $name = $email;
                            break;
                        case 'visible' :
                        default :
                            $obfuscate = array('@' => ' [at] ', '.' => ' [dot] ', '-' => ' [dash] ');
                            $name = strtr($email, $obfuscate);
                    }

                }
                break;
            case self::TYPE_INTERWIKI:
                if (empty($name)) {
                    $name = $this->getId();
                }
                break;
            case self::TYPE_LOCAL:
                if (empty($name)) {
                    $name = $this->getFragment();
                }
                break;
            default:
                return $this->getRef();
        }

        return $name;
    }

    /**
     * @param $title -the value of the title attribute of the anchor
     */
    public
    function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string the title of the link
     */
    public
    function getTitle()
    {
        if (empty($this->title)) {
            switch ($this->type) {
                case self::TYPE_INTERNAL:
                    $this->title = $this->getInternalPage()->getTitle();
                    break;
                case self::TYPE_EXTERNAL:
                    // null, stay empty
                    break;
            }
        }
        return $this->title;
    }


    public
    function getId()
    {
        return $this->id;
    }

    public
    function getQueries()
    {
        return $this->parameters;
    }

    public
    function getFragment()
    {
        return $this->fragment;
    }

    private
    function getUrl()
    {
        $url = "";
        switch ($this->getType()) {
            case self::TYPE_INTERNAL:
                $url = wl($this->toAbsoluteId(), $this->parameters);
                if ($this->fragment) {
                    $url .= '#' . $this->fragment;
                }
                break;
            case self::TYPE_INTERWIKI:
                $wiki = $this->wiki;
                $url = $this->renderer->_resolveInterWiki($wiki, $this->getId());
                break;
            case self::TYPE_WINDOWS_SHARE:
                $url = str_replace('\\', '/', $this->getRef());
                $url = 'file:///' . $url;
                break;
            case self::TYPE_EXTERNAL:
                /**
                 * Authorized scheme only
                 * to not inject code
                 */
                if (is_null($this->authorizedSchemes)) {
                    $this->authorizedSchemes = getSchemes();
                }
                if (!in_array($this->schemeUri, $this->authorizedSchemes)) {
                    $url = '';
                } else {
                    $url = $this->ref;
                }
                break;
            case self::TYPE_EMAIL:
                /**
                 * An email link is `<email>`
                 * {@link Emaillink::connectTo()}
                 * or
                 * {@link PluginTrait::email()
                 */
                // common.php#obfsucate implements the $conf['mailguard']
                $address = $this->emailObfuscation($this->ref);
                // Encode only if visible, the hex option
                // should not be encoded (otherwise, double up with the & characters)
                global $conf;
                if ($conf['mailguard'] == 'visible') {
                    $address = rawurlencode($address);
                }
                $url = 'mailto:' . $address;
                break;
            case self::TYPE_LOCAL:
                $url = '#' . $this->renderer->_headerToLink($this->ref);
                break;
            default:
                LogUtility::log2FrontEnd("The url type (" . $this->getType() . ") was not expected to get the URL", LogUtility::LVL_MSG_ERROR, \syntax_plugin_combo_link::TAG);
        }


        return $url;
    }

    public
    function getWiki()
    {
        return $this->wiki;
    }

    /**
     * @return array
     */
    public
    function getAttribute()
    {
        return $this->attributes;
    }

    public
    function setAttributes(array &$attributes)
    {
        $this->attributes = &$attributes;
    }

    public
    function getScheme()
    {
        return $this->schemeUri;
    }

    /**
     * @return bool true if the page should be protected
     */
    private function isProtectedLink()
    {
        $protectedLink = false;
        if ($this->getType() == self::TYPE_INTERNAL) {
            global $conf;

            // Low Quality Page protection
            $lqppEnable = $conf['plugin'][PluginUtility::PLUGIN_BASE_NAME][LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE];
            if ($lqppEnable == 1
                && $this->getInternalPage()->isLowQualityPage()) {
                $protectedLink = true;
            }

            if ($protectedLink === false) {
                $latePublicationProtectionEnabled = $conf['plugin'][PluginUtility::PLUGIN_BASE_NAME][Publication::CONF_LATE_PUBLICATION_PROTECTION_ENABLE];
                if ($latePublicationProtectionEnabled == 1
                    && $this->getInternalPage()->isLatePublication()) {
                    $protectedLink = true;
                }
            }
        }
        return $protectedLink;
    }

    public function getHTMLTag()
    {
        switch ($this->getType()) {
            case self::TYPE_INTERNAL:
                // We could also have used a <a> with `rel="nofollow"`
                // The span element is then modified as link by javascript if the user is not anonymous
                if ($this->isProtectedLink()) {
                    return "span";
                } else {
                    return "a";
                }
                break;
            case self::TYPE_INTERWIKI:
                if (!$this->wikiExists()) {
                    return "span";
                } else {
                    return "a";
                }
            default:
                return "a";
        }

    }

    private function wikiExists()
    {
        $wikis = getInterwiki();
        return key_exists($this->wiki, $wikis);
    }

    private function emailObfuscation($input)
    {
        return obfuscate($input);
    }

    public function renderClosingTag()
    {
        $HTMLTag = $this->getHTMLTag();
        return "</$HTMLTag>";
    }

    public function isRelative()
    {
        return strpos($this->getId(), ':') !== 0;
    }

    /**
     * @param $input
     * @return string|string[] Encode
     */
    private function urlEncoded($input)
    {
        /**
         * URL encoded
         */
        $input = str_replace('&', '&amp;', $input);
        $input = str_replace('&amp;amp;', '&amp;', $input);
        return $input;
    }

    public static function getHtmlClassInternalLink()
    {
        $oldClassName = PluginUtility::getConfValue(self::CONF_USE_DOKUWIKI_CLASS_NAME);
        if ($oldClassName) {
            return "wikilink1";
        } else {
            return "link-internal";
        }
    }

    public static function getHtmlClassEmailLink()
    {
        $oldClassName = PluginUtility::getConfValue(self::CONF_USE_DOKUWIKI_CLASS_NAME);
        if ($oldClassName) {
            return "mail";
        } else {
            return "link-mail";
        }
    }

    public static function getHtmlClassExternalLink()
    {
        $oldClassName = PluginUtility::getConfValue(self::CONF_USE_DOKUWIKI_CLASS_NAME);
        if ($oldClassName) {
            return "urlextern";
        } else {
            return "link-external";
        }
    }

    //FYI: exist in dokuwiki is "wikilink1 but we let the control to the user
    public static function getHtmlClassNotExist()
    {
        $oldClassName = PluginUtility::getConfValue(self::CONF_USE_DOKUWIKI_CLASS_NAME);
        if ($oldClassName) {
            return "wikilink2";
        } else {
            return "text-danger";
        }
    }


}
