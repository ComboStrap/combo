<?php

namespace ComboStrap;


use Exception;
use syntax_plugin_combo_container;

class FetcherPage extends FetcherAbs implements FetcherSource
{

    use FetcherTraitLocalPath;

    const CANONICAL = "page";

    const PAGE_CORE_AREA = "page-core";
    const PAGE_SIDE_AREA = "page-side";
    const PAGE_HEADER_AREA = "page-header";
    const PAGE_FOOTER_AREA = "page-footer";
    const PAGE_MAIN_AREA = "page-main";
    const MAIN_SIDE_AREA = "main-side";
    const MAIN_CONTENT_AREA = "main-content";
    const MAIN_HEADER_AREA = "main-header";
    const MAIN_FOOTER_AREA = "main-footer";
    const LAYOUT_ELEMENTS = [
        self::PAGE_CORE_AREA,
        self::PAGE_SIDE_AREA,
        self::PAGE_HEADER_AREA,
        self::PAGE_MAIN_AREA,
        self::PAGE_FOOTER_AREA,
        self::MAIN_HEADER_AREA,
        self::MAIN_CONTENT_AREA,
        self::MAIN_SIDE_AREA,
        self::MAIN_FOOTER_AREA,
    ];
    const DATA_LAYOUT_CONTAINER_ATTRIBUTE = "data-layout-container";
    const DATA_EMPTY_ACTION_ATTRIBUTE = "data-empty-action";
    const UTF_8_CHARSET_VALUE = "utf-8";
    const VIEWPORT_RESPONSIVE_VALUE = "width=device-width,initial-scale=1";
    const APPLE_TOUCH_ICON_REL_VALUE = "apple-touch-icon";
    const POSITION_RELATIVE_CLASS = "position-relative";
    private string $requestedLayout;


    public static function createPageFetcherFromRequestedPage(): FetcherPage
    {
        return self::createPageFetcherFromPath(WikiPath::createPagePathFromRequestedPage());
    }

    private static function createPageFetcherFromPath(WikiPath $path): FetcherPage
    {
        $fetcherPage = new FetcherPage();
        $fetcherPage->setRequestedPath($path);
        return $fetcherPage;
    }

    /**
     * @param Url|null $url
     * @return Url
     *
     * Note: The fetch url is the {@link FetcherCache keyCache}
     */
    function getFetchUrl(Url $url = null): Url
    {
        /**
         * Overwrite default fetcher endpoint
         * that is {@link UrlEndpoint::createFetchUrl()}
         */
        $url = UrlEndpoint::createDokuUrl();
        $url = parent::getFetchUrl($url);
        try {
            $url->addQueryParameter(PageLayout::PROPERTY_NAME, $this->getRequestedLayout());
        } catch (ExceptionNotFound $e) {
            // no requested layout
        }
        $this->addLocalPathParametersToFetchUrl($url, DokuwikiId::DOKUWIKI_ID_ATTRIBUTE);;
        return $url;
    }


    public static function createPageFetcherFromPage(PageFragment $pageFragment): FetcherPage
    {
        return self::createPageFetcherFromPath($pageFragment->getPath());
    }

    /**
     *
     * @throws ExceptionNotFound - if the main markup page was not found
     */
    public function getFetchPath(): LocalPath
    {

        /**
         * Request environment
         */
        $this->setRequestEnvironment();

        /**
         * We first collect the depend file to
         * check if we can use the cache
         */
        $requestedPage = PageFragment::createPageFromPathObject($this->getOriginalPath());
        $layoutName = PageLayout::createFromPage($requestedPage)->getValueOrDefault();

        $layoutDirectory = WikiPath::createWikiPath(":layout:$layoutName:", WikiPath::COMBO_DRIVE);
        if (!FileSystems::exists($layoutDirectory)) {
            throw new ExceptionRuntimeInternal("The layout directory ($layoutName) does not exist at $layoutDirectory", self::CANONICAL);
        }
        $layoutCssPath = $layoutDirectory->resolve("$layoutName.css");
        $layoutJsPath = $layoutDirectory->resolve("$layoutName.js");
        $htmlTemplatePath = $layoutDirectory->resolve("$layoutName.html");
        $layoutJsonPath = $layoutDirectory->resolve("$layoutName.json");

        $cache = FetcherCache::createFrom($this)
            ->addFileDependency($layoutCssPath)
            ->addFileDependency($layoutJsPath)
            ->addFileDependency($htmlTemplatePath)
            ->addFileDependency($layoutJsonPath);

        try {
            $domDocument = $this->htmlTemplatePathToHtmlDom($htmlTemplatePath);
        } catch (ExceptionBadSyntax $e) {
            throw new ExceptionRuntimeInternal("The Html template layout ($htmlTemplatePath) is not valid. Error: {$e->getMessage()}", self::CANONICAL, 1, $e);
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntimeInternal("The Html template layout ($htmlTemplatePath) does not exists", self::CANONICAL);
        }

        $htmlOutputByAreaName = [];
        $pageElements = $this->buildAndGetPageElements($domDocument);


        /**
         * Run the main slot
         * Get the HTML fragment
         * The first one should be the main because it has the frontmatter
         */
        $mainElement = $pageElements[self::MAIN_CONTENT_AREA];
        try {
            /**
             * The {@link FetcherPageFragment::getFetchPath() Get fetch path}
             * will start the rendering if there is no HTML path
             * or the cache is not fresh
             */
            $path = $mainElement->getPageFragmentFetcher()->getFetchPath();
            $cache->addFileDependency($path);
        } catch (ExceptionNotFound $e) {
            // it should be found
            throw new ExceptionNotFound("The main page markup document was not found. Error:{$e->getMessage()}", self::CANONICAL);
        }

        /**
         * Run the secondary slots
         */
        foreach ($pageElements as $elementId => $pageElement) {
            if ($elementId === self::MAIN_CONTENT_AREA) {
                // already added just below
                continue;
            }
            try {
                $cache->addFileDependency($pageElement->getPageFragmentFetcher()->getFetchPath());
            } catch (ExceptionNotFound $e) {
                // no fetcher or container
            }
        }

        /**
         * Do we create the page or return the cache
         */
        if ($cache->isCacheUsable()) {
            return $cache->getFile();
        }

        /**
         * Creating the HTML document
         *
         */
        foreach ($pageElements as $pageElement) {


            $domElement = $pageElement->getDomElement();

            /**
             * Layout Container
             * Page Header and Footer have a bar that permits to set the layout container value
             *
             * The page core does not have any
             * It's by default contained for all layout
             * generally applied on the page-core element ie
             * <div id="page-core" data-layout-container=>
             */
            if ($domElement->hasAttribute(self::DATA_LAYOUT_CONTAINER_ATTRIBUTE)) {
                $domElement->removeAttribute(self::DATA_LAYOUT_CONTAINER_ATTRIBUTE);
                $container = PluginUtility::getConfValue(syntax_plugin_combo_container::DEFAULT_LAYOUT_CONTAINER_CONF, syntax_plugin_combo_container::DEFAULT_LAYOUT_CONTAINER_DEFAULT_VALUE);
                $domElement->addClass(syntax_plugin_combo_container::getClassName($container));
            }


            /**
             * Relative positioning is important for the positioning of the pagetools (page-core), edit button, ...
             * for absolutely positioning at the left corner of the viewport (message, tool, ...)
             */
            $domElement->addClass(self::POSITION_RELATIVE_CLASS);

            /**
             * Rendering
             */
            if (!$pageElement->isSlot()) {
                // no rendering for container area, this is a parent
                continue;
            }


            try {

                $fetcher = $pageElement->getPageFragmentFetcher();

            } catch (ExceptionNotFound $e) {

                /**
                 * no fragment (page side for instance)
                 * remove or empty ?
                 *   * remove allows to not have any empty node but it may break css rules
                 *   * empty permits not break any css rules (grid may be broken for instance)
                 */
                $action = $domElement->getAttributeOrDefault(self::DATA_EMPTY_ACTION_ATTRIBUTE, "none");
                switch ($action) {
                    case "remove":
                        $domElement->remove();
                        break;
                    case "none":
                        // the empty node will stay in the page
                        break;
                    default:
                        LogUtility::internalError("The value ($action) of the attribute (" . self::DATA_EMPTY_ACTION_ATTRIBUTE . ") is unknown", self::CANONICAL);
                }
                continue;

            }

            /**
             * We don't load / add the HTML string in the actual DOM document
             * to no add by-effect, corrections during loading and writing
             *
             * We add a template variable, we save the HTML in a array
             * And replace them after the loop
             */
            $layoutVariable = $pageElement->getVariableName();
            $htmlOutputByAreaName[$layoutVariable] = $fetcher->getFetchPathAsHtmlString();
            $domElement->appendTextNode('$' . $layoutVariable);

        }


        /**
         * Html
         */
        try {
            $html = $domDocument->querySelector("html");
        } catch (ExceptionBadSyntax|ExceptionNotFound $e) {
            throw new ExceptionRuntimeInternal("The template ($htmlTemplatePath) does not have a html element");
        }

        $langValue = Lang::createForPage($requestedPage)->getValueOrDefault();
        global $lang;
        $langDirection = $lang['direction'];
        $html
            ->setAttribute("lang", $langValue)
            ->setAttribute("dir", $langDirection);
        /**
         * Not Xhtml bedcause it does not support boolean attribute without any value
         *  ->setAttribute("xmlns", "http://www.w3.org/1999/xhtml")
         *  ->setAttribute("xml:lang", $langValue)
         */
        $this->setRemFontSizeToHtml($html);

        /**
         * Head
         */
        try {
            $head = $domDocument->querySelector("head");
        } catch (ExceptionBadSyntax|ExceptionNotFound $e) {
            throw new ExceptionRuntimeInternal("The template ($htmlTemplatePath) does not have a head element");
        }
        $this->checkCharSetMeta($head);
        $this->checkViewPortMeta($head);
        $this->addPageIconMeta($head);
        $this->addTitleMeta($head);

        /**
         * Snippet (in header)
         * Css and Js from the layout if any
         *
         * Note that Header may be added during rendering and must be
         * then called after rendering
         */
        $this->addHeadElements($head, $layoutCssPath, $layoutJsPath);

        /**
         * Body
         * {@link tpl_classes} will add the dokuwiki class.
         * See https://www.dokuwiki.org/devel:templates#dokuwiki_class
         * dokuwiki__top ID is needed for the "Back to top" utility
         * used also by some plugins
         */
        $tplClasses = tpl_classes();
        try {
            $layoutClass = StyleUtility::addComboStrapSuffix("layout-$layoutName");
            $domDocument->querySelector("body")
                ->addClass($tplClasses)
                ->addClass(self::POSITION_RELATIVE_CLASS)
                ->addClass($layoutClass);
        } catch (ExceptionBadSyntax|ExceptionNotFound $e) {
            throw new ExceptionRuntimeInternal("The template ($htmlTemplatePath) does not have a body element");
        }


        if (sizeof($htmlOutputByAreaName) === 0) {
            LogUtility::internalError("No slot was rendered");
        }


        /**
         * We save as XML because we strive to be XML compliant (ie XHTML)
         * And we want to load it as XML to check the XHTML namespace (ie xmlns)
         */
        $htmlBodyDocumentString = $domDocument->toHtml();
        $finalHtmlBodyString = Template::create($htmlBodyDocumentString)->setProperties($htmlOutputByAreaName)->render();

        /**
         * DocType is required by bootstrap
         * https://getbootstrap.com/docs/5.0/getting-started/introduction/#html5-doctype
         */
        $finalHtmlBodyString = "<!doctype html>\n$finalHtmlBodyString";
        $cache->storeCache($finalHtmlBodyString);

        return $cache->getFile();

    }

    function getFetchPathAsHtmlString(): string
    {

        /**
         * TODO: add return {@link TplUtility::printMessage()}
         */
        return FileSystems::getContent($this->getFetchPath());


    }

    /**
     * @throws ExceptionNotFound
     */
    function getBuster(): string
    {
        return FileSystems::getCacheBuster($this->getOriginalPath());
    }

    public function getMime(): Mime
    {
        return Mime::create(Mime::HTML);
    }

    public function getFetcherName(): string
    {
        return self::CANONICAL;
    }

    /**
     * @throws ExceptionBadSyntax
     */
    public function getFetchPathAsHtmlDom(): XmlDocument
    {
        $content = $this->getFetchPathAsHtmlString();
        return XmlDocument::createXmlDocFromMarkup($content);
    }

    /**
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotFound
     */
    private function htmlTemplatePathToHtmlDom(WikiPath $layoutHtmlPath): XmlDocument
    {
        try {
            $htmlStringLayout = FileSystems::getContent($layoutHtmlPath);
        } catch (ExceptionNotFound $e) {
            throw new ExceptionNotFound("The layout file ($layoutHtmlPath) does not exist at $layoutHtmlPath", self::CANONICAL);
        }
        try {
            return XmlDocument::createHtmlDocFromMarkup($htmlStringLayout);
        } catch (ExceptionBadSyntax $e) {
            throw new ExceptionBadSyntax("The html template file ($layoutHtmlPath) is not valid. Error: {$e->getMessage()}", self::CANONICAL, 1, $e);
        }
    }

    /**
     * @param XmlDocument $htmlBodyDomElement
     * @return PageElement[]
     */
    private function buildAndGetPageElements(XmlDocument $htmlBodyDomElement): array
    {

        $areas = [];
        foreach (self::LAYOUT_ELEMENTS as $elementId) {

            /**
             * If the id is not in the html template we don't show it
             */
            try {
                $domElement = $htmlBodyDomElement->querySelector("#$elementId");
            } catch (ExceptionBadSyntax $e) {
                LogUtility::internalError("The selector should not have a bad syntax");
                continue;
            } catch (ExceptionNotFound $e) {
                continue;
            }

            $areas[$elementId] = new PageElement($domElement, $this);

        }
        return $areas;

    }

    public function getRequestedPath(): WikiPath
    {
        return $this->getOriginalPath();
    }

    public function setRequestedLayout(string $layoutValue): FetcherPage
    {
        $this->requestedLayout = $layoutValue;
        return $this;
    }

    private function getJsonConfigurations($layoutJsonPath): array
    {
        try {
            $jsonString = FileSystems::getContent($layoutJsonPath);
        } catch (ExceptionNotFound $e) {
            // The layout file ($layoutJsonPath) does not exist at $layoutJsonPath", self::CANONICAL, 1, $e);
            return [];
        }
        try {
            $json = Json::createFromString($jsonString);
        } catch (ExceptionBadSyntax $e) {
            throw new ExceptionRuntimeInternal("The layout file ($layoutJsonPath) could not be loaded as json. Error: {$e->getMessage()}", self::CANONICAL, 1, $e);
        }
        return $json->toArray();
    }

    /**
     * @throws ExceptionNotFound
     */
    private function getRequestedLayout(): string
    {
        if (!isset($this->requestedLayout)) {
            throw new ExceptionNotFound("No requested layout");
        }
        return $this->requestedLayout;
    }

    /**
     * @param XmlElement $head
     * @return void
     *
     * Responsive meta tag
     */
    private function checkViewPortMeta(XmlElement $head)
    {
        $expectedResponsiveContent = self::VIEWPORT_RESPONSIVE_VALUE;
        try {
            $responsiveMeta = $head->querySelector('meta[name="viewport"]');
            $responsiveActualValue = $responsiveMeta->getAttribute("content");
            if ($responsiveActualValue !== $expectedResponsiveContent) {
                LogUtility::warning("The actual viewport meta ($responsiveActualValue) should be $expectedResponsiveContent");
            }
        } catch (ExceptionBadSyntax|ExceptionNotFound $e) {
            try {
                $head->appendChild(
                    $head->getDocument()
                        ->createElement("meta")
                        ->setAttribute("name", "viewport")
                        ->setAttribute("content", $expectedResponsiveContent)
                );
            } catch (\DOMException $e) {
                throw new ExceptionRuntimeInternal("Bad responsive name meta, should not occur", self::CANONICAL, 1, $e);
            }
        }
    }

    /**
     * Character set
     * Note: avoid using {@link Html::encode() character entities} in your HTML,
     * provided their encoding matches that of the document (generally UTF-8)
     */
    private function checkCharSetMeta(XmlElement $head)
    {
        $charsetValue = self::UTF_8_CHARSET_VALUE;
        try {
            $metaCharset = $head->querySelector("meta[charset]");
            $charsetActualValue = $metaCharset->getAttribute("charset");
            if ($charsetActualValue !== $charsetValue) {
                LogUtility::warning("The actual charset ($charsetActualValue) should be $charsetValue");
            }
        } catch (ExceptionBadSyntax|ExceptionNotFound $e) {
            try {
                $metaCharset = $head->getDocument()
                    ->createElement("meta")
                    ->setAttribute("charset", $charsetValue);
                $head->appendChild($metaCharset);
            } catch (\DOMException $e) {
                throw new ExceptionRuntimeInternal("Bad local name meta, should not occur", self::CANONICAL, 1, $e);
            }
        }
    }

    /**
     * @param XmlElement $head
     * @return void
     * Adapted from {@link TplUtility::renderFaviconMetaLinks()}
     */
    private function addPageIconMeta(XmlElement $head)
    {
        $this->addShortcutFavIconInHead($head);
        $this->addIconInHead($head);
        $this->addAppleTouchIconInHead($head);
    }

    /**
     * Add a favIcon.ico
     * @param XmlElement $head
     * @return void
     */
    private function addShortcutFavIconInHead(XmlElement $head)
    {

        $internalFavIcon = WikiPath::createComboResource('images:favicon.ico');
        $iconPaths = array(
            WikiPath::createMediaPathFromId(':favicon.ico'),
            WikiPath::createMediaPathFromId(':wiki:favicon.ico'),
            $internalFavIcon
        );
        try {
            /**
             * @var WikiPath $icoWikiPath - we give wiki paths, we get wiki path
             */
            $icoWikiPath = FileSystems::getFirstExistingPath($iconPaths);
        } catch (ExceptionNotFound $e) {
            LogUtility::internalError("The internal fav icon ($internalFavIcon) should be at minimal found", self::CANONICAL);
            return;
        }

        try {
            $head->appendChild(
                $head->getDocument()
                    ->createElement("link")
                    ->setAttribute("rel", "shortcut icon")
                    ->setAttribute("href", FetcherLocalPath::createFromPath($icoWikiPath)->getFetchUrl()->toAbsoluteUrl()->toString())
            );
        } catch (ExceptionNotFound|\DOMException $e) {
            LogUtility::internalError("The file should be found and the local name should be good. Error: {$e->getMessage()}");
        }

    }

    /**
     * Add Icon Png (16x16 and 32x32)
     * @param XmlElement $head
     * @return void
     */
    private function addIconInHead(XmlElement $head)
    {


        $sizeValues = ["32x32", "16x16"];
        foreach ($sizeValues as $sizeValue) {

            $internalIcon = WikiPath::createComboResource(":images:favicon-$sizeValue.png");
            $iconPaths = array(
                WikiPath::createMediaPathFromId(":favicon-$sizeValue.png"),
                WikiPath::createMediaPathFromId(":wiki:favicon-$sizeValue.png"),
                $internalIcon
            );
            try {
                /**
                 * @var WikiPath $iconPath - to say to the linter that this is a wiki path
                 */
                $iconPath = FileSystems::getFirstExistingPath($iconPaths);
            } catch (ExceptionNotFound $e) {
                LogUtility::internalError("The internal icon ($internalIcon) should be at minimal found", self::CANONICAL);
                continue;
            }
            try {
                $head->appendChild(
                    $head->getDocument()
                        ->createElement("link")
                        ->setAttribute("rel", "icon")
                        ->setAttribute("sizes", $sizeValue)
                        ->setAttribute("type", Mime::PNG)
                        ->setAttribute("href", FetcherLocalPath::createFromPath($iconPath)->getFetchUrl()->toAbsoluteUrl()->toString())
                );
            } catch (ExceptionNotFound|\DOMException $e) {
                LogUtility::internalError("The file ($iconPath) should be found and the local name should be good. Error: {$e->getMessage()}");
            }
        }

    }

    /**
     * Add Apple touch icon
     * @param XmlElement $head
     * @return void
     */
    private function addAppleTouchIconInHead(XmlElement $head)
    {

        $internalIcon = WikiPath::createComboResource(":images:apple-touch-icon.png");
        $iconPaths = array(
            WikiPath::createMediaPathFromId(":apple-touch-icon.png"),
            WikiPath::createMediaPathFromId(":wiki:apple-touch-icon.png"),
            $internalIcon
        );
        try {
            /**
             * @var WikiPath $iconPath - to say to the linter that this is a wiki path
             */
            $iconPath = FileSystems::getFirstExistingPath($iconPaths);
        } catch (ExceptionNotFound $e) {
            LogUtility::internalError("The internal apple icon ($internalIcon) should be at minimal found", self::CANONICAL);
            return;
        }
        try {
            $fetcherLocalPath = FetcherRaster::createImageRasterFetchFromPath($iconPath);
            $sizesValue = "{$fetcherLocalPath->getIntrinsicWidth()}x{$fetcherLocalPath->getIntrinsicHeight()}";
            $head->appendChild(
                $head->getDocument()
                    ->createElement("link")
                    ->setAttribute("rel", self::APPLE_TOUCH_ICON_REL_VALUE)
                    ->setAttribute("sizes", $sizesValue)
                    ->setAttribute("type", Mime::PNG)
                    ->setAttribute("href", $fetcherLocalPath->getFetchUrl()->toAbsoluteUrl()->toString())
            );
        } catch (ExceptionBadArgument|\DOMException $e) {
            LogUtility::internalError("The file ($iconPath) should be found and the local name should be good. Error: {$e->getMessage()}");
        }

    }

    private function addTitleMeta(XmlElement $head)
    {

        try {
            $titleMeta = $head->querySelector("title");
        } catch (ExceptionBadSyntax|ExceptionNotFound $e) {
            try {
                $titleMeta = $head->getDocument()
                    ->createElement("title");
                $head->appendChild($titleMeta);
            } catch (\DOMException $e) {
                throw new ExceptionRuntimeInternal("Bad local name title, should not occur", self::CANONICAL, 1, $e);
            }
        }

        $nodeValue = PageFragment::createPageFromPathObject($this->getRequestedPath());
        $title = PageTitle::createForPage($nodeValue)->getValueOrDefault();
        $titleMeta->setNodeValue($title);

    }

    private function setRequestedPath(WikiPath $requestedPath): FetcherPage
    {
        $this->setOriginalPath($requestedPath);
        return $this;
    }


    private function setRemFontSizeToHtml(XmlElement $html)
    {
        /**
         * Same as {@link TplUtility::CONF_REM_SIZE}
         */
        $remSize = tpl_getConf("remSize", null);
        if ($remSize === null) {
            return;
        }
        try {
            $remSizeInt = DataType::toInteger($remSize);
        } catch (ExceptionBadArgument $e) {
            LogUtility::error("The rem size configuration value ($remSize) is not an integer. Error:{$e->getMessage()}", self::CANONICAL);
            return;
        }
        $html->addStyle("font-size", "{$remSizeInt}px");

    }

    private function addHeadElements(XmlElement $head, WikiPath $layoutCssPath, WikiPath $layoutJsPath)
    {


        /**
         * Bootstrap meta-headers function registration
         */
        TplUtility::registerHeaderHandler();

        /**
         * Add the layout js and css first
         */
        $snippetManager = PluginUtility::getSnippetManager();
        try {
            $content = FileSystems::getContent($layoutCssPath);
            $snippetManager->attachCssInternalStylesheetForRequest(self::CANONICAL, $content);
        } catch (ExceptionNotFound $e) {
            // no css found, not a problem
        }
        if (FileSystems::exists($layoutJsPath)) {
            $snippetManager->attachInternalJavascriptFromPathForRequest(self::CANONICAL, $layoutJsPath);
        }

        /**
         * Start the meta headers
         */
        ob_start();
        try {
            /**
             * The request environment variable are needed
             * by tpl_metaheaders
             */
            $this->setRequestEnvironment();
            tpl_metaheaders();
            $htmlHeaders = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        try {
            $headerDocument = XmlDocument::createHtmlDocFromMarkup("<div>$htmlHeaders</div>");
        } catch (ExceptionBadSyntax $e) {
            LogUtility::error("The created meta html header string could not be read. Error:{$e->getMessage()}", self::CANONICAL);
            return;
        }
        $headers = $headerDocument->getElement()->getChildrenElement();
        foreach ($headers as $childElement) {
            $head->appendChild($childElement);
        }

    }

    private function setRequestEnvironment()
    {
        WikiRequest::create()
            ->setNewAct("show")
            ->setNewRunningId($this->getRequestedPath()->getWikiId())
            ->setNewRequestedId($this->getRequestedPath()->getWikiId());
    }


}
