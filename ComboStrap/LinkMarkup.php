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
use syntax_plugin_combo_variable;

require_once(__DIR__ . '/PluginUtility.php');

/**
 *
 * @package ComboStrap
 *
 * Parse the ref found in a markup link or media
 * and return an XHTML compliant array
 * with href, style, ... attributes
 *
 *
 */
class LinkMarkup
{


    /**
     * Class added to the type of link
     * Class have styling rule conflict, they are by default not set
     * but this configuration permits to turn it back
     */
    const CONF_USE_DOKUWIKI_CLASS_NAME = "useDokuwikiLinkClassName";
    /**
     * This configuration will set for all internal link
     * the {@link LinkMarkup::PREVIEW_ATTRIBUTE} preview attribute
     */
    const CONF_PREVIEW_LINK = "previewLink";
    const CONF_PREVIEW_LINK_DEFAULT = 0;


    const TEXT_ERROR_CLASS = "text-danger";

    /**
     * The known parameters for an email url
     * The other are styling attribute :)
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



    private MarkupRef $markupRef;

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
         * Url (called ref by dokuwiki)
         */
        $this->markupRef = MarkupRef::createLinkFromRef($ref);


    }

    public static function createFromPageIdOrPath($id): LinkMarkup
    {
        DokuPath::addRootSeparatorIfNotPresent($id);
        return new LinkMarkup($id);
    }

    public static function createFromRef(string $ref): LinkMarkup
    {
        return new LinkMarkup($ref);
    }


    /**
     * @param $uriType
     * @return $this
     */
    public function setUriType($uriType): LinkMarkup
    {
        $this->uriType = $uriType;
        return $this;
    }


    /**
     *
     *
     *
     *
     * @throws ExceptionBadSyntax
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
            case MarkupRef::WIKI_URI:
                if (!$this->markupRef->getUrl()->hasProperty("do")) {
                    foreach ($this->getMarkupRef()->getUrl()->getQuery() as $key => $value) {
                        if ($key !== self::SEARCH_HIGHLIGHT_QUERY_PROPERTY) {
                            $outputAttributes->addComponentAttributeValue($key, $value);
                        }
                    }
                }
                break;
            case
            MarkupRef::EMAIL_URI:
                foreach ($this->getMarkupRef()->getUrl()->getQuery() as $key => $value) {
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
            $outputAttributes->addOutputAttributeValue("href", $url);
        }


        /**
         * Processing by type
         */
        switch ($this->getUriType()) {
            case MarkupRef::INTERWIKI_URI:

                // normal link for the `this` wiki
                if ($this->getWiki() !== "this") {
                    PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot(MarkupRef::INTERWIKI_URI);
                }
                /**
                 * Target
                 */
                $interWikiConf = $conf['target']['interwiki'];
                if (!empty($interWikiConf)) {
                    $outputAttributes->addOutputAttributeValue('target', $interWikiConf);
                    $outputAttributes->addOutputAttributeValue('rel', 'noopener');
                }
                $outputAttributes->addClassName(self::getHtmlClassInterWikiLink());
                $wikiClass = "iw_" . preg_replace('/[^_\-a-z0-9]+/i', '_', $this->getWiki());
                $outputAttributes->addClassName($wikiClass);
                if (!$this->wikiExists()) {
                    $outputAttributes->addClassName(self::getHtmlClassNotExist());
                    $outputAttributes->addOutputAttributeValue("rel", 'nofollow');
                }

                break;
            case MarkupRef::WIKI_URI:
                /**
                 * Derived from {@link Doku_Renderer_xhtml::internallink()}
                 */
                // https://www.dokuwiki.org/config:target
                $target = $conf['target']['wiki'];
                if (!empty($target)) {
                    $outputAttributes->addOutputAttributeValue('target', $target);
                }
                /**
                 * Internal Page
                 */
                $linkedPage = $this->getInternalPage();
                $outputAttributes->addOutputAttributeValue("data-wiki-id", $linkedPage->getDokuwikiId());


                if (!$linkedPage->exists()) {

                    /**
                     * Red color
                     * if not `do=edit`
                     */
                    if (!$this->markupRef->getUrl()->hasProperty("do")) {
                        $outputAttributes->addClassName(self::getHtmlClassNotExist());
                        $outputAttributes->addOutputAttributeValue("rel", 'nofollow');
                    }

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
                        Tooltip::addToolTipSnippetIfNeeded();
                        $tooltipHtml = <<<EOF
<h3>{$linkedPage->getNameOrDefault()}</h3>
<p>{$linkedPage->getDescriptionOrElseDokuWiki()}</p>
EOF;
                        $dataAttributeNamespace = Bootstrap::getDataNamespace();
                        $outputAttributes->addOutputAttributeValue("data{$dataAttributeNamespace}-toggle", "tooltip");
                        $outputAttributes->addOutputAttributeValue("data{$dataAttributeNamespace}-placement", "top");
                        $outputAttributes->addOutputAttributeValue("data{$dataAttributeNamespace}-html", "true");
                        $outputAttributes->addOutputAttributeValue("title", $tooltipHtml);
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
                        PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot($snippetLowQualityPageId);
                        /**
                         * Note The protection does occur on Javascript level, not on the HTML
                         * because the created page is valid for a anonymous or logged-in user
                         * Javascript is controlling
                         */
                        if (LowQualityPage::isProtectionEnabled()) {

                            $linkType = LowQualityPage::getLowQualityLinkType();
                            $outputAttributes->addOutputAttributeValue("data-$pageProtectionAcronym-link", $linkType);
                            $outputAttributes->addOutputAttributeValue("data-$pageProtectionAcronym-source", $lowerCaseLowQualityAcronym);

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
                            $outputAttributes->addOutputAttributeValue("data-$pageProtectionAcronym-link", PageProtection::PAGE_PROTECTION_LINK_LOGIN);
                            $outputAttributes->addOutputAttributeValue("data-$pageProtectionAcronym-source", $lowerCaseLatePublicationAcronym);
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
                        if (!empty($this->getMarkupRef()->getPath())) {
                            $description = $linkedPage->getDescriptionOrElseDokuWiki();
                            if (empty($description)) {
                                // Rare case
                                $description = $linkedPage->getH1OrDefault();
                            }
                            if (!empty($acronym)) {
                                $description = $description . " ($acronym)";
                            }
                            $outputAttributes->addOutputAttributeValue("title", $description);
                        }

                    }

                }

                break;

            case MarkupRef::WINDOWS_SHARE_URI:
                // https://www.dokuwiki.org/config:target
                $windowsTarget = $conf['target']['windows'];
                if (!empty($windowsTarget)) {
                    $outputAttributes->addOutputAttributeValue('target', $windowsTarget);
                }
                $outputAttributes->addClassName("windows");
                break;
            case MarkupRef::LOCAL_URI:
                break;
            case MarkupRef::EMAIL_URI:
                $outputAttributes->addClassName(self::getHtmlClassEmailLink());
                break;
            case MarkupRef::WEB_URI:
                if ($conf['relnofollow']) {
                    $outputAttributes->addOutputAttributeValue("rel", 'nofollow ugc');
                }
                // https://www.dokuwiki.org/config:target
                $externTarget = $conf['target']['extern'];
                if (!empty($externTarget)) {
                    $outputAttributes->addOutputAttributeValue('target', $externTarget);
                    $outputAttributes->addOutputAttributeValue("rel", 'noopener');
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
        if ($this->getUriType() == MarkupRef::EMAIL_URI) {
            $emailAddress = $this->obfuscateEmail($this->markupRef->getPath());
            $outputAttributes->addOutputAttributeValue("title", $emailAddress);
        }

        /**
         * Return
         */
        return $outputAttributes;


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
            if ($this->getUriType() == MarkupRef::WIKI_URI) {
                // if there is no path, this is the actual page
                $path = $this->markupRef->getPath();
                $this->linkedPage = Page::createPageFromPathObject($path);

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

    /**
     * The label inside the anchor tag if there is none
     * @param false $navigation
     * @return string|null
     */
    public function getLabel(bool $navigation = false): ?string
    {

        switch ($this->getUriType()) {
            case MarkupRef::WIKI_URI:
                if ($navigation) {
                    return $this->getInternalPage()->getNameOrDefault();
                } else {
                    return $this->getInternalPage()->getTitleOrDefault();
                }

            case MarkupRef::EMAIL_URI:

                global $conf;
                $email = $this->markupRef->getPath();
                switch ($conf['mailguard']) {
                    case 'none' :
                        return $email;
                    case 'visible' :
                    default :
                        $obfuscate = array('@' => ' [at] ', '.' => ' [dot] ', '-' => ' [dash] ');
                        return strtr($email, $obfuscate);
                }
            case MarkupRef::INTERWIKI_URI:
                return $this->markupRef->getPath();
            case MarkupRef::LOCAL_URI:
                return $this->markupRef->getFragment();
            default:
                return $this->getRef();
        }
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
     * @throws ExceptionBadSyntax
     * @var string $targetEnvironmentAmpersand
     * By default, all data are encoded
     * at {@link TagAttributes::encodeToHtmlValue()}
     * therefore the default is non-encoded
     *
     */
    public function getUrl()
    {

        switch ($this->getUriType()) {
            case MarkupRef::WIKI_URI:
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
                if ($this->markupRef->getUrl()->hasProperty("do")) {

                    $absoluteUrl = Site::shouldUrlBeAbsolute();
                    $url = wl(
                        $page->getDokuwikiId(),
                        $this->markupRef->getUrl()->getQuery(),
                        $absoluteUrl
                    );

                } else {

                    /**
                     * No parameters by default known
                     */
                    $url = $page->getCanonicalUrl(
                        [],
                        false
                    );

                    /**
                     * The search term
                     * Code adapted found at {@link Doku_Renderer_xhtml::internallink()}
                     * We can't use the previous {@link wl function}
                     * because it encode too much
                     */
                    try {
                        $searchTerms = $this->markupRef->getUrl()->getQueryPropertyValue(self::SEARCH_HIGHLIGHT_QUERY_PROPERTY);
                        $url .= Url::AMPERSAND_CHARACTER;
                        PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot("search-hit");
                        if (is_array($searchTerms)) {
                            /**
                             * To verify, do we really need the []
                             * to get an array in php ?
                             */
                            $searchTermsQuery = [];
                            foreach ($searchTerms as $searchTerm) {
                                $searchTermsQuery[] = "s[]=$searchTerm";
                            }
                            $url .= implode(Url::AMPERSAND_CHARACTER, $searchTermsQuery);
                        } else {
                            $url .= "s=$searchTerms";
                        }
                    } catch (ExceptionNotFound $e) {
                        // ok
                    }


                }
                try {
                    $fragment = $this->markupRef->getUrl()->getFragment();
                    /**
                     * pageutils (transform a fragment in section id)
                     */
                    $check = false;
                    $url .= '#' . sectionID($fragment, $check);
                } catch (ExceptionNotFound $e) {
                    // ok no fragment
                }

                break;
            case MarkupRef::INTERWIKI_URI:
                $wiki = $this->wiki;
                $extendedPath = $this->markupRef->getPath();
                try {
                    $fragment = $this->markupRef->getUrl()->getFragment();
                    $extendedPath .= "#$fragment";
                } catch (ExceptionNotFound $e) {
                    // ok no fragment
                }
                $url = InterWiki::createFrom($wiki, $extendedPath);
                break;
            case MarkupRef::WINDOWS_SHARE_URI:
                $url = str_replace('\\', '/', $this->getRef());
                $url = 'file:///' . $url;
                break;
            case MarkupRef::EMAIL_URI:
                /**
                 * An email link is `<email>`
                 * {@link Emaillink::connectTo()}
                 * or
                 * {@link PluginTrait::email()
                 */
                // common.php#obfsucate implements the $conf['mailguard']
                $uri = $this->getMarkupRef()->getPath();
                $uri = $this->obfuscateEmail($uri);
                $uri = urlencode($uri);
                $queryParameters = $this->getMarkupRef()->getQueryParameters();
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
            case MarkupRef::LOCAL_URI:
                $check = false;
                $url = '#' . sectionID($this->ref, $check);
                break;
            case MarkupRef::WEB_URI:
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
                    throw new ExceptionBadSyntax("The scheme ($this->schemeUri) is not authorized as uri");
                } else {
                    $url = $this->ref;
                }
                break;
            case MarkupRef::VARIABLE_URI:
                throw new ExceptionBadSyntax("A template variable uri ($this->ref) can not give back an url, it should be first be replaced");
            default:
                throw new ExceptionBadSyntax("The structure of the reference ($this->ref) is unknown");
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


    /**
     * @return bool
     * @deprecated should not be here ref does not have the notion of relative
     */
    public
    function isRelative(): bool
    {
        return strpos($this->ref, DokuPath::PATH_SEPARATOR) !== 0;
    }

    public
    function getMarkupRef(): MarkupRef
    {
        return $this->markupRef;
    }


    public
    static function getHtmlClassInternalLink(): string
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




}
