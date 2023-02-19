<?php

namespace ComboStrap;


/**
 * A page template is the object
 * that generates a HTML page
 * (The templating engine)
 *
 * It's used by Fetcher that creates pages such
 * as {@link FetcherPage}, {@link FetcherMarkupWebcode} or {@link FetcherPageBundler}
 */
class PageTemplate
{

    const CANONICAL = "layout";
    public const MAIN_FOOTER_ELEMENT = "main-footer";
    public const PAGE_SIDE_ELEMENT = "page-side";
    public const MAIN_CONTENT_ELEMENT = "main-content";
    public const PAGE_CORE_ELEMENT = "page-core";
    public const LAYOUT_ELEMENTS = [
        PageTemplate::PAGE_CORE_ELEMENT,
        PageTemplate::PAGE_SIDE_ELEMENT,
        PageTemplate::PAGE_HEADER_ELEMENT,
        PageTemplate::PAGE_MAIN_ELEMENT,
        PageTemplate::PAGE_FOOTER_ELEMENT,
        PageTemplate::MAIN_HEADER_ELEMENT,
        PageTemplate::MAIN_TOC_ELEMENT,
        PageTemplate::MAIN_CONTENT_ELEMENT,
        PageTemplate::MAIN_SIDE_ELEMENT,
        PageTemplate::MAIN_FOOTER_ELEMENT,
        PageTemplate::PAGE_TOOL_ELEMENT
    ];
    public const POSITION_RELATIVE_CLASS = "position-relative";
    public const PAGE_TOOL_ELEMENT = "page-tool";
    public const MAIN_SIDE_ELEMENT = "main-side";
    public const PAGE_FOOTER_ELEMENT = "page-footer";
    public const MAIN_HEADER_ELEMENT = "main-header";
    public const PAGE_MAIN_ELEMENT = "page-main";
    public const MAIN_TOC_ELEMENT = "main-toc";
    public const PAGE_HEADER_ELEMENT = "page-header";
    public const DATA_LAYOUT_CONTAINER_ATTRIBUTE = "data-layout-container";
    public const DATA_EMPTY_ACTION_ATTRIBUTE = "data-empty-action";
    public const UTF_8_CHARSET_VALUE = "utf-8";
    public const VIEWPORT_RESPONSIVE_VALUE = "width=device-width,initial-scale=1";
    public const TASK_RUNNER_ID = "task-runner";
    public const APPLE_TOUCH_ICON_REL_VALUE = "apple-touch-icon";
    public const CONF_REM_SIZE = "remSize";
    public const PRELOAD_TAG = "preload";
    const CONF_PAGE_FOOTER_NAME = "footerSlotPageName";
    const CONF_PAGE_FOOTER_NAME_DEFAULT = "slot_footer";
    const CONF_PAGE_HEADER_NAME = "headerSlotPageName";
    const CONF_PAGE_HEADER_NAME_DEFAULT = "slot_header";
    const CONF_PAGE_MAIN_SIDEKICK_NAME = "sidekickSlotPageName";
    const CONF_PAGE_MAIN_SIDEKICK_NAME_DEFAULT = Site::SLOT_MAIN_SIDE_NAME;
    private string $layoutName;
    private WikiPath $cssPath;
    private WikiPath $jsPath;
    private WikiPath $htmlTemplatePath;
    private XmlDocument $templateDomDocument;

    private string $requestedTitle;

    /**
     * @var PageTemplateElement[]
     */
    private array $pageElements = [];

    private bool $requestedEnableTaskRunner = true;
    private WikiPath $requestedContextPath;
    private Lang $requestedLang;
    private Toc $toc;
    private bool $deleteSocialHeads = false;


    /**
     * @param string $layoutName
     * @throws ExceptionNotFound - if the layout does not exist
     * @throws ExceptionBadSyntax - if the layout html template is not valid
     */
    public function __construct(string $layoutName)
    {

        $this->layoutName = $layoutName;

        $layoutDirectory = WikiPath::createWikiPath(":layout:$this->layoutName:", WikiPath::COMBO_DRIVE);
        $this->cssPath = $layoutDirectory->resolve("$this->layoutName.css");
        $this->jsPath = $layoutDirectory->resolve("$this->layoutName.js");
        $this->htmlTemplatePath = $layoutDirectory->resolve("$this->layoutName.html");
        $this->templateDomDocument = $this->htmlTemplatePathToHtmlDom($this->htmlTemplatePath);

        foreach (PageTemplate::LAYOUT_ELEMENTS as $elementId) {

            /**
             * If the id is not in the html template we don't show it
             */
            try {
                $domElement = $this->templateDomDocument->querySelector("#$elementId");
            } catch (ExceptionBadSyntax $e) {
                LogUtility::internalError("The selector should not have a bad syntax");
                continue;
            } catch (ExceptionNotFound $e) {
                continue;
            }

            $this->pageElements[$elementId] = new PageTemplateElement($this, $domElement);

        }

    }

    /**
     * @throws ExceptionNotFound - if the layout does not exist
     * @throws ExceptionBadSyntax - if the layout html template is not valid
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
     * @throws ExceptionBadSyntax - bad html template
     * @throws ExceptionNotFound - layout not found
     */
    public static function createFromLayoutName(string $layoutName): PageTemplate
    {
        return new PageTemplate($layoutName);
    }

    public static function getPoweredBy(): string
    {
        $domain = PluginUtility::$URL_APEX;
        $version = PluginUtility::$INFO_PLUGIN['version'] . " (" . PluginUtility::$INFO_PLUGIN['date'] . ")";
        $poweredBy = "<div class=\"mx-auto\" style=\"width: 300px;text-align: center;margin-bottom: 1rem\">";
        $poweredBy .= "  <small><i>Powered by <a href=\"$domain\" title=\"ComboStrap " . $version . "\" style=\"color:#495057\">ComboStrap</a></i></small>";
        $poweredBy .= '</div>';
        return $poweredBy;
    }


    /**
     * Add or not the task runner / web bug call
     * @param bool $b
     * @return PageTemplate
     */
    public function setRequestedEnableTaskRunner(bool $b): PageTemplate
    {
        $this->requestedEnableTaskRunner = $b;
        return $this;
    }

    public function getCssPath(): WikiPath
    {
        return $this->cssPath;
    }

    public function getJsPath(): WikiPath
    {
        return $this->jsPath;
    }

    public function getHtmlTemplatePath(): WikiPath
    {
        return $this->htmlTemplatePath;
    }

    /**
     * @return WikiPath from where the markup slot should be searched
     * @throws ExceptionNotFound
     */
    public function getRequestedContextPath(): WikiPath
    {
        if (!isset($this->requestedContextPath)) {
            throw new ExceptionNotFound("A requested context path was not found");
        }
        return $this->requestedContextPath;
    }

    /**
     * @param $mainHtml - the html in the main area
     * @return string - the page as html string (not dom because that's not how works dokuwiki)
     * @throws ExceptionNotFound|ExceptionBadArgument
     */
    public function generateAndGetPageHtmlAsString(string $mainHtml): string
    {

        $executionContext = (ExecutionContext::getActualOrCreateFromEnv())
            ->setExecutingPageTemplate($this);
        try {

            $htmlFragmentByVariables = [];
            try {
                $pageLayoutElement = $this->getMainElement();
                $layoutVariable = $pageLayoutElement->getVariableName();
                $htmlFragmentByVariables[$layoutVariable] = $mainHtml;
                $pageLayoutElement->getDomElement()->insertAdjacentTextNode(Template::VARIABLE_PREFIX . $layoutVariable);
            } catch (ExceptionNotFound $e) {
                // main element is mandatory, an error should have been thrown at build time
                throw new ExceptionRuntimeInternal("Main element was not found", self::CANONICAL, 1, $e);
            }

            /**
             * Creating the HTML document
             */
            $pageLayoutElements = $this->getPageLayoutElements();
            foreach ($pageLayoutElements as $pageElement) {

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
                if ($domElement->hasAttribute(PageTemplate::DATA_LAYOUT_CONTAINER_ATTRIBUTE)) {
                    $domElement->removeAttribute(PageTemplate::DATA_LAYOUT_CONTAINER_ATTRIBUTE);
                    $container = Site::getConfValue(ContainerTag::DEFAULT_LAYOUT_CONTAINER_CONF, ContainerTag::DEFAULT_LAYOUT_CONTAINER_DEFAULT_VALUE);
                    $domElement->addClass(ContainerTag::getClassName($container));
                }


                /**
                 * Rendering
                 */
                if (!$pageElement->isSlot() || $pageElement->isMain()) {
                    /**
                     * No rendering for container area
                     * or for the main (passed as argument)
                     */
                    continue;
                }


                try {

                    $fetcher = $pageElement->getMarkupFetcher();
                    try {
                        $fetcherHtmlString = $fetcher->getFetchString();
                    } catch (\Exception $e) {
                        throw new ExceptionRuntimeInternal($e->getMessage(), self::CANONICAL, 1, $e);
                    }

                    /**
                     * We don't load / add the HTML string in the actual DOM document
                     * to no add by-effect, corrections during loading and writing
                     *
                     * We add a template variable, we save the HTML in a array
                     * And replace them after the loop
                     */
                    $layoutVariable = $pageElement->getVariableName();
                    $htmlFragmentByVariables[$layoutVariable] = $fetcherHtmlString;
                    $domElement->insertAdjacentTextNode(Template::VARIABLE_PREFIX . $layoutVariable);

                } catch (ExceptionNotFound|ExceptionBadArgument $e) {

                    if ($e instanceof ExceptionBadArgument) {
                        if (PluginUtility::isDevOrTest()) {
                            /**
                             * The slot {@link Path} for now should be all {@link WikiPath}
                             */
                            throw new ExceptionRuntimeInternal("Internal Error: the path could not be transformed as Wiki Path, while trying to get the fetcher. Error:{$e->getMessage()}", self::CANONICAL);
                        }
                    }

                    /**
                     * no fetcher fragment (page side for instance)
                     * remove or empty ?
                     *   * remove allows to not have any empty node but it may break css rules
                     *   * empty permits not break any css rules (grid may be broken for instance)
                     */
                    $action = $domElement->getAttributeOrDefault(PageTemplate::DATA_EMPTY_ACTION_ATTRIBUTE, "none");
                    switch ($action) {
                        case "remove":
                            $domElement->remove();
                            break;
                        case "none":
                            // the empty node will stay in the page
                            break;
                        default:
                            LogUtility::internalError("The value ($action) of the attribute (" . PageTemplate::DATA_EMPTY_ACTION_ATTRIBUTE . ") is unknown", self::CANONICAL);
                    }
                    continue;

                }

            }

            /**
             * Html
             */
            try {
                $html = $this->getTemplateDomDocument()->querySelector("html");
            } catch (ExceptionBadSyntax|ExceptionNotFound $e) {
                throw new ExceptionRuntimeInternal("The template ($this->htmlTemplatePath) does not have a html element");
            }

            try {
                $lang = $this->getRequestedLang();
                $langValue = $lang->getValueOrDefault();
                $langDirection = $lang->getDirection();
            } catch (ExceptionNotFound $e) {
                // Site value
                $langValue = Site::getLang();
                $langDirection = Site::getLangDirection();
            }
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
             * Body
             * {@link tpl_classes} will add the dokuwiki class.
             * See https://www.dokuwiki.org/devel:templates#dokuwiki_class
             * dokuwiki__top ID is needed for the "Back to top" utility
             * used also by some plugins
             */
            $tplClasses = tpl_classes();
            try {
                $layoutClass = StyleUtility::addComboStrapSuffix("layout-{$this->getLayoutName()}");
                $bodyElement = $this->getTemplateDomDocument()->querySelector("body")
                    ->addClass($tplClasses)
                    ->addClass(PageTemplate::POSITION_RELATIVE_CLASS)
                    ->addClass($layoutClass);
            } catch (ExceptionBadSyntax|ExceptionNotFound $e) {
                throw new ExceptionRuntimeInternal("The template ($this->htmlTemplatePath) does not have a body element");
            }

            $this->addTaskRunnerImageIfRequested($bodyElement);

            if (sizeof($htmlFragmentByVariables) === 0) {
                LogUtility::internalError("No slot was rendered");
            }

            /**
             * Messages
             */
            $this->addMessages($bodyElement);

            /**
             * Toc
             */
            try {

                $tocId = PageTemplate::MAIN_TOC_ELEMENT;
                $tocElement = $this->getPageElement($tocId)->getDomElement();
                $tocElement->addClass(Toc::getClass());

                $toc = $this->getTocOrDefault();
                $tocVariable = Template::toValidVariableName($tocId);
                $htmlFragmentByVariables[$tocVariable] = $toc->toXhtml();
                $tocElement->insertAdjacentTextNode(Template::VARIABLE_PREFIX . $tocVariable);

            } catch (ExceptionNotFound $e) {
                // no toc
            }

            /**
             * Page Tool
             */
            try {
                /**
                 * Page tool is located relatively to its parent
                 */
                $pageToolElement = $this->getPageElement(PageTemplate::PAGE_TOOL_ELEMENT)->getDomElement();
                try {
                    $pageToolParent = $pageToolElement->getParent();
                } catch (ExceptionNotFound $e) {
                    throw new ExceptionRuntimeInternal("The page tool element has no parent in the template ($this->htmlTemplatePath)");
                }
                $pageToolParent->addClass(PageTemplate::POSITION_RELATIVE_CLASS);

                /**
                 * The railbar
                 */
                $attributeName = "data-layout";
                $railBar = FetcherRailBar::createRailBar()
                    ->setRequestedPath($this->getRequestedContextPath());
                $railBarLayout = $pageToolElement->getAttribute($attributeName);
                if ($railBarLayout !== "") {
                    $pageToolElement->removeAttribute($attributeName);
                    if ($railBarLayout === "offcanvas") {
                        try {
                            $railBar = $railBar->setRequestedLayout($railBarLayout);
                        } catch (ExceptionBadArgument $e) {
                            LogUtility::internalError("The layout ($this) has railbar node that has a layout value of ($railBarLayout) that is unknown. Error:{$e->getMessage()}", self::CANONICAL);
                        }
                    }
                }
                $railBarVariable = Template::toValidVariableName(FetcherRailBar::NAME);
                $htmlFragmentByVariables[$railBarVariable] = $railBar->getFetchString();
                $pageToolElement->insertAdjacentTextNode(Template::VARIABLE_PREFIX . $railBarVariable);

            } catch (ExceptionNotFound $e) {
                // no page tool or no requested context path
            }

            /**
             * Head
             * (At the end of all processing, please)
             */
            try {
                $head = $this->getTemplateDomDocument()->querySelector("head");
            } catch (ExceptionBadSyntax|ExceptionNotFound $e) {
                throw new ExceptionRuntimeInternal("The template ($this->htmlTemplatePath) does not have a head element");
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
             * then called after rendering and toc
             * At last then
             */
            $this->addHeadElements($head, $htmlFragmentByVariables);


            /**
             * Preloaded Css
             * Not really useful
             * We add it just before the end of the body tag
             */
            try {
                $preloadHtml = $this->getHtmlForPreloadedStyleSheets();
                $preloadVariable = Template::toValidVariableName(self::PRELOAD_TAG);
                $htmlFragmentByVariables[$preloadVariable] = $preloadHtml;
                $bodyElement->insertAdjacentTextNode(Template::VARIABLE_PREFIX . $preloadVariable, "beforeend");
            } catch (ExceptionNotFound $e) {
                // no preloaded stylesheet resources
            } catch (ExceptionBadArgument $e) {
                // if the insert position is not good, should not happen as it's hard coded by us
                throw new ExceptionRuntimeInternal("Inserting the preloaded HTML returns an error. Error:{$e->getMessage()}", self::CANONICAL, 1, $e);
            }

            /**
             * We save as XML because we strive to be XML compliant (ie XHTML)
             * And we want to load it as XML to check the XHTML namespace (ie xmlns)
             */
            $htmlBodyDocumentString = $this->getTemplateDomDocument()->toHtml();
            $finalHtmlBodyString = Template::create($htmlBodyDocumentString)->setProperties($htmlFragmentByVariables)->render();

            /**
             * DocType is required by bootstrap
             * https://getbootstrap.com/docs/5.0/getting-started/introduction/#html5-doctype
             */
            return "<!doctype html>\n$finalHtmlBodyString";
        } finally {
            $executionContext
                ->closeExecutingPageTemplate();
        }

    }

    /**
     * @return PageTemplateElement[]
     */
    public function getPageLayoutElements(): array
    {
        return $this->pageElements;
    }

    private function getTemplateDomDocument(): XmlDocument
    {
        return $this->templateDomDocument;
    }

    public function setRequestedContextPath(WikiPath $wikiPath): PageTemplate
    {
        $this->requestedContextPath = $wikiPath;
        return $this;
    }

    /**
     * @throws ExceptionNotFound
     */
    private function getRequestedLang(): Lang
    {
        if (!isset($this->requestedLang)) {
            throw new ExceptionNotFound("No requested lang");
        }
        return $this->requestedLang;
    }

    /**
     * @param Lang $requestedLang
     * @return PageTemplate
     */
    public function setRequestedLang(Lang $requestedLang): PageTemplate
    {
        $this->requestedLang = $requestedLang;
        return $this;
    }

    private function setRemFontSizeToHtml(XmlElement $html)
    {
        /**
         * Same as {@link self::CONF_REM_SIZE}
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

    private function getLayoutName(): string
    {
        return $this->layoutName;
    }

    /**
     * Adapted from {@link tpl_indexerWebBug()}
     * @throws ExceptionNotFound
     */
    private function addTaskRunnerImageIfRequested(XmlElement $bodyElement): void
    {

        if ($this->requestedEnableTaskRunner === false) {
            return;
        }
        try {
            $taskRunnerImg = $bodyElement->getDocument()->createElement("img");
        } catch (\DOMException $e) {
            LogUtility::internalError("img is a valid tag ban. No exception should happen .Error: {$e->getMessage()}.");
            return;
        }

        try {
            $htmlUrl = UrlEndpoint::createTaskRunnerUrl()
                ->addQueryParameter(DokuwikiId::DOKUWIKI_ID_ATTRIBUTE, $this->getRequestedContextPath()->getWikiId())
                ->addQueryParameter(time())
                ->toHtmlString();
        } catch (ExceptionNotFound $e) {
            throw new ExceptionNotFound("A request path is mandatory when adding a task runner. Disable it if you don't want one in the layout ($this).");
        }

        // no more 1x1 px image because of ad blockers
        $taskRunnerImg
            ->setAttribute("id", PageTemplate::TASK_RUNNER_ID)
            ->addClass("d-none")
            ->setAttribute('width', 2)
            ->setAttribute('height', 1)
            ->setAttribute('alt', 'Task Runner')
            ->setAttribute('src', $htmlUrl);
        $bodyElement->appendChild($taskRunnerImg);

    }

    /**
     * @throws ExceptionNotFound
     */
    private function getPageElement(string $elementId): PageTemplateElement
    {
        $element = $this->pageElements[$elementId];
        if ($element === null) {
            throw new ExceptionNotFound("No element ($elementId) found for the layout ($this)");
        }
        return $element;
    }

    public function __toString()
    {
        return $this->layoutName;
    }


    /**
     * Character set
     * Note: avoid using {@link Html::encode() character entities} in your HTML,
     * provided their encoding matches that of the document (generally UTF-8)
     */
    private function checkCharSetMeta(XmlElement $head)
    {
        $charsetValue = PageTemplate::UTF_8_CHARSET_VALUE;
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
                    ->setAttribute("href", FetcherRawLocalPath::createFromPath($icoWikiPath)->getFetchUrl()->toAbsoluteUrl()->toString())
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
                        ->setAttribute("href", FetcherRawLocalPath::createFromPath($iconPath)->getFetchUrl()->toAbsoluteUrl()->toString())
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
        } catch (\Exception $e) {
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

        $title = $this->getRequestedTitleOrDefault();
        $titleMeta->setNodeValue($title);

    }

    /**
     * @throws ExceptionNotFound - if there is no title and not path
     */
    private function getRequestedTitleOrDefault(): string
    {
        if (isset($this->requestedTitle)) {
            return $this->requestedTitle;
        }
        try {
            $path = $this->getRequestedContextPath();
            $markupPath = MarkupPath::createPageFromPathObject($path);
            return PageTitle::createForMarkup($markupPath)
                ->getValueOrDefault();
        } catch (ExceptionNotFound $e) {
            throw new ExceptionNotFound("A title should be set when the requested path is not set");
        }


    }

    /**
     * @param string $requestedTitle
     * @return PageTemplate
     */
    public function setRequestedTitle(string $requestedTitle): PageTemplate
    {
        $this->requestedTitle = $requestedTitle;
        return $this;
    }

    /**
     * @param XmlElement $head
     * @return void
     *
     * Responsive meta tag
     */
    private function checkViewPortMeta(XmlElement $head)
    {
        $expectedResponsiveContent = PageTemplate::VIEWPORT_RESPONSIVE_VALUE;
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

    private function addHeadElements(XmlElement $head, &$htmlFragmentByVariables)
    {

        /**
         * Add the layout js and css first
         */
        $snippetManager = PluginUtility::getSnippetManager();
        try {
            $content = FileSystems::getContent($this->getCssPath());
            $snippetManager->attachCssInternalStylesheet(self::CANONICAL, $content);
        } catch (ExceptionNotFound $e) {
            // no css found, not a problem
        }
        if (FileSystems::exists($this->getJsPath())) {
            $snippetManager->attachInternalJavascriptFromPathForRequest(self::CANONICAL, $this->getJsPath());
        }

        /**
         * Start the meta headers
         */
        /**
         * Meta headers
         * To delete the not needed headers for an export
         * such as manifest, alternate, ...
         */
        if ($this->deleteSocialHeads) {
            global $EVENT_HANDLER;
            $EVENT_HANDLER->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'deleteSocialHeadTags');
        }
        ob_start();
        try {
            tpl_metaheaders();
            $htmlHeaders = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        $variableName = "headElements";
        $htmlFragmentByVariables[$variableName] = $htmlHeaders;
        $head->insertAdjacentTextNode(Template::VARIABLE_PREFIX . $variableName);


    }

    /**
     * Delete all social heads (export or iframe case)
     * @param $event
     */
    public function deleteSocialHeadTags(&$event)
    {

        $data = &$event->data;
        foreach ($data as $tag => &$heads) {
            switch ($tag) {
                case "link":
                    $deletedRel = ["manifest", "search", "start", "alternate", "canonical"];
                    foreach ($heads as $id => $headAttributes) {
                        if (isset($headAttributes['rel'])) {
                            $rel = $headAttributes['rel'];
                            if (in_array($rel, $deletedRel)) {
                                unset($heads[$id]);
                            }
                        }
                    }
                    break;
                case "meta":
                    $deletedMeta = ["robots", "og:url", "og:description", "description"];
                    foreach ($heads as $id => $headAttributes) {
                        if (isset($headAttributes['name']) || isset($headAttributes['property'])) {
                            $rel = $headAttributes['name'];
                            if ($rel === null) {
                                $rel = $headAttributes['property'];
                            }
                            if (in_array($rel, $deletedMeta)) {
                                unset($heads[$id]);
                            }
                        }
                    }
                    break;
                case "script":
                    foreach ($heads as $id => $headAttributes) {
                        if (isset($headAttributes['src'])) {
                            $src = $headAttributes['src'];
                            if (strpos($src, "lib/exe/js.php") !== false) {
                                unset($heads[$id]);
                            }
                        }
                    }
            }
        }
    }

    public function getSlotElements(): array
    {
        $slotElements = [];
        foreach ($this->getPageLayoutElements() as $element) {
            if ($element->isSlot()) {
                $slotElements[] = $element;
            }
        }
        return $slotElements;
    }


    /**
     * @throws ExceptionNotFound
     */
    public function getMainElement(): PageTemplateElement
    {
        return $this->getPageElement(PageTemplate::MAIN_CONTENT_ELEMENT);
    }


    public function hasContentHeader(): bool
    {
        try {
            $element = $this->getPageElement(PageTemplate::MAIN_HEADER_ELEMENT);
        } catch (ExceptionNotFound $e) {
            return false;
        }
        try {
            $element->getMarkupFetcher();
            return true;
        } catch (\Exception $e) {
            return false;
        }

    }

    public function setToc(Toc $toc): PageTemplate
    {
        $this->toc = $toc;
        return $this;
    }

    private function getTocOrDefault(): Toc
    {
        if (isset($this->toc)) {
            return $this->toc;
        }
        $wikiPath = $this->getRequestedContextPath();
        if (FileSystems::isDirectory($wikiPath)) {
            LogUtility::error("We have a found an inconsistency. The context path is not a markup directory and does have therefore no toc but the template ($this) has a toc.");
        }
        $markup = MarkupPath::createPageFromPathObject($wikiPath);
        return Toc::createForPage($markup);

    }

    /**
     * Delete the social head tags
     * @param bool $deleteSocialHeads
     * @return PageTemplate
     */
    public function setDeleteSocialHeadTags(bool $deleteSocialHeads): PageTemplate
    {
        $this->deleteSocialHeads = $deleteSocialHeads;
        return $this;
    }

    /**
     * @throws ExceptionBadSyntax
     */
    public function generateAndGetPageHtmlAsDom(string $mainHtml): XmlDocument
    {
        return XmlDocument::createHtmlDocFromMarkup($this->generateAndGetPageHtmlAsString($mainHtml));
    }

    /**
     * Add the preloaded CSS resources
     * at the end
     * @throws ExceptionNotFound
     */
    private function getHtmlForPreloadedStyleSheets(): string
    {

        // For the preload if any
        try {
            $preloadedCss = ExecutionContext::getActualOrCreateFromEnv()->getRuntimeObject(self::PRELOAD_TAG);
        } catch (ExceptionNotFound $e) {
            throw new ExceptionNotFound("No preloaded resources found");
        }

        //
        // Note: Adding this css in an animationFrame
        // such as https://github.com/jakearchibald/svgomg/blob/master/src/index.html#L183
        // would be difficult to test

        $class = StyleUtility::addComboStrapSuffix(self::PRELOAD_TAG);
        $preloadHtml = "<div class=\"$class\">";
        foreach ($preloadedCss as $link) {
            $htmlLink = '<link rel="stylesheet" href="' . $link['href'] . '" ';
            if ($link['crossorigin'] != "") {
                $htmlLink .= ' crossorigin="' . $link['crossorigin'] . '" ';
            }
            if (!empty($link['class'])) {
                $htmlLink .= ' class="' . $link['class'] . '" ';
            }
            // No integrity here
            $htmlLink .= '>';
            $preloadHtml .= $htmlLink;
        }
        $preloadHtml .= "</div>";
        return $preloadHtml;

    }

    /**
     * Variation of {@link html_msgarea()}
     */
    public function addMessages(XmlElement $bodyElement): void
    {

        global $MSG, $MSG_shown;
        /** @var array $MSG */
        // store if the global $MSG has already been shown and thus HTML output has been started
        $MSG_shown = true;

        if (!isset($MSG)) return;


        $shown = array();

        $toasts = "";
        foreach ($MSG as $msg) {
            $hash = md5($msg['msg']);
            if (isset($shown[$hash])) continue; // skip double messages
            if (info_msg_allowed($msg)) {
                $level = ucfirst($msg['lvl']);
                switch ($level) {
                    case "Error":
                        $class = "text-danger";
                        $autoHide = "false";
                        break;
                    default:
                        $class = "text-primary";
                        $autoHide = "true";
                        break;
                }
                $toasts .= <<<EOF
<div role="alert" aria-live="assertive" aria-atomic="true" class="toast fade" data-bs-autohide="$autoHide">
  <div class="toast-header">
    <strong class="me-auto $class">{$level}</strong>
    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
  </div>
  <div class="toast-body">
        <p>{$msg['msg']}</p>
  </div>
</div>
EOF;

            }
            $shown[$hash] = 1;
        }

        unset($GLOBALS['MSG']);

        if ($toasts === "") {
            return;
        }

        // position fixed to not participate into the grid
        $toastsHtml =<<<EOF
<div class="toast-container position-fixed mb-3 me-3 bottom-0 end-0" id="toastPlacement" style="z-index:1060">
$toasts
</div>
EOF;
        try {
            $bodyElement->insertAdjacentHTML('beforeend', $toastsHtml);
        } catch (ExceptionBadSyntax|ExceptionBadArgument $e) {
            // should not happen
            LogUtility::error($e);
        }

        ExecutionContext::getActualOrCreateFromEnv()
            ->getSnippetSystem()
            ->attachJavascriptFromComponentId("toast");
    }

}
