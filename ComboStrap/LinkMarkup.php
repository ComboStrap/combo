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


use Doku_Renderer_xhtml;
use dokuwiki\Extension\PluginTrait;
use dokuwiki\Utf8\Conversion;
use syntax_plugin_combo_link;


/**
 *
 * @package ComboStrap
 *
 * Parse the ref found in a markup link
 * and return an {@link LinkMarkup::toAttributes()} array for an anchor (a)
 * with href, style, ... attributes
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


    /**
     * Highlight Key
     * Adding this property to the internal query will highlight the words
     *
     * See {@link html_hilight}
     */
    const SEARCH_HIGHLIGHT_QUERY_PROPERTY = "s";
    const DATA_WIKI_ID = "data-wiki-id";

    /**
     * For styling on the anchor tag (ie a)
     */
    public const ANCHOR_HTML_SNIPPET_ID = "anchor-branding";


    private MarkupRef $markupRef;


    private TagAttributes $stylingAttributes;

    /**
     * Link constructor.
     * @param String $ref
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotFound
     */
    public function __construct(string $ref)
    {

        $this->stylingAttributes = TagAttributes::createEmpty(syntax_plugin_combo_link::TAG);

        $this->markupRef = MarkupRef::createLinkFromRef($ref);

        $this->collectStylingAttributeInUrl();


    }

    public static function createFromPageIdOrPath($id): LinkMarkup
    {
        WikiPath::addRootSeparatorIfNotPresent($id);
        try {
            return new LinkMarkup($id);
        } catch (ExceptionBadArgument|ExceptionBadSyntax|ExceptionNotFound $e) {
            throw new ExceptionRuntime("Internal error: an id should be a good reference");
        }
    }

    /**
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotFound
     */
    public static function createFromRef(string $ref): LinkMarkup
    {
        return new LinkMarkup($ref);
    }

    public static function getHtmlClassLocalLink(): string
    {
        return "link-local";
    }


    /**
     *
     * @throws ExceptionNotFound
     */
    public function toAttributes(): TagAttributes
    {

        $outputAttributes = $this->stylingAttributes;


        $url = $this->getMarkupRef()->getUrl();
        $outputAttributes->addOutputAttributeValue("href", $url->toString());

        /**
         * The search term
         * Code adapted found at {@link Doku_Renderer_xhtml::internallink()}
         * We can't use the previous {@link wl function}
         * because it encode too much
         */
        if ($url->hasProperty(self::SEARCH_HIGHLIGHT_QUERY_PROPERTY)) {
            PluginUtility::getSnippetManager()->attachCssInternalStyleSheet("search-hit");
        }

        /**
         * Default Link Color
         * Saturation and lightness comes from the
         * Note:
         *   * blue color of Bootstrap #0d6efd s: 98, l: 52
         *   * blue color of twitter #1d9bf0 s: 88, l: 53
         *   * reddit gray with s: 16, l : 31
         *   * the text is s: 11, l: 15
         * We choose the gray/tone rendering to be close to black
         * the color of the text
         */
        $primaryColor = Site::getPrimaryColor();
        if (Site::isBrandingColorInheritanceEnabled() && $primaryColor !== null) {

            $primaryColorText = Site::getPrimaryColorForText();
            $primaryColorHoverText = Site::getPrimaryColorTextHover();
            if ($primaryColorText !== null) {
                $aCss = <<<EOF
main a {
    color: {$primaryColorText->toRgbHex()};
}
main a:hover {
    color: {$primaryColorHoverText->toRgbHex()};
}
EOF;
                SnippetSystem::getFromContext()->attachCssInternalStylesheet(self::ANCHOR_HTML_SNIPPET_ID, $aCss);
            }
        }


        global $conf;


        /**
         * Processing by type
         */
        switch ($this->getMarkupRef()->getSchemeType()) {
            case MarkupRef::INTERWIKI_URI:
                try {
                    $interWiki = $this->getMarkupRef()->getInterWiki();
                } catch (ExceptionNotFound $e) {
                    LogUtility::internalError("The interwiki should be available. We were unable to create the link attributes.");
                    return $outputAttributes;
                }
                // normal link for the `this` wiki
                if ($interWiki->getWiki() !== "this") {
                    PluginUtility::getSnippetManager()->attachCssInternalStyleSheet(MarkupRef::INTERWIKI_URI);
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
                $wikiClass = "iw_" . preg_replace('/[^_\-a-z0-9]+/i', '_', $interWiki->getWiki());
                $outputAttributes->addClassName($wikiClass);
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
                try {
                    $dokuPath = $this->getMarkupRef()->getPath();
                } catch (ExceptionNotFound $e) {
                    throw new ExceptionNotFound("We were unable to process the internal link dokuwiki id on the link. The path was not found. Error: {$e->getMessage()}");
                }
                $page = MarkupPath::createPageFromPathObject($dokuPath);
                $outputAttributes->addOutputAttributeValue(self::DATA_WIKI_ID, $dokuPath->getWikiId());


                if (!FileSystems::exists($dokuPath)) {

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
                    $previewConfig = SiteConfig::getConfValue(self::CONF_PREVIEW_LINK, self::CONF_PREVIEW_LINK_DEFAULT);
                    $preview = $outputAttributes->hasComponentAttributeAndRemove(self::PREVIEW_ATTRIBUTE);
                    if ($preview || $previewConfig === 1) {
                        Tooltip::addToolTipSnippetIfNeeded();
                        // The name and not the tooltip because otherwise it would be to lengthy
                        $tooltipHtml = <<<EOF
<h3>{$page->getNameOrDefault()}</h3>
<p>{$page->getDescriptionOrElseDokuWiki()}</p>
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
                    if ($page->isLowQualityPage()) {

                        /**
                         * Add a class to style it differently
                         * (the acronym is added to the description, later)
                         */
                        $acronym = LowQualityPage::LOW_QUALITY_PROTECTION_ACRONYM;
                        $lowerCaseLowQualityAcronym = strtolower(LowQualityPage::LOW_QUALITY_PROTECTION_ACRONYM);
                        $outputAttributes->addClassName(StyleUtility::addComboStrapSuffix(LowQualityPage::CLASS_SUFFIX));
                        $snippetLowQualityPageId = $lowerCaseLowQualityAcronym;
                        PluginUtility::getSnippetManager()->attachCssInternalStyleSheet($snippetLowQualityPageId);
                        /**
                         * Note The protection does occur on Javascript level, not on the HTML
                         * because the created page is valid for a anonymous or logged-in user
                         * Javascript is controlling
                         */
                        if (LowQualityPage::isProtectionEnabled()) {

                            $linkType = LowQualityPage::getLowQualityLinkType();
                            $outputAttributes->addOutputAttributeValue(PageProtection::DATA_PP_LINK, $linkType);
                            $outputAttributes->addOutputAttributeValue(PageProtection::DATA_PP_SOURCE, $lowerCaseLowQualityAcronym);

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
                    if ($page->isLatePublication()) {
                        /**
                         * Add a class to style it differently if needed
                         */
                        $className = StyleUtility::addComboStrapSuffix(PagePublicationDate::LATE_PUBLICATION_CLASS_PREFIX_NAME);
                        $outputAttributes->addClassName($className);
                        if (PagePublicationDate::isLatePublicationProtectionEnabled()) {
                            $outputAttributes->removeOutputAttributeIfPresent(PageProtection::DATA_PP_LINK);
                            $outputAttributes->removeOutputAttributeIfPresent(PageProtection::DATA_PP_SOURCE);
                            $outputAttributes->addOutputAttributeValue(PageProtection::DATA_PP_LINK, PageProtection::PAGE_PROTECTION_LINK_LOGIN);
                            $acronym = PagePublicationDate::LATE_PUBLICATION_PROTECTION_ACRONYM;
                            $lowerCaseLatePublicationAcronym = strtolower(PagePublicationDate::LATE_PUBLICATION_PROTECTION_ACRONYM);
                            $outputAttributes->addOutputAttributeValue(PageProtection::DATA_PP_SOURCE, $lowerCaseLatePublicationAcronym);
                            PageProtection::addPageProtectionSnippet();
                        }

                    }

                    /**
                     * Title (ie tooltip vs title html attribute)
                     */
                    if (!$outputAttributes->hasAttribute("title")) {

                        $description = PageDescription::createForPage($page)->getValueOrDefault();
                        if (!empty($acronym)) {
                            $description = $description . " ($acronym)";
                        }
                        $outputAttributes->addOutputAttributeValue("title", $description);

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
                $outputAttributes->addClassName(self::getHtmlClassLocalLink());
                if (!$outputAttributes->hasAttribute("title")) {
                    $description = ucfirst($this->markupRef->getUrl()->getFragment());
                    if ($description !== "") {
                        $description = str_replace("_", " ", $description);
                        $outputAttributes->addOutputAttributeValue("title", $description);
                    }
                }
                break;
            case MarkupRef::EMAIL_URI:
                $outputAttributes->addClassName(self::getHtmlClassEmailLink());
                /**
                 * An email link is `<email>`
                 * {@link Emaillink::connectTo()}
                 * or
                 * {@link PluginTrait::email()
                 */
                // common.php#obfsucate implements the $conf['mailguard']
                $uri = $url->getPath();
                $uri = $this->obfuscateEmail($uri);
                $uri = urlencode($uri);
                $queryParameters = $url->getQueryProperties();
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
                // replace href
                $outputAttributes->removeOutputAttributeIfPresent("href");
                $outputAttributes->addOutputAttributeValue("href", 'mailto:' . $uri);
                break;
            case MarkupRef::WEB_URI:
                /**
                 * It may be a absolute url
                 * that points to the local website
                 * (case of the {@link \syntax_plugin_combo_permalink}
                 */
                if($url->isExternal()) {

                    if ($conf['relnofollow']) {
                        $outputAttributes->addOutputAttributeValue("rel", 'nofollow ugc');
                    }
                    // https://www.dokuwiki.org/config:target
                    $externTarget = $conf['target']['extern'];
                    if (!empty($externTarget)) {
                        $outputAttributes->addOutputAttributeValue('target', $externTarget);
                        $outputAttributes->addOutputAttributeValue("rel", 'noopener');
                    }
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
        if ($this->getMarkupRef()->getSchemeType() == MarkupRef::EMAIL_URI) {
            $emailAddress = $this->obfuscateEmail($this->markupRef->getUrl()->getPath());
            $outputAttributes->addOutputAttributeValue("title", $emailAddress);
        }


        /**
         * Return
         */
        return $outputAttributes;


    }


    /**
     * The label inside the anchor tag if there is none
     * @param false $navigation
     * @return string
     * @throws ExceptionNotFound|ExceptionBadArgument
     *
     */
    public
    function getDefaultLabel(bool $navigation = false): string
    {

        switch ($this->getMarkupRef()->getSchemeType()) {
            case MarkupRef::WIKI_URI:
                $page = $this->getPage();
                if ($navigation) {
                    return ResourceName::createForResource($page)->getValueOrDefault();
                } else {
                    return PageTitle::createForMarkup($page)->getValueOrDefault();
                }
            case MarkupRef::EMAIL_URI:
                global $conf;
                $email = $this->markupRef->getUrl()->getPath();
                switch ($conf['mailguard']) {
                    case 'none' :
                        return $email;
                    case 'visible' :
                    default :
                        $obfuscate = array('@' => ' [at] ', '.' => ' [dot] ', '-' => ' [dash] ');
                        return strtr($email, $obfuscate);
                }
            case MarkupRef::INTERWIKI_URI:
                try {
                    $path = $this->markupRef->getInterWiki()->toUrl()->getPath();
                    if ($path[0] === "/") {
                        return substr($path, 1);
                    } else {
                        return $path;
                    }
                } catch (ExceptionBadSyntax|ExceptionNotFound $e) {
                    return "interwiki";
                }
            case MarkupRef::LOCAL_URI:
                return $this->markupRef->getUrl()->getFragment();
            default:
                return $this->markupRef->getRef();
        }
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
        return strpos($this->getMarkupRef()->getRef(), WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT) !== 0;
    }

    public
    function getMarkupRef(): MarkupRef
    {
        return $this->markupRef;
    }


    public
    static function getHtmlClassInternalLink(): string
    {
        $oldClassName = SiteConfig::getConfValue(self::CONF_USE_DOKUWIKI_CLASS_NAME);
        if ($oldClassName) {
            return "wikilink1";
        } else {
            return "link-internal";
        }
    }

    public
    static function getHtmlClassEmailLink(): string
    {
        $oldClassName = SiteConfig::getConfValue(self::CONF_USE_DOKUWIKI_CLASS_NAME);
        if ($oldClassName) {
            return "mail";
        } else {
            return "link-mail";
        }
    }

    public
    static function getHtmlClassInterWikiLink(): string
    {
        $oldClassName = SiteConfig::getConfValue(self::CONF_USE_DOKUWIKI_CLASS_NAME);
        if ($oldClassName) {
            return "interwiki";
        } else {
            return "link-interwiki";
        }
    }

    public
    static function getHtmlClassExternalLink(): string
    {
        $oldClassName = SiteConfig::getConfValue(self::CONF_USE_DOKUWIKI_CLASS_NAME);
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
        $oldClassName = SiteConfig::getConfValue(self::CONF_USE_DOKUWIKI_CLASS_NAME);
        if ($oldClassName) {
            return "wikilink2";
        } else {
            return self::TEXT_ERROR_CLASS;
        }
    }

    public
    function __toString()
    {
        return $this->getMarkupRef()->getRef();
    }


    /**
     * @throws ExceptionNotFound
     */
    private
    function getPage(): MarkupPath
    {
        return MarkupPath::createPageFromPathObject($this->getMarkupRef()->getPath());
    }

    /**
     * Styling attribute
     * may be passed via parameters
     * for internal link
     * We don't want the styling attribute
     * in the URL
     */
    private
    function collectStylingAttributeInUrl()
    {


        /**
         * We will not overwrite the parameters if this is an dokuwiki
         * action link (with the `do` property)
         */
        if ($this->markupRef->getUrl()->hasProperty("do")) {
            return;
        }

        /**
         * Add the attribute from the URL
         * if this is not a `do`
         */
        switch ($this->markupRef->getSchemeType()) {
            case MarkupRef::WIKI_URI:
                $showDokuProperty = [self::SEARCH_HIGHLIGHT_QUERY_PROPERTY, DokuWikiId::DOKUWIKI_ID_ATTRIBUTE];
                foreach ($this->getMarkupRef()->getUrl()->getQueryProperties() as $key => $value) {
                    if (!in_array($key, $showDokuProperty)) {
                        $this->getMarkupRef()->getUrl()->removeQueryParameter($key);
                        if (!TagAttributes::isEmptyValue($value)) {
                            $this->stylingAttributes->addComponentAttributeValue($key, $value);
                        } else {
                            $this->stylingAttributes->addEmptyComponentAttributeValue($key);
                        }
                    }
                }
                break;
            case
            MarkupRef::EMAIL_URI:
                foreach ($this->getMarkupRef()->getUrl()->getQueryProperties() as $key => $value) {
                    if (!in_array($key, self::EMAIL_VALID_PARAMETERS)) {
                        $this->stylingAttributes->addComponentAttributeValue($key, $value);
                    }
                }
                break;
        }

    }

    /**
     * @return TagAttributes - the unknown attributes in a url are collected as styling attributes if this not a do query
     * by {@link LinkMarkup::collectStylingAttributeInUrl()}
     */
    public
    function getStylingAttributes(): TagAttributes
    {
        return $this->stylingAttributes;
    }


}
