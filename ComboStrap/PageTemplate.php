<?php

namespace ComboStrap;


use Symfony\Component\Yaml\Yaml;

/**
 * A page template is the object
 * that generates a HTML page
 * (ie the templating engine)
 *
 * It's used by Fetcher that creates pages such
 * as {@link FetcherPage}, {@link FetcherMarkupWebcode} or {@link FetcherPageBundler}
 */
class PageTemplate
{


    private array $templateDefinition;
    const CANONICAL = "template";


    public const UTF_8_CHARSET_VALUE = "utf-8";
    public const VIEWPORT_RESPONSIVE_VALUE = "width=device-width, initial-scale=1";
    public const TASK_RUNNER_ID = "task-runner";
    public const APPLE_TOUCH_ICON_REL_VALUE = "apple-touch-icon";

    public const PRELOAD_TAG = "preload";

    private string $layoutName;


    private string $requestedTitle;

    /**
     * @var PageTemplateSlot[]
     */
    private array $pageElements = [];

    private bool $requestedEnableTaskRunner = true;
    private WikiPath $requestedContextPath;
    private Lang $requestedLang;
    private Toc $toc;
    private bool $deleteSocialHeads = false;
    private string $mainContent;
    private string $templateString;
    private array $model;
    private bool $hadMessages = false;


    public static function create(): PageTemplate
    {
        return new PageTemplate();
    }

    public static function config(): PageTemplate
    {
        return new PageTemplate();
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
     * @throws ExceptionNotFound
     */
    public function getHtmlTemplatePath(): LocalPath
    {
        return $this->getEngine()->getTemplatesDirectory()->resolve($this->layoutName . "." . PageTemplateEngine::EXTENSION_HBS);
    }

    public function setTemplateString(string $templateString): PageTemplate
    {
        $this->templateString = $templateString;
        return $this;
    }

    public function setModel(array $model)
    {
        $this->model = $model;
        return $this;
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
     *
     * @return string - the page as html string (not dom because that's not how works dokuwiki)
     *
     */
    public function render(): string
    {

        $executionContext = (ExecutionContext::getActualOrCreateFromEnv())
            ->setExecutingPageTemplate($this);
        try {

            $model = $this->getModel();
            $pageTemplateEngine = $this->getEngine();
            if ($this->isTemplateStringExecutionMode()) {

                $template = $this->templateString;
            } else {
                $theme = $this->getTheme();
                $pageTemplateEngine = PageTemplateEngine::createForTheme($theme);
                $template = $this->getLayoutName();
            }

            /**
             * Checks ???
             * DocType is required by bootstrap
             * https://getbootstrap.com/docs/5.0/getting-started/introduction/#html5-doctype
             * <!doctype html>
             */
            return $pageTemplateEngine->render($template, $model);


        } finally {
            $executionContext
                ->closeExecutingPageTemplate();
        }

    }

    /**
     * @return string[]
     */
    public function getSlotIds(): array
    {
        $definition = $this->getDefinition();
        $slots = $definition['slots'];
        if ($slots == null) {
            return [];
        }
        return $slots;

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


    private function getLayoutName(): string
    {
        if (isset($this->layoutName)) {
            return $this->layoutName;
        }
        try {
            $requestedPath = $this->getRequestedContextPath();
            return PageLayoutName::createFromPage(MarkupPath::createPageFromPathObject($requestedPath))
                ->getValueOrDefault();
        } catch (ExceptionNotFound $e) {
            // no requested path
        }
        return ExecutionContext::getActualOrCreateFromEnv()
            ->getConfig()
            ->getDefaultLayoutName();
    }


    public function __toString()
    {
        return $this->layoutName;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getCssPath(): LocalPath
    {
        return $this->getEngine()->getTemplatesDirectory()->resolve("$this->layoutName.css");
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getJsPath(): LocalPath
    {
        $jsPath = $this->getEngine()->getTemplatesDirectory()->resolve("$this->layoutName.js");
        if (!FileSystems::exists($jsPath)) {
            throw new ExceptionNotFound("No js file");
        }
        return $jsPath;
    }

    public function hasMessages(): bool
    {
        return $this->hadMessages;
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
    private function getPageIconHeadLinkHtml(): string
    {
        $html = $this->getShortcutFavIconHtmlLink();
        $html .= $this->getIconHtmlLink();
        $html .= $this->getAppleTouchIconHtmlLink();
        return $html;
    }

    /**
     * Add a favIcon.ico
     *
     */
    private function getShortcutFavIconHtmlLink(): string
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
            return "";
        }

        return TagAttributes::createEmpty()
            ->addOutputAttributeValue("rel", "shortcut icon")
            ->addOutputAttributeValue("href", FetcherRawLocalPath::createFromPath($icoWikiPath)->getFetchUrl()->toAbsoluteUrl()->toString())
            ->toHtmlEmptyTag("link");

    }

    /**
     * Add Icon Png (16x16 and 32x32)
     * @return string
     */
    private function getIconHtmlLink(): string
    {

        $html = "";
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
            $html .= TagAttributes::createEmpty()
                ->addOutputAttributeValue("rel", "icon")
                ->addOutputAttributeValue("sizes", $sizeValue)
                ->addOutputAttributeValue("type", Mime::PNG)
                ->addOutputAttributeValue("href", FetcherRawLocalPath::createFromPath($iconPath)->getFetchUrl()->toAbsoluteUrl()->toString())
                ->toHtmlEmptyTag("link");
        }
        return $html;
    }

    /**
     * Add Apple touch icon
     *
     * @return string
     */
    private function getAppleTouchIconHtmlLink(): string
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
            return "";
        }
        try {
            $fetcherLocalPath = FetcherRaster::createImageRasterFetchFromPath($iconPath);
            $sizesValue = "{$fetcherLocalPath->getIntrinsicWidth()}x{$fetcherLocalPath->getIntrinsicHeight()}";

            return TagAttributes::createEmpty()
                ->addOutputAttributeValue("rel", self::APPLE_TOUCH_ICON_REL_VALUE)
                ->addOutputAttributeValue("sizes", $sizesValue)
                ->addOutputAttributeValue("type", Mime::PNG)
                ->addOutputAttributeValue("href", $fetcherLocalPath->getFetchUrl()->toAbsoluteUrl()->toString())
                ->toHtmlEmptyTag("link");
        } catch (\Exception $e) {
            LogUtility::internalError("The file ($iconPath) should be found and the local name should be good. Error: {$e->getMessage()}");
            return "";
        }
    }

    public
    function getModel(): array
    {

        /**
         * Mandatory HTML attributes
         */
        $model =
            [
                PageTitle::PROPERTY_NAME => $this->getRequestedTitleOrDefault(),
                Lang::PROPERTY_NAME => $this->getRequestedLangOrDefault()->getValueOrDefault(),
                // The direction is not yet calculated from the page, we let the browser determine it from the lang
                // dokuwiki has a direction config also ...
                // "dir" => $this->getRequestedLangOrDefault()->getDirection()
            ];

        if (isset($this->model)) {
            return array_merge($model, $this->model);
        }

        /**
         * The width of the layout
         */
        $container = SiteConfig::getConfValue(ContainerTag::DEFAULT_LAYOUT_CONTAINER_CONF, ContainerTag::DEFAULT_LAYOUT_CONTAINER_DEFAULT_VALUE);
        $containerClass = ContainerTag::getClassName($container);
        $model["layout-container-class"] = $containerClass;


        /**
         * The rem
         */
        try {
            $model["rem-size"] = ExecutionContext::getActualOrCreateFromEnv()->getConfig()->getRemFontSize();
        } catch (ExceptionNotFound $e) {
            // ok none
        }


        /**
         * Body class
         * {@link tpl_classes} will add the dokuwiki class.
         * See https://www.dokuwiki.org/devel:templates#dokuwiki_class
         * dokuwiki__top ID is needed for the "Back to top" utility
         * used also by some plugins
         */
        $bodyDokuwikiClass = tpl_classes();
        try {
            $bodyTemplateIdentifierClass = StyleUtility::addComboStrapSuffix("{$this->getTheme()}-{$this->getLayoutName()}");
        } catch (\Exception $e) {
            $bodyTemplateIdentifierClass = StyleUtility::addComboStrapSuffix("template-string");
        }
        // position relative is for the toast and messages that are in the corner
        $model['body-classes'] = "$bodyDokuwikiClass position-relative $bodyTemplateIdentifierClass";

        /**
         * Data coupled to a page
         */
        try {

            $contextPath = $this->getRequestedContextPath();
            $markupPath = MarkupPath::createPageFromPathObject($contextPath);
            /**
             * Meta
             */
            $metadata = $markupPath->getMetadataForRendering();
            $model = array_merge($metadata, $model);


            /**
             * Railbar
             * You can define the layout type by page
             * This is not a handelbars helper because it needs some css snippet.
             */
            $railBarLayout = $this->getRailbarLayout();
            try {
                $model["railbar-html"] = FetcherRailBar::createRailBar()
                    ->setRequestedLayout($railBarLayout)
                    ->setRequestedPath($contextPath)
                    ->getFetchString();
            } catch (ExceptionBadArgument $e) {
                LogUtility::error("Error while creating the railbar layout");
            }

            /**
             * Colors ?
             */
            $model['primary-color'] = Site::getPrimaryColor();
            $model['secondary-color'] = Site::getSecondaryColor();

            /**
             * Main
             */
            if (isset($this->mainContent)) {
                $model["main-content-html"] = $this->mainContent;
            } else {
                try {
                    $model["main-content-html"] = $markupPath->createHtmlFetcherWithItselfAsContextPath()->getFetchString();
                } catch (ExceptionCast|ExceptionNotExists|ExceptionNotExists $e) {
                    LogUtility::error("Error while rendering the page content.", self::CANONICAL, $e);
                    $model["main-content-html"] = "An error has occured. " . $e->getMessage();
                }
            }

            /**
             * Toc (after main execution please)
             */
            $model['toc-class'] = Toc::getClass();
            $model['toc-html'] = $this->getTocOrDefault()->toXhtml();

            /**
             * Slots
             */
            foreach ($this->getSlotIds() as $slotId) {
                try {
                    $model["$slotId-html"] = PageTemplateSlot::createFor($slotId, $this)->getMarkupFetcher()->getFetchString();
                } catch (ExceptionNotFound|ExceptionCompile $e) {
                    LogUtility::error("Error while rendering the slot $slotId for the template ($this)", self::CANONICAL, $e);
                    $model["$slotId-html"] = LogUtility::wrapInRedForHtml("Error: " . $e->getMessage());
                }
            }

            /**
             * Found in {@link tpl_content()}
             * Used to add html such as {@link \action_plugin_combo_routermessage}
             * Not sure if this is the right place to add it.
             */
            ob_start();
            global $ACT;
            \dokuwiki\Extension\Event::createAndTrigger('TPL_ACT_RENDER', $ACT);
            $tplActRenderOutput = ob_get_clean();
            if (!empty($tplActRenderOutput)) {
                $model["main-content-afterbegin-html"] = $tplActRenderOutput;
                $this->hadMessages = true;
            }

        } catch (ExceptionNotFound $e) {
            // no context path
        }

        /**
         * Preloaded Css
         * (Not really useful but legacy)
         * We add it just before the end of the body tag
         */
        try {
            $model['preloaded-stylesheet-html'] = $this->getHtmlForPreloadedStyleSheets();
        } catch (ExceptionNotFound $e) {
            // no preloaded stylesheet resources
        }

        /**
         * Head Html
         * Snippet, Css and Js from the layout if any
         *
         * Note that head tag may be added during rendering and must be then called after rendering and toc
         * (ie at last then)
         */
        $model['head-html'] = $this->getHeadHtml();
        $model['powered-by'] = self::getPoweredBy();

        /**
         * Messages
         * (Should come just before the page creation
         * due to the $MSG_shown mechanism in {@link html_msgarea()}
         * We may also get messages in the head
         */
        try {
            $model['messages-html'] = $this->getMessages();
            /**
             * Because they must be problem and message with the {@link self::getHeadHtml()}
             * We process the messages at the end
             * It means that the needed script needs to be added manually
             */
            // output <script class="snippet-toast-cs"/>, not good because the body will then be empty
//            $model['head-html'] .= Snippet::getOrCreateFromComponentId("toast", Snippet::EXTENSION_JS)
//                ->toTagAttributes()
//                ->toHtmlEmptyTag("script");
        } catch (ExceptionNotFound $e) {
            // no messages
        } catch (ExceptionBadState $e) {
            throw ExceptionRuntimeInternal::withMessageAndError("The toast snippet should have been found", $e);
        }

        /**
         * Task runner needs the id
         */
        if ($this->requestedEnableTaskRunner && isset($this->requestedContextPath)) {
            $model['task-runner-html'] = $this->getTaskRunnerImg();
        }

        return $model;
    }


    private
    function getRequestedTitleOrDefault(): string
    {

        if (isset($this->requestedTitle)) {
            return $this->requestedTitle;
        }

        try {
            $path = $this->getRequestedContextPath();
            $markupPath = MarkupPath::createPageFromPathObject($path);
            return PageTitle::createForMarkup($markupPath)->getValueOrDefault();
        } catch (ExceptionNotFound $e) {
            //
        }
        throw new ExceptionBadSyntaxRuntime("A title is mandatory");


    }


    /**
     * Delete all social heads (export or iframe case)
     * @param $event
     */
    public
    function deleteSocialHeadTags(&$event)
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


    /**
     * @throws ExceptionNotFound
     */
    private
    function getTocOrDefault(): Toc
    {

        if (isset($this->toc)) {
            /**
             * The {@link FetcherPageBundler}
             * bundle pages can create a toc for multiples pages
             */
            return $this->toc;
        }

        $wikiPath = $this->getRequestedContextPath();
        if (FileSystems::isDirectory($wikiPath)) {
            LogUtility::error("We have a found an inconsistency. The context path is not a markup directory and does have therefore no toc but the template ($this) has a toc.");
        }
        $markup = MarkupPath::createPageFromPathObject($wikiPath);
        return Toc::createForPage($markup);

    }

    public
    function setMainContent(string $mainContent): PageTemplate
    {
        $this->mainContent = $mainContent;
        return $this;
    }


    /**
     * @throws ExceptionBadSyntax
     */
    public
    function renderAsDom(): XmlDocument
    {
        return XmlDocument::createHtmlDocFromMarkup($this->render());
    }

    /**
     * Add the preloaded CSS resources
     * at the end
     * @throws ExceptionNotFound
     */
    private
    function getHtmlForPreloadedStyleSheets(): string
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
     * @throws ExceptionNotFound
     */
    public
    function getMessages(): string
    {

        global $MSG, $MSG_shown;
        /** @var array $MSG */
        // store if the global $MSG has already been shown and thus HTML output has been started
        $MSG_shown = true;

        if (!isset($MSG)) {
            throw new ExceptionNotFound("No messages");
        }

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
            throw new ExceptionNotFound("No messages");
        }
        $this->hadMessages = true;

        // position fixed to not participate into the grid
        return <<<EOF
<div class="toast-container position-fixed mb-3 me-3 bottom-0 end-0" id="toastPlacement" style="z-index:1060">
$toasts
</div>
EOF;

    }

    private function canBeCached(): bool
    {
        // no if message
        return true;
    }

    /**
     * Adapted from {@link tpl_indexerWebBug()}
     * @return string
     */
    private
    function getTaskRunnerImg(): string
    {

        try {
            $htmlUrl = UrlEndpoint::createTaskRunnerUrl()
                ->addQueryParameter(DokuwikiId::DOKUWIKI_ID_ATTRIBUTE, $this->getRequestedContextPath()->getWikiId())
                ->addQueryParameter(time())
                ->toHtmlString();
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntimeInternal("A request path is mandatory when adding a task runner. Disable it if you don't want one in the layout ($this).");
        }

        // no more 1x1 px image because of ad blockers
        return TagAttributes::createEmpty()
            ->addOutputAttributeValue("id", PageTemplate::TASK_RUNNER_ID)
            ->addClassName("d-none")
            ->addOutputAttributeValue('width', 2)
            ->addOutputAttributeValue('height', 1)
            ->addOutputAttributeValue('alt', 'Task Runner')
            ->addOutputAttributeValue('src', $htmlUrl)
            ->toHtmlEmptyTag("img");
    }

    private
    function getRequestedLangOrDefault(): Lang
    {
        try {
            return $this->getRequestedLang();
        } catch (ExceptionNotFound $e) {
            return Lang::createFromValue("en");
        }
    }

    private function getTheme()
    {
        return $this->theme ?? ExecutionContext::getActualOrCreateFromEnv()->getConfig()->getTheme();
    }

    private function getHeadHtml(): string
    {
        if (!$this->isTemplateStringExecutionMode()) {

            $themeName = $this->getTheme();
            /**
             * Add the layout js and css first
             */
            $snippetManager = PluginUtility::getSnippetManager();
            try {
                $cssPath = $this->getCssPath();
                $content = FileSystems::getContent($cssPath);
                $snippetManager->attachCssInternalStylesheet(self::CANONICAL, $content);
            } catch (ExceptionNotFound $e) {
                // no css found, not a problem
            }
            try {
                $jsPath = $this->getJsPath();
                $snippetManager->attachInternalJavascriptFromPathForRequest(self::CANONICAL, $jsPath);
            } catch (ExceptionNotFound $e) {
                // not found
            }


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
            $headIcon = $this->getPageIconHeadLinkHtml();
            return $headIcon . ob_get_contents();
        } finally {
            ob_end_clean();
        }

    }


    public
    function setLayoutName(string $layoutName): PageTemplate
    {
        $this->layoutName = $layoutName;
        return $this;
    }

    /**
     * Add or not the task runner / web bug call
     * @param bool $b
     * @return PageTemplate
     */
    public
    function setRequestedEnableTaskRunner(bool $b): PageTemplate
    {
        $this->requestedEnableTaskRunner = $b;
        return $this;
    }


    /**
     * @param Lang $requestedLang
     * @return PageTemplate
     */
    public
    function setRequestedLang(Lang $requestedLang): PageTemplate
    {
        $this->requestedLang = $requestedLang;
        return $this;
    }

    /**
     * @param string $requestedTitle
     * @return PageTemplate
     */
    public
    function setRequestedTitle(string $requestedTitle): PageTemplate
    {
        $this->requestedTitle = $requestedTitle;
        return $this;
    }

    /**
     * Delete the social head tags
     * @param bool $deleteSocialHeads
     * @return PageTemplate
     */
    public
    function setDeleteSocialHeadTags(bool $deleteSocialHeads): PageTemplate
    {
        $this->deleteSocialHeads = $deleteSocialHeads;
        return $this;
    }

    public
    function setRequestedContextPath(WikiPath $contextPath): PageTemplate
    {
        $this->requestedContextPath = $contextPath;
        return $this;
    }

    public
    function setToc(Toc $toc): PageTemplate
    {
        $this->toc = $toc;
        return $this;
    }

    /**
     * There is two mode of execution, via:
     * * a file template (theme)
     * * or a string template (string)
     *
     * @return bool - true if this a string template executions
     */
    private function isTemplateStringExecutionMode(): bool
    {
        return isset($this->templateString);
    }

    private function getEngine(): PageTemplateEngine
    {
        if ($this->isTemplateStringExecutionMode()) {
            return PageTemplateEngine::createForString();

        } else {
            $theme = $this->getTheme();
            return PageTemplateEngine::createForTheme($theme);
        }
    }

    private function getDefinition(): array
    {
        try {
            if (isset($this->templateDefinition)) {
                return $this->templateDefinition;
            }
            $file = $this->getEngine()->getTemplatesDirectory()->resolve("$this->layoutName.yml");
            if (!FileSystems::exists($file)) {
                return [];
            }
            $this->templateDefinition = Yaml::parseFile($file->toAbsoluteString());
            return $this->templateDefinition;
        } catch (ExceptionNotFound $e) {
            // no template directory, not a theme run
            return [];
        }
    }

    private function getRailbarLayout(): string
    {
        $definition = $this->getDefinition();
        if (isset($definition['railbar']['layout'])) {
            return $definition['railbar']['layout'];
        }
        return FetcherRailBar::BOTH_LAYOUT;
    }

}
