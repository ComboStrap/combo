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
use dokuwiki\Utf8\Conversion;
use syntax_plugin_combo_tooltip;

require_once(__DIR__ . '/PluginUtility.php');

/**
 *
 * @package ComboStrap
 *
 * Parse the ref found in a markup link
 * and return an XHTML compliant array
 * with href, style, ... attributes
 */
class MarkupRef
{



    /**
     * Type of link
     */
    const INTERWIKI_URI = 'interwiki';
    const WINDOWS_SHARE_URI = 'windowsShare';
    const STANDARD_URI = 'external';

    const EMAIL_URI = 'email';
    const LOCAL_URI = 'local';
    const WIKI_URI = 'internal';
    const VARIABLE_URI = 'internal_template';


    /**
     * Class added to the type of link
     * Class have styling rule conflict, they are by default not set
     * but this configuration permits to turn it back
     */
    const CONF_USE_DOKUWIKI_CLASS_NAME = "useDokuwikiLinkClassName";
    /**
     * This configuration will set for all internal link
     * the {@link MarkupRef::PREVIEW_ATTRIBUTE} preview attribute
     */
    const CONF_PREVIEW_LINK = "previewLink";
    const CONF_PREVIEW_LINK_DEFAULT = 0;


    const TEXT_ERROR_CLASS = "text-danger";

    /**
     * The known parameters for an email url
     */
    const EMAIL_VALID_PARAMETERS = ["subject"];

    /**
     * If set, it will show a page preview
     */
    const PREVIEW_ATTRIBUTE = "preview";
    const PREVIEW_TOOLTIP = "preview";

    /**
     * Highlight Key
     * Adding this property to the internal query will highlight the words
     *
     * See {@link html_hilight}
     */
    const SEARCH_HIGHLIGHT_QUERY_PROPERTY = "s";


    /**
     * @var mixed
     */
    private $uriType;
    /**
     * @var mixed
     */
    private $ref;

    /**
     * @var Page the internal linked page if the link is an internal one
     */
    private $linkedPage;

    /**
     * @var string The value of the title attribute of an anchor
     */
    private $title;


    /**
     * The name of the wiki for an inter wiki link
     * @var string
     */
    private $wiki;


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
     * @var string the query string as it was parsed
     */
    private $originalQueryString;
    /**
     * @var DokuwikiUrl
     */
    private $dokuwikiUrl;
    /**
     * @var array|string|null
     */
    private $type;
    /**
     * @var array
     */
    private $interwiki;

    /**
     * Link constructor.
     * @param $ref
     */
    public function __construct($ref)
    {


        /**
         * Windows share link
         */
        if ($this->uriType == null) {
            if (preg_match('/^\\\\\\\\[^\\\\]+?\\\\/u', $ref)) {
                $this->uriType = self::WINDOWS_SHARE_URI;
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
        if ($this->uriType == null) {
            if (preg_match('!^#.+!', $ref)) {
                $this->uriType = self::LOCAL_URI;
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
        if ($this->uriType == null) {
            $emailRfc2822 = "0-9a-zA-Z!#$%&'*+/=?^_`{|}~-";
            $emailPattern = '[' . $emailRfc2822 . ']+(?:\.[' . $emailRfc2822 . ']+)*@(?i:[0-9a-z][0-9a-z-]*\.)+(?i:[a-z]{2,63})';
            if (preg_match('<' . $emailPattern . '>', $ref)) {
                $this->uriType = self::EMAIL_URI;
                $this->ref = $ref;
                // we don't return. The query part is parsed afterwards
            }
        }


        /**
         * External (ie only https)
         */
        if ($this->uriType == null) {
            /**
             * Example: `https://`
             *
             * Other scheme are not yet recognized
             * because it can also be a wiki id
             * For instance, `mailto:` is also a valid page
             */
            if (preg_match('#^([a-z0-9\-\.+]+?)://#i', $ref)) {
                $this->uriType = self::STANDARD_URI;
                $this->schemeUri = strtolower(substr($ref, 0, strpos($ref, ":")));
                $this->ref = $ref;
            }
        }

        /**
         * Interwiki ?
         */
        $refProcessing = $ref;
        if ($this->uriType == null) {
            $interwikiPosition = strpos($refProcessing, ">");
            if ($interwikiPosition !== false) {
                $this->wiki = strtolower(substr($refProcessing, 0, $interwikiPosition));
                $refProcessing = substr($refProcessing, $interwikiPosition + 1);
                $this->ref = $ref;
                $this->uriType = self::INTERWIKI_URI;
            }
        }

        /**
         * Internal then
         */
        if ($this->uriType == null) {
            /**
             * It can be a link with a ref template
             */
            if (TemplateUtility::isVariable($ref)) {
                $this->uriType = self::VARIABLE_URI;
            } else {
                $this->uriType = self::WIKI_URI;
            }
            $this->ref = $ref;
        }


        /**
         * Url (called ref by dokuwiki)
         */
        $this->dokuwikiUrl = DokuwikiUrl::createFromUrl($refProcessing);


    }

    public static function createFromPageId($id): MarkupRef
    {
        return new MarkupRef(":$id");
    }

    public static function createFromRef(string $ref): MarkupRef
    {
        return new MarkupRef($ref);
    }


    /**
     * @param $uriType
     * @return $this
     */
    public function setUriType($uriType): MarkupRef
    {
        $this->uriType = $uriType;
        return $this;
    }


    /**
     *
     *
     *
     * @throws ExceptionCombo
     */
    public function toAttributes($logicalTag = \syntax_plugin_combo_link::TAG): TagAttributes
    {

        $outputAttributes = TagAttributes::createEmpty($logicalTag);

        $type = $this->getUriType();


        /**
         * Add the attribute from the URL
         * if this is not a `do`
         */

        switch ($type) {
            case self::WIKI_URI:
                if (!$this->dokuwikiUrl->hasQueryParameter("do")) {
                    foreach ($this->getDokuwikiUrl()->getQueryParameters() as $key => $value) {
                        if ($key !== self::SEARCH_HIGHLIGHT_QUERY_PROPERTY) {
                            $outputAttributes->addComponentAttributeValue($key, $value);
                        }
                    }
                }
                break;
            case
            self::EMAIL_URI:
                foreach ($this->getDokuwikiUrl()->getQueryParameters() as $key => $value) {
                    if (!in_array($key, self::EMAIL_VALID_PARAMETERS)) {
                        $outputAttributes->addComponentAttributeValue($key, $value);
                    }
                }
                break;
        }


        global $conf;

        /**
         * Get the url
         */
        $url = $this->getUrl();
        if (!empty($url)) {
            $outputAttributes->addHtmlAttributeValue("href", $url);
        }


        /**
         * Processing by type
         */
        switch ($this->getUriType()) {
            case self::INTERWIKI_URI:

                // normal link for the `this` wiki
                if ($this->getWiki() != "this") {
                    PluginUtility::getSnippetManager()->attachCssSnippetForSlot(self::INTERWIKI_URI);
                }
                /**
                 * Target
                 */
                $interWikiConf = $conf['target']['interwiki'];
                if (!empty($interWikiConf)) {
                    $outputAttributes->addHtmlAttributeValue('target', $interWikiConf);
                    $outputAttributes->addHtmlAttributeValue('rel', 'noopener');
                }
                $outputAttributes->addClassName(self::getHtmlClassInterWikiLink());
                $wikiClass = "iw_" . preg_replace('/[^_\-a-z0-9]+/i', '_', $this->getWiki());
                $outputAttributes->addClassName($wikiClass);
                if (!$this->wikiExists()) {
                    $outputAttributes->addClassName(self::getHtmlClassNotExist());
                    $outputAttributes->addHtmlAttributeValue("rel", 'nofollow');
                }

                break;
            case self::WIKI_URI:
                /**
                 * Derived from {@link Doku_Renderer_xhtml::internallink()}
                 */
                // https://www.dokuwiki.org/config:target
                $target = $conf['target']['wiki'];
                if (!empty($target)) {
                    $outputAttributes->addHtmlAttributeValue('target', $target);
                }
                /**
                 * Internal Page
                 */
                $linkedPage = $this->getInternalPage();
                $outputAttributes->addHtmlAttributeValue("data-wiki-id", $linkedPage->getDokuwikiId());


                if (!$linkedPage->exists()) {

                    /**
                     * Red color
                     */
                    $outputAttributes->addClassName(self::getHtmlClassNotExist());
                    $outputAttributes->addHtmlAttributeValue("rel", 'nofollow');

                } else {

                    /**
                     * Internal Link Class
                     */
                    $outputAttributes->addClassName(self::getHtmlClassInternalLink());

                    /**
                     * Link Creation
                     * Do we need to set the title or the tooltip
                     * Processing variables
                     */
                    $acronym = "";

                    /**
                     * Preview tooltip
                     */
                    $previewConfig = PluginUtility::getConfValue(self::CONF_PREVIEW_LINK, self::CONF_PREVIEW_LINK_DEFAULT);
                    $preview = $outputAttributes->getBooleanValueAndRemoveIfPresent(self::PREVIEW_ATTRIBUTE, $previewConfig);
                    if ($preview) {
                        syntax_plugin_combo_tooltip::addToolTipSnippetIfNeeded();
                        $tooltipHtml = <<<EOF
<h3>{$linkedPage->getNameOrDefault()}</h3>
<p>{$linkedPage->getDescriptionOrElseDokuWiki()}</p>
EOF;
                        $dataAttributeNamespace = Bootstrap::getDataNamespace();
                        $outputAttributes->addHtmlAttributeValue("data{$dataAttributeNamespace}-toggle", "tooltip");
                        $outputAttributes->addHtmlAttributeValue("data{$dataAttributeNamespace}-placement", "top");
                        $outputAttributes->addHtmlAttributeValue("data{$dataAttributeNamespace}-html", "true");
                        $outputAttributes->addHtmlAttributeValue("title", $tooltipHtml);
                    }

                    /**
                     * Low quality Page
                     * (It has a higher priority than preview and
                     * the code comes then after)
                     */
                    $pageProtectionAcronym = strtolower(PageProtection::ACRONYM);
                    if ($linkedPage->isLowQualityPage()) {

                        /**
                         * Add a class to style it differently
                         * (the acronym is added to the description, later)
                         */
                        $acronym = LowQualityPage::LOW_QUALITY_PROTECTION_ACRONYM;
                        $lowerCaseLowQualityAcronym = strtolower(LowQualityPage::LOW_QUALITY_PROTECTION_ACRONYM);
                        $outputAttributes->addClassName(LowQualityPage::CLASS_NAME . "-combo");
                        $snippetLowQualityPageId = $lowerCaseLowQualityAcronym;
                        PluginUtility::getSnippetManager()->attachCssSnippetForSlot($snippetLowQualityPageId);
                        /**
                         * Note The protection does occur on Javascript level, not on the HTML
                         * because the created page is valid for a anonymous or logged-in user
                         * Javascript is controlling
                         */
                        if (LowQualityPage::isProtectionEnabled()) {

                            $linkType = LowQualityPage::getLowQualityLinkType();
                            $outputAttributes->addHtmlAttributeValue("data-$pageProtectionAcronym-link", $linkType);
                            $outputAttributes->addHtmlAttributeValue("data-$pageProtectionAcronym-source", $lowerCaseLowQualityAcronym);

                            /**
                             * Low Quality Page protection javascript is only for warning or login link
                             */
                            if (in_array($linkType, [PageProtection::PAGE_PROTECTION_LINK_WARNING, PageProtection::PAGE_PROTECTION_LINK_LOGIN])) {
                                PageProtection::addPageProtectionSnippet();
                            }

                        }
                    }

                    /**
                     * Late publication has a higher priority than
                     * the late publication and the is therefore after
                     * (In case this a low quality page late published)
                     */
                    if ($linkedPage->isLatePublication()) {
                        /**
                         * Add a class to style it differently if needed
                         */
                        $outputAttributes->addClassName(PagePublicationDate::LATE_PUBLICATION_CLASS_NAME . "-combo");
                        if (PagePublicationDate::isLatePublicationProtectionEnabled()) {
                            $acronym = PagePublicationDate::LATE_PUBLICATION_PROTECTION_ACRONYM;
                            $lowerCaseLatePublicationAcronym = strtolower(PagePublicationDate::LATE_PUBLICATION_PROTECTION_ACRONYM);
                            $outputAttributes->addHtmlAttributeValue("data-$pageProtectionAcronym-link", PageProtection::PAGE_PROTECTION_LINK_LOGIN);
                            $outputAttributes->addHtmlAttributeValue("data-$pageProtectionAcronym-source", $lowerCaseLatePublicationAcronym);
                            PageProtection::addPageProtectionSnippet();
                        }

                    }

                    /**
                     * Title (ie tooltip vs title html attribute)
                     */
                    if (!$outputAttributes->hasAttribute("title")) {

                        /**
                         * If this is not a link into the same page
                         */
                        if (!empty($this->getDokuwikiUrl()->getPath())) {
                            $description = $linkedPage->getDescriptionOrElseDokuWiki();
                            if (empty($description)) {
                                // Rare case
                                $description = $linkedPage->getH1OrDefault();
                            }
                            if (!empty($acronym)) {
                                $description = $description . " ($acronym)";
                            }
                            $outputAttributes->addHtmlAttributeValue("title", $description);
                        }

                    }

                }

                break;

            case self::WINDOWS_SHARE_URI:
                // https://www.dokuwiki.org/config:target
                $windowsTarget = $conf['target']['windows'];
                if (!empty($windowsTarget)) {
                    $outputAttributes->addHtmlAttributeValue('target', $windowsTarget);
                }
                $outputAttributes->addClassName("windows");
                break;
            case self::LOCAL_URI:
                break;
            case self::EMAIL_URI:
                $outputAttributes->addClassName(self::getHtmlClassEmailLink());
                break;
            case self::STANDARD_URI:
                if ($conf['relnofollow']) {
                    $outputAttributes->addHtmlAttributeValue("rel", 'nofollow ugc');
                }
                // https://www.dokuwiki.org/config:target
                $externTarget = $conf['target']['extern'];
                if (!empty($externTarget)) {
                    $outputAttributes->addHtmlAttributeValue('target', $externTarget);
                    $outputAttributes->addHtmlAttributeValue("rel", 'noopener');
                }
                if ($this->type === null) {
                    /**
                     * Default class for default external link
                     * To not interfere with other external link style
                     * For instance, {@link \syntax_plugin_combo_share}
                     */
                    $outputAttributes->addClassName(self::getHtmlClassExternalLink());
                }
                break;
            default:
                /**
                 * May be any external link
                 * such as {@link \syntax_plugin_combo_share}
                 */
                break;

        }

        /**
         * An email URL and title
         * may be already encoded because of the vanguard configuration
         *
         * The url is not treated as an attribute
         * because the transformation function encodes the value
         * to mitigate XSS
         *
         */
        if ($this->getUriType() == self::EMAIL_URI) {
            $emailAddress = $this->obfuscateEmail($this->dokuwikiUrl->getPath());
            $outputAttributes->addHtmlAttributeValue("title", $emailAddress);
        }

        /**
         * Return
         */
        return $outputAttributes;


    }

    /**
     * Keep track of the backlinks ie meta['relation']['references']
     * @param Doku_Renderer_metadata $metaDataRenderer
     */
    public
    function handleMetadata(Doku_Renderer_metadata $metaDataRenderer)
    {


    }

    /**
     * Return the type of link from an ID
     *
     * @return string a `TYPE_xxx` constant
     */
    public
    function getUriType(): string
    {
        return $this->uriType;
    }


    /**
     * @return Page - the internal page or an error if the link is not an internal one
     */
    public
    function getInternalPage(): Page
    {
        if ($this->linkedPage == null) {
            if ($this->getUriType() == self::WIKI_URI) {
                // if there is no path, this is the actual page
                $pathOrId = $this->dokuwikiUrl->getPath();

                $this->linkedPage = Page::createPageFromNonQualifiedPath($pathOrId);

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
        switch ($this->getUriType()) {
            case self::WIKI_URI:
                if (!empty($name)) {
                    /**
                     * With the new link syntax class, this is no more possible
                     * because there is an enter and exit state
                     * TODO: create a function to render on DOKU_LEXER_UNMATCHED ?
                     */
                    $name = TemplateUtility::renderStringTemplateForPageId($name, $this->dokuwikiUrl->getPath());
                }
                if (empty($name)) {
                    $name = $this->getInternalPage()->getNameOrDefault();
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
            case self::EMAIL_URI:
                if (empty($name)) {
                    global $conf;
                    $email = $this->dokuwikiUrl->getPath();
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
            case self::INTERWIKI_URI:
                if (empty($name)) {
                    $name = $this->dokuwikiUrl->getPath();
                }
                break;
            case self::LOCAL_URI:
                if (empty($name)) {
                    $name = $this->dokuwikiUrl->getFragment();
                }
                break;
            default:
                return $this->getRef();
        }

        return $name;
    }

    /**
     * @param $title - the value of the title attribute of the anchor
     */
    public
    function setTitle($title)
    {
        $this->title = $title;
    }


    /**
     * @throws ExceptionCombo
     */
    public
    function getUrl()
    {

        switch ($this->getUriType()) {
            case self::WIKI_URI:
                $page = $this->getInternalPage();

                /**
                 * Styling attribute
                 * may be passed via parameters
                 * for internal link
                 * We don't want the styling attribute
                 * in the URL
                 *
                 * We will not overwrite the parameters if this is an dokuwiki
                 * action link (with the `do` property)
                 */
                if ($this->dokuwikiUrl->hasQueryParameter("do")) {

                    $url = wl($page->getDokuwikiId(), $this->dokuwikiUrl->getQueryParameters());

                } else {

                    /**
                     * No parameters by default known
                     */
                    $url = $page->getCanonicalUrl(
                        [],
                        false,
                        DokuwikiUrl::AMPERSAND_URL_ENCODED_FOR_HTML
                    );

                    /**
                     * The search term
                     * Code adapted found at {@link Doku_Renderer_xhtml::internallink()}
                     * We can't use the previous {@link wl function}
                     * because it encode too much
                     */
                    $searchTerms = $this->dokuwikiUrl->getQueryParameter(self::SEARCH_HIGHLIGHT_QUERY_PROPERTY);
                    if ($searchTerms !== null) {
                        $url .= DokuwikiUrl::AMPERSAND_URL_ENCODED_FOR_HTML;
                        PluginUtility::getSnippetManager()->attachCssSnippetForSlot("search");
                        if (is_array($searchTerms)) {
                            /**
                             * To verify, do we really need the []
                             * to get an array in php ?
                             */
                            $searchTermsQuery = [];
                            foreach ($searchTerms as $searchTerm) {
                                $searchTermsQuery[] = "s[]=$searchTerm";
                            }
                            $url .= implode(DokuwikiUrl::AMPERSAND_URL_ENCODED_FOR_HTML, $searchTermsQuery);
                        } else {
                            $url .= "s=$searchTerms";
                        }
                    }


                }
                if ($this->dokuwikiUrl->getFragment() != null) {
                    /**
                     * pageutils (transform a fragment in section id)
                     */
                    $check = false;
                    $url .= '#' . sectionID($this->dokuwikiUrl->getFragment(), $check);
                }
                break;
            case self::INTERWIKI_URI:
                $wiki = $this->wiki;
                $extendedPath = $this->dokuwikiUrl->getPath();
                if ($this->dokuwikiUrl->getFragment() !== null) {
                    $extendedPath .= "#{$this->dokuwikiUrl->getFragment()}";
                }
                $url = $this->interWikiRefToUrl($wiki, $extendedPath);
                break;
            case self::WINDOWS_SHARE_URI:
                $url = str_replace('\\', '/', $this->getRef());
                $url = 'file:///' . $url;
                break;
            case self::EMAIL_URI:
                /**
                 * An email link is `<email>`
                 * {@link Emaillink::connectTo()}
                 * or
                 * {@link PluginTrait::email()
                 */
                // common.php#obfsucate implements the $conf['mailguard']
                $uri = $this->getDokuwikiUrl()->getPath();
                $uri = $this->obfuscateEmail($uri);
                $uri = urlencode($uri);
                $queryParameters = $this->getDokuwikiUrl()->getQueryParameters();
                if (sizeof($queryParameters) > 0) {
                    $uri .= "?";
                    foreach ($queryParameters as $key => $value) {
                        $value = urlencode($value);
                        $key = urlencode($key);
                        if (in_array($key, self::EMAIL_VALID_PARAMETERS)) {
                            $uri .= "$key=$value";
                        }
                    }
                }
                $url = 'mailto:' . $uri;
                break;
            case self::LOCAL_URI:
                $check = false;
                $url = '#' . sectionID($this->ref, $check);
                break;
            case self::STANDARD_URI:
                /**
                 * Default is external
                 * For instance, {@link \syntax_plugin_combo_share} link
                 */
                /**
                 * Authorized scheme only
                 * to not inject code
                 */
                if (is_null($this->authorizedSchemes)) {
                    // https://www.dokuwiki.org/urlschemes
                    $this->authorizedSchemes = getSchemes();
                    $this->authorizedSchemes[] = "whatsapp";
                    $this->authorizedSchemes[] = "mailto";
                }
                if (!in_array($this->schemeUri, $this->authorizedSchemes)) {
                    throw new ExceptionCombo("The scheme ($this->schemeUri) is not authorized as uri");
                } else {
                    $url = $this->ref;
                }
                break;
            default:
                throw new ExceptionCombo("The structure of the reference ($this->ref) is unknown");
        }


        return $url;
    }

    public function getWiki(): ?string
    {
        return $this->wiki;
    }


    public
    function getScheme()
    {
        return $this->schemeUri;
    }

    /**
     * @return bool true if the page should be protected
     */
    private
    function isProtectedLink()
    {
        $protectedLink = false;
        if ($this->getUriType() == self::WIKI_URI) {

            // Low Quality Page protection
            $lqppEnable = PluginUtility::getConfValue(LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE);
            if ($lqppEnable == 1
                && $this->getInternalPage()->isLowQualityPage()) {
                $protectedLink = true;
            }

            if ($protectedLink === false) {
                $latePublicationProtectionEnabled = PluginUtility::getConfValue(PagePublicationDate::CONF_LATE_PUBLICATION_PROTECTION_ENABLE);
                if ($latePublicationProtectionEnabled == 1
                    && $this->getInternalPage()->isLatePublication()) {
                    $protectedLink = true;
                }
            }
        }
        return $protectedLink;
    }


    private
    function wikiExists(): bool
    {
        $wikis = getInterwiki();
        return key_exists($this->wiki, $wikis);
    }

    private
    function obfuscateEmail($email, $inAttribute = true): string
    {
        /**
         * adapted from {@link obfuscate()} in common.php
         */
        global $conf;

        $mailGuard = $conf['mailguard'];
        if ($mailGuard === "hex" && $inAttribute) {
            $mailGuard = "visible";
        }
        switch ($mailGuard) {
            case 'visible' :
                $obfuscate = array('@' => ' [at] ', '.' => ' [dot] ', '-' => ' [dash] ');
                return strtr($email, $obfuscate);

            case 'hex' :
                return Conversion::toHtml($email, true);

            case 'none' :
            default :
                return $email;
        }
    }


    public
    function isRelative(): bool
    {
        return strpos($this->path, ':') !== 0;
    }

    public
    function getDokuwikiUrl(): DokuwikiUrl
    {
        return $this->dokuwikiUrl;
    }


    public
    static function getHtmlClassInternalLink()
    {
        $oldClassName = PluginUtility::getConfValue(self::CONF_USE_DOKUWIKI_CLASS_NAME);
        if ($oldClassName) {
            return "wikilink1";
        } else {
            return "link-internal";
        }
    }

    public
    static function getHtmlClassEmailLink(): string
    {
        $oldClassName = PluginUtility::getConfValue(self::CONF_USE_DOKUWIKI_CLASS_NAME);
        if ($oldClassName) {
            return "mail";
        } else {
            return "link-mail";
        }
    }

    public
    static function getHtmlClassInterWikiLink(): string
    {
        $oldClassName = PluginUtility::getConfValue(self::CONF_USE_DOKUWIKI_CLASS_NAME);
        if ($oldClassName) {
            return "interwiki";
        } else {
            return "link-interwiki";
        }
    }

    public
    static function getHtmlClassExternalLink(): string
    {
        $oldClassName = PluginUtility::getConfValue(self::CONF_USE_DOKUWIKI_CLASS_NAME);
        if ($oldClassName) {
            return "urlextern";
        } else {
            return "link-external";
        }
    }

//FYI: exist in dokuwiki is "wikilink1 but we let the control to the user
    public
    static function getHtmlClassNotExist(): string
    {
        $oldClassName = PluginUtility::getConfValue(self::CONF_USE_DOKUWIKI_CLASS_NAME);
        if ($oldClassName) {
            return "wikilink2";
        } else {
            return self::TEXT_ERROR_CLASS;
        }
    }

    public
    function __toString()
    {
        return $this->ref;
    }

    private
    function getEmailObfuscationConfiguration()
    {
        global $conf;
        return $conf['mailguard'];
    }

    /**
     * @param string $shortcut
     * @param string $reference
     * @return mixed|string
     * Adapted  from {@link Doku_Renderer_xhtml::_resolveInterWiki()}
     * @noinspection DuplicatedCode
     */
    private function interWikiRefToUrl(string &$shortcut, string $reference)
    {

        if ($this->interwiki === null) {
            $this->interwiki = getInterwiki();
        }

        // Get interwiki URL
        if (isset($this->interwiki[$shortcut])) {
            $url = $this->interwiki[$shortcut];
        } elseif (isset($this->interwiki['default'])) {
            $shortcut = 'default';
            $url = $this->interwiki[$shortcut];
        } else {
            // not parsable interwiki outputs '' to make sure string manipulation works
            $shortcut = '';
            $url = '';
        }

        //split into hash and url part
        $hash = strrchr($reference, '#');
        if ($hash) {
            $reference = substr($reference, 0, -strlen($hash));
            $hash = substr($hash, 1);
        }

        //replace placeholder
        if (preg_match('#\{(URL|NAME|SCHEME|HOST|PORT|PATH|QUERY)\}#', $url)) {
            //use placeholders
            $url = str_replace('{URL}', rawurlencode($reference), $url);
            //wiki names will be cleaned next, otherwise urlencode unsafe chars
            $url = str_replace('{NAME}', ($url[0] === ':') ? $reference :
                preg_replace_callback('/[[\\\\\]^`{|}#%]/', function ($match) {
                    return rawurlencode($match[0]);
                }, $reference), $url);
            $parsed = parse_url($reference);
            if (empty($parsed['scheme'])) $parsed['scheme'] = '';
            if (empty($parsed['host'])) $parsed['host'] = '';
            if (empty($parsed['port'])) $parsed['port'] = 80;
            if (empty($parsed['path'])) $parsed['path'] = '';
            if (empty($parsed['query'])) $parsed['query'] = '';
            $url = strtr($url, [
                '{SCHEME}' => $parsed['scheme'],
                '{HOST}' => $parsed['host'],
                '{PORT}' => $parsed['port'],
                '{PATH}' => $parsed['path'],
                '{QUERY}' => $parsed['query'],
            ]);
        } else if ($url != '') {
            // make sure when no url is defined, we keep it null
            // default
            $url = $url . rawurlencode($reference);
        }
        //handle as wiki links
        if ($url[0] === ':') {
            $urlParam = null;
            $id = $url;
            if (strpos($url, '?') !== false) {
                list($id, $urlParam) = explode('?', $url, 2);
            }
            $url = wl(cleanID($id), $urlParam);
            $exists = page_exists($id);
        }
        if ($hash) $url .= '#' . rawurlencode($hash);

        return $url;
    }


}
