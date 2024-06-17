<?php

namespace ComboStrap;


use ComboStrap\Meta\Field\PageTemplateName;
use ComboStrap\TagAttribute\StyleAttribute;
use ComboStrap\Web\UrlEndpoint;
use ComboStrap\Xml\XmlDocument;
use ComboStrap\Xml\XmlElement;
use Symfony\Component\Yaml\Yaml;

/**
 * A page template is the object
 * that generates a HTML page
 * (ie the templating engine)
 *
 * It's used by Fetcher that creates pages such
 * as {@link FetcherPage}, {@link FetcherMarkupWebcode} or {@link FetcherPageBundler}
 *
 * Unfortunately, the template is not a runtime parameters
 * Showing the heading 1 for instance depends on it.
 */
class TemplateForWebPage
{


    /**
     * An internal configuration
     * to tell if the page is social
     * (ie seo, search engine, friendly)
     */
    const CONF_INTERNAL_IS_SOCIAL = "web-page-is-social";

    /**
     * DocType is required by bootstrap and chrome
     * https://developer.chrome.com/docs/lighthouse/best-practices/doctype/
     * https://getbootstrap.com/docs/5.0/getting-started/introduction/#html5-doctype
     * <!doctype html>
     *
     * The eol `\n` is needed for lightouse
     */
    const DOCTYPE = "<!doctype html>\n";

    private array $templateDefinition;
    const CANONICAL = "template";


    public const UTF_8_CHARSET_VALUE = "utf-8";
    public const VIEWPORT_RESPONSIVE_VALUE = "width=device-width, initial-scale=1";
    public const TASK_RUNNER_ID = "task-runner";
    public const APPLE_TOUCH_ICON_REL_VALUE = "apple-touch-icon";

    public const PRELOAD_TAG = "preload";

    private string $templateName;


    private string $requestedTitle;


    private bool $requestedEnableTaskRunner = true;
    private WikiPath $requestedContextPath;
    private Lang $requestedLang;
    private Toc $toc;
    private bool $isSocial;
    private string $mainContent;
    private string $templateString;
    private array $model;
    private bool $hadMessages = false;
    private string $requestedTheme;
    private bool $isIframe = false;
    private array $slots;


    public static function create(): TemplateForWebPage
    {
        return new TemplateForWebPage();
    }

    public static function config(): TemplateForWebPage
    {
        return new TemplateForWebPage();
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
        return $this->getEngine()->searchTemplateByName($this->templateName . "." . TemplateEngine::EXTENSION_HBS);
    }

    public function setTemplateString(string $templateString): TemplateForWebPage
    {
        $this->templateString = $templateString;
        return $this;
    }

    public function setModel(array $model): TemplateForWebPage
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
        /**
         * The deprecated report are just messing up html
         */
        $oldLevel = error_reporting(E_ALL ^ E_DEPRECATED);
        try {


            $pageTemplateEngine = $this->getEngine();
            if ($this->isTemplateStringExecutionMode()) {
                $template = $this->templateString;
            } else {
                $pageTemplateEngine = $this->getEngine();
                $template = $this->getTemplateName();
                if (!$pageTemplateEngine->templateExists($template)) {
                    $defaultTemplate = PageTemplateName::HOLY_TEMPLATE_VALUE;
                    LogUtility::warning("The template ($template) was not found, the default template ($defaultTemplate) was used instead.");
                    $template = $defaultTemplate;
                    $this->setRequestedTemplateName($template);
                }
            }

            /**
             * Get model should came after template validation
             * as the template definition is named dependent
             * (Create a builder, nom de dieu)
             */
            $model = $this->getModel();


            return self::DOCTYPE . $pageTemplateEngine->renderWebPage($template, $model);


        } finally {
            error_reporting($oldLevel);
            $executionContext
                ->closeExecutingPageTemplate();
        }

    }

    /**
     * @return string[]
     */
    public function getElementIds(): array
    {
        $definition = $this->getDefinition();
        $elements = $definition['elements'] ?? null;
        if ($elements == null) {
            return [];
        }
        return $elements;

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


    public function getTemplateName(): string
    {
        if (isset($this->templateName)) {
            return $this->templateName;
        }
        try {
            $requestedPath = $this->getRequestedContextPath();
            return PageTemplateName::createFromPage(MarkupPath::createPageFromPathObject($requestedPath))
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
        return $this->templateName;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getCssPath(): LocalPath
    {
        return $this->getEngine()->searchTemplateByName("$this->templateName.css");
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getJsPath(): LocalPath
    {
        $jsPath = $this->getEngine()->searchTemplateByName("$this->templateName.js");
        if (!FileSystems::exists($jsPath)) {
            throw new ExceptionNotFound("No js file");
        }
        return $jsPath;
    }

    public function hasMessages(): bool
    {
        return $this->hadMessages;
    }

    public function setRequestedTheme(string $themeName): TemplateForWebPage
    {
        $this->requestedTheme = $themeName;
        return $this;
    }

    public function hasElement(string $elementId): bool
    {
        return in_array($elementId, $this->getElementIds());
    }

    public function isSocial(): bool
    {
        if (isset($this->isSocial)) {
            return $this->isSocial;
        }
        try {
            $path = $this->getRequestedContextPath();
            if (!FileSystems::exists($path)) {
                return false;
            }
            $markup = MarkupPath::createPageFromPathObject($path);
            if ($markup->isSlot()) {
                // slot are not social
                return false;
            }
        } catch (ExceptionNotFound $e) {
            // not a path run
            return false;
        }
        if ($this->isIframe) {
            return false;
        }
        return ExecutionContext::getActualOrCreateFromEnv()
            ->getConfig()
            ->getBooleanValue(self::CONF_INTERNAL_IS_SOCIAL, true);

    }

    public function setIsIframe(bool $isIframe): TemplateForWebPage
    {
        $this->isIframe = $isIframe;
        return $this;
    }

    /**
     * @return TemplateSlot[]
     */
    public function getSlots(): array
    {
        if (isset($this->slots)) {
            return $this->slots;
        }
        $this->slots = [];
        foreach ($this->getElementIds() as $elementId) {
            if ($elementId === TemplateSlot::MAIN_TOC_ID) {
                /**
                 * Main toc element is not a slot
                 */
                continue;
            }

            try {
                $this->slots[] = TemplateSlot::createFromElementId($elementId, $this->getRequestedContextPath());
            } catch (ExceptionNotFound $e) {
                LogUtility::internalError("This template is not for a markup path, it cannot have slots then.");
            }
        }
        return $this->slots;
    }


    /**
     * Character set
     * Note: avoid using {@link Html::encode() character entities} in your HTML,
     * provided their encoding matches that of the document (generally UTF-8)
     */
    private function checkCharSetMeta(XmlElement $head)
    {
        $charsetValue = TemplateForWebPage::UTF_8_CHARSET_VALUE;
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

        $executionConfig = ExecutionContext::getActualOrCreateFromEnv()->getConfig();

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
        $container = $executionConfig->getValue(ContainerTag::DEFAULT_LAYOUT_CONTAINER_CONF, ContainerTag::DEFAULT_LAYOUT_CONTAINER_DEFAULT_VALUE);
        $containerClass = ContainerTag::getClassName($container);
        $model["layout-container-class"] = $containerClass;


        /**
         * The rem
         */
        try {
            $model["rem-size"] = $executionConfig->getRemFontSize();
        } catch (ExceptionNotFound $e) {
            // ok none
        }


        /**
         * Body class
         * {@link tpl_classes} will add the dokuwiki class.
         * See https://www.dokuwiki.org/devel:templates#dokuwiki_class
         * dokuwiki__top ID is needed for the "Back to top" utility
         * used also by some plugins
         * dokwuiki as class is also needed as it's used by the linkwizard
         * to locate where to add the node (ie .appendTo('.dokuwiki:first'))
         */
        $bodyDokuwikiClass = tpl_classes();
        try {
            $bodyTemplateIdentifierClass = StyleAttribute::addComboStrapSuffix("{$this->getTheme()}-{$this->getTemplateName()}");
        } catch (\Exception $e) {
            $bodyTemplateIdentifierClass = StyleAttribute::addComboStrapSuffix("template-string");
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
             * Css Variables Colors
             * Added for now in `head-partial.hbs`
             */
            try {
                $primaryColor = $executionConfig->getPrimaryColor();
                $model[BrandingColors::PRIMARY_COLOR_TEMPLATE_ATTRIBUTE] = $primaryColor->toCssValue();
                $model[BrandingColors::PRIMARY_COLOR_TEXT_ATTRIBUTE] = ColorSystem::toTextColor($primaryColor);
                $model[BrandingColors::PRIMARY_COLOR_TEXT_HOVER_ATTRIBUTE] = ColorSystem::toTextHoverColor($primaryColor);
            } catch (ExceptionNotFound $e) {
                // not found
                $model[BrandingColors::PRIMARY_COLOR_TEMPLATE_ATTRIBUTE] = null;
            }
            try {
                $secondaryColor = $executionConfig->getSecondaryColor();
                $model[BrandingColors::SECONDARY_COLOR_TEMPLATE_ATTRIBUTE] = $secondaryColor->toCssValue();
            } catch (ExceptionNotFound $e) {
                // not found
            }


            /**
             * Main
             */
            if (isset($this->mainContent)) {
                $model["main-content-html"] = $this->mainContent;
            } else {
                try {
                    if (!$markupPath->isSlot()) {
                        $requestedContextPathForMain = $this->getRequestedContextPath();
                    } else {
                        try {
                            $markupContextPath = SlotSystem::getContextPath();
                            SlotSystem::sendContextPathMessage($markupContextPath);
                            $requestedContextPathForMain = $markupContextPath->toWikiPath();
                        } catch (ExceptionNotFound|ExceptionCast $e) {
                            $requestedContextPathForMain = $this->getRequestedContextPath();
                        }
                    }
                    $model["main-content-html"] = FetcherMarkup::confRoot()
                        ->setRequestedMimeToXhtml()
                        ->setRequestedContextPath($requestedContextPathForMain)
                        ->setRequestedExecutingPath($this->getRequestedContextPath())
                        ->build()
                        ->getFetchString();
                } catch (ExceptionCompile|ExceptionNotExists|ExceptionNotExists $e) {
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
            foreach ($this->getSlots() as $slot) {

                $elementId = $slot->getElementId();
                try {
                    $model["$elementId-html"] = $slot->getMarkupFetcher()->getFetchString();
                } catch (ExceptionNotFound|ExceptionNotExists $e) {
                    // no slot found
                } catch (ExceptionCompile $e) {
                    LogUtility::error("Error while rendering the slot $elementId for the template ($this)", self::CANONICAL, $e);
                    $model["$elementId-html"] = LogUtility::wrapInRedForHtml("Error: " . $e->getMessage());
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
            if (isset($this->mainContent)) {
                $model["main-content-html"] = $this->mainContent;
            }
        }


        /**
         * Head Html
         * Snippet, Css and Js from the layout if any
         *
         * Note that head tag may be added during rendering and must be then called after rendering and toc
         * (ie at last then)
         */
        $model['head-html'] = $this->getHeadHtml();

        /**
         * Preloaded Css
         * (It must come after the head processing as this is where the preloaded script are defined)
         * (Not really useful but legacy)
         * We add it just before the end of the body tag
         */
        try {
            $model['preloaded-stylesheet-html'] = $this->getHtmlForPreloadedStyleSheets();
        } catch (ExceptionNotFound $e) {
            // no preloaded stylesheet resources
        }

        /**
         * Powered by
         */
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
            $model['head-html'] .= Snippet::getOrCreateFromComponentId("toast", Snippet::EXTENSION_JS)->toXhtml();
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
            LogUtility::error("We have a found an inconsistency. The context path is a directory and does have therefore no toc but the template ($this) has a toc.");
        }
        $markup = MarkupPath::createPageFromPathObject($wikiPath);
        return Toc::createForPage($markup);

    }

    public
    function setMainContent(string $mainContent): TemplateForWebPage
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
            $executionContext = ExecutionContext::getActualOrCreateFromEnv();
            $preloadedCss = $executionContext->getRuntimeObject(self::PRELOAD_TAG);
        } catch (ExceptionNotFound $e) {
            throw new ExceptionNotFound("No preloaded resources found");
        }

        //
        // Note: Adding this css in an animationFrame
        // such as https://github.com/jakearchibald/svgomg/blob/master/src/index.html#L183
        // would be difficult to test

        $class = StyleAttribute::addComboStrapSuffix(self::PRELOAD_TAG);
        $preloadHtml = "<div class=\"$class\">";
        foreach ($preloadedCss as $link) {
            $htmlLink = '<link rel="stylesheet" href="' . $link['href'] . '" ';
            if (($link['crossorigin'] ?? '') != "") {
                $htmlLink .= ' crossorigin="' . $link['crossorigin'] . '" ';
            }
            if (!empty(($link['class'] ?? null))) {
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

        global $MSG;

        if (!isset($MSG)) {
            throw new ExceptionNotFound("No messages");
        }

        // deduplicate and auth
        $uniqueMessages = [];
        foreach ($MSG as $msg) {
            if (!info_msg_allowed($msg)) {
                continue;
            }
            $hash = md5($msg['msg']);
            $uniqueMessages[$hash] = $msg;
        }

        $messagesByLevel = [];
        foreach ($uniqueMessages as $message) {
            $level = $message['lvl'];
            $messagesByLevel[$level][] = $message;
        }

        $toasts = "";
        foreach ($messagesByLevel as $level => $messagesForLevel) {
            $level = ucfirst($level);
            switch ($level) {
                case "Error":
                    $class = "text-danger";
                    $levelName = "Error";
                    break;
                case "Notify":
                    $class = "text-warning";
                    $levelName = "Warning";
                    break;
                default:
                    $levelName = $level;
                    $class = "text-primary";
                    break;
            }
            $autoHide = "false"; // auto-hidding is really bad ui
            $toastMessage = "";
            foreach ($messagesForLevel as $messageForLevel) {
                $toastMessage .= "<p>{$messageForLevel['msg']}</p>";
            }


            $toasts .= <<<EOF
<div role="alert" aria-live="assertive" aria-atomic="true" class="toast fade" data-bs-autohide="$autoHide">
  <div class="toast-header">
    <strong class="me-auto $class">{$levelName}</strong>
    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
  </div>
  <div class="toast-body">
        $toastMessage
  </div>
</div>
EOF;
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

    private
    function canBeCached(): bool
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
                ->toString();
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntimeInternal("A request path is mandatory when adding a task runner. Disable it if you don't want one in the layout ($this).");
        }

        // no more 1x1 px image because of ad blockers
        return TagAttributes::createEmpty()
            ->addOutputAttributeValue("id", TemplateForWebPage::TASK_RUNNER_ID)
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

    private
    function getTheme(): string
    {
        return $this->requestedTheme ?? ExecutionContext::getActualOrCreateFromEnv()->getConfig()->getTheme();
    }

    private
    function getHeadHtml(): string
    {
        $snippetManager = PluginUtility::getSnippetManager();

        if (!$this->isTemplateStringExecutionMode()) {

            /**
             * Add the layout js and css first
             */

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
         * Dokuwiki Smiley does not have any height
         */
        $snippetManager->attachCssInternalStyleSheet("dokuwiki-smiley");

        /**
         * Iframe
         */
        if ($this->isIframe) {
            global $EVENT_HANDLER;
            $EVENT_HANDLER->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'onlyIframeHeadTags');
        }
        /**
         * Start the meta headers
         */
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
    function setRequestedTemplateName(string $templateName): TemplateForWebPage
    {
        $this->templateName = $templateName;
        return $this;
    }

    /**
     * Add or not the task runner / web bug call
     * @param bool $b
     * @return TemplateForWebPage
     */
    public
    function setRequestedEnableTaskRunner(bool $b): TemplateForWebPage
    {
        $this->requestedEnableTaskRunner = $b;
        return $this;
    }


    /**
     * @param Lang $requestedLang
     * @return TemplateForWebPage
     */
    public
    function setRequestedLang(Lang $requestedLang): TemplateForWebPage
    {
        $this->requestedLang = $requestedLang;
        return $this;
    }

    /**
     * @param string $requestedTitle
     * @return TemplateForWebPage
     */
    public
    function setRequestedTitle(string $requestedTitle): TemplateForWebPage
    {
        $this->requestedTitle = $requestedTitle;
        return $this;
    }

    /**
     * Delete the social head tags
     * (ie the page should not be indexed)
     * This is used for iframe content for instance
     * @param bool $isSocial
     * @return TemplateForWebPage
     */
    public
    function setIsSocial(bool $isSocial): TemplateForWebPage
    {
        $this->isSocial = $isSocial;
        return $this;
    }

    public
    function setRequestedContextPath(WikiPath $contextPath): TemplateForWebPage
    {
        $this->requestedContextPath = $contextPath;
        return $this;
    }

    public
    function setToc(Toc $toc): TemplateForWebPage
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
    private
    function isTemplateStringExecutionMode(): bool
    {
        return isset($this->templateString);
    }

    private
    function getEngine(): TemplateEngine
    {
        if ($this->isTemplateStringExecutionMode()) {
            return TemplateEngine::createForString();

        } else {
            $theme = $this->getTheme();
            return TemplateEngine::createForTheme($theme);
        }
    }

    private
    function getDefinition(): array
    {
        try {
            if (isset($this->templateDefinition)) {
                return $this->templateDefinition;
            }
            $file = $this->getEngine()->searchTemplateByName("{$this->getTemplateName()}.yml");
            if (!FileSystems::exists($file)) {
                return [];
            }
            $this->templateDefinition = Yaml::parseFile($file->toAbsoluteId());
            return $this->templateDefinition;
        } catch (ExceptionNotFound $e) {
            // no template directory, not a theme run
            return [];
        }
    }

    private
    function getRailbarLayout(): string
    {
        $definition = $this->getDefinition();
        if (isset($definition['railbar']['layout'])) {
            return $definition['railbar']['layout'];
        }
        return FetcherRailBar::BOTH_LAYOUT;
    }

    /**
     * Keep the only iframe head tag needed
     * @param $event
     * @return void
     */
    public
    function onlyIframeHeadTags(&$event)
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
                            if ($rel === "stylesheet") {
                                $href = $headAttributes['href'];
                                if (strpos($href, "lib/exe/css.php") !== false) {
                                    unset($heads[$id]);
                                }
                            }
                        }
                    }
                    break;
                case "meta":
                    $deletedMeta = ["og:url", "og:description", "description", "robots"];
                    foreach ($heads as $id => $headAttributes) {
                        if (isset($headAttributes['name']) || isset($headAttributes['property'])) {
                            $rel = $headAttributes['name'] ?? null;
                            if ($rel === null) {
                                $rel = $headAttributes['property'] ?? null;
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
                            if (strpos($src, "lib/exe/jquery.php") !== false) {
                                unset($heads[$id]);
                            }
                        }
                    }
                    break;
            }
        }
    }

}
