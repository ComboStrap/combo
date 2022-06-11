<?php

namespace ComboStrap;


use Exception;
use syntax_plugin_combo_container;

class Layout
{


    const CANONICAL = "layout";
    const PAGE_CORE_AREA = "page-core";
    const PAGE_SIDE_AREA = "page-side";
    const PAGE_HEADER_AREA = "page-header";
    const PAGE_FOOTER_AREA = "page-footer";
    const PAGE_MAIN_AREA = "page-main";
    const MAIN_SIDE_AREA = "main-side";
    const MAIN_CONTENT_AREA = "main-content";
    const MAIN_HEADER_AREA = "main-header";
    const MAIN_FOOTER_AREA = "main-footer";
    const areas = [
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

    /**
     * @var LayoutArea[]
     */
    private array $layoutAreas;

    public static function create(): Layout
    {
        $layout = new Layout();
        $layout->getOrCreateArea(self::PAGE_HEADER_AREA)
            ->setSlotName(Site::getPageHeaderSlotName());
        $layout->getOrCreateArea(self::PAGE_FOOTER_AREA)
            ->setSlotName(Site::getPageFooterSlotName());

        $layout->getOrCreateArea(self::MAIN_CONTENT_AREA);
        $layout->getOrCreateArea(self::PAGE_SIDE_AREA)
            ->setSlotName(Site::getSidebarName());

        $layout->getOrCreateArea(self::MAIN_SIDE_AREA)
            ->setSlotName(Site::getPageSideSlotName());
        $layout->getOrCreateArea(self::MAIN_HEADER_AREA)
            ->setSlotName("slot_main_header");
        $layout->getOrCreateArea(self::MAIN_FOOTER_AREA)
            ->setSlotName("slot_main_footer");

        return $layout;

    }

    public function getOrCreateArea($areaIdentifier): LayoutArea
    {
        $layoutArea = $this->layoutAreas[$areaIdentifier];
        if ($layoutArea === null) {
            $layoutArea = new LayoutArea($areaIdentifier);
            $this->layoutAreas[$areaIdentifier] = $layoutArea;
        }
        return $layoutArea;
    }

    /**
     * @throws ExceptionNotFound - if the layout resources were not found
     * @throws ExceptionBadSyntax - if the template xhtml is not valid
     */
    public function getHtmlPage(): string
    {
        $requestedPage = Page::createPageFromRequestedPage();

        global $ACT;
        switch ($ACT) {
            case "preview": // edit preview
            case "edit": // edit
            case "admin": // admin page
            case "media": // media manager
                /**
                 * Note: the secondary slot will not render because the act is not show
                 * This is for now not used by strap
                 */
                $layoutName = "hamburger";
                break;
            case "show":
                $layoutName = PageLayout::createFromPage($requestedPage)
                    ->getValueOrDefault();
                break;
            default:
            case "login": // login
            case "resendpwd": // passwd resend
            case "register": // register form
            case "profile": // profile form
            case "search": // search
            case "recent": // the revisions for the website
            case "index": // the website index
            case "diff": // diff between revisions
            case "revisions": // Known as old revisions (old version of the page)
                $layoutName = "median";
                break;
        }

        $layoutDirectory = DokuPath::createDokuPath(":layout:$layoutName:", DokuPath::COMBO_DRIVE);
        if (!FileSystems::exists($layoutDirectory)) {
            throw new ExceptionNotFound("The layout directory ($layoutName) does not exist at $layoutDirectory", self::CANONICAL);
        }


        /**
         * Css and Js
         */
        $snippetManager = PluginUtility::getSnippetManager();
        $layoutCssPath = $layoutDirectory->resolve("$layoutName.css");
        try {
            $content = FileSystems::getContent($layoutCssPath);
            $snippetManager->attachCssInternalStylesheetForRequest(self::CANONICAL, $content);
        } catch (ExceptionNotFound $e) {
            // not a problem
        }
        $layoutJsPath = $layoutDirectory->resolve("$layoutName.js");
        try {
            $content = FileSystems::getContent($layoutJsPath);
            $snippetManager->attachJavascriptInternalForRequest(self::CANONICAL, $content);
        } catch (ExceptionNotFound $e) {
            // not a problem
        }

        $layoutHtmlFileName = "$layoutName.html";
        $layoutHtmlPath = $layoutDirectory->resolve($layoutHtmlFileName);
        try {
            $html = FileSystems::getContent($layoutHtmlPath);
        } catch (ExceptionNotFound $e) {
            throw new ExceptionNotFound("The layout file ($layoutHtmlFileName) does not exist at $layoutHtmlPath", self::CANONICAL);
        }
        try {
            $htmlDocument = XmlDocument::createHtmlDocFromMarkup("<div>$html</div>");
        } catch (ExceptionBadSyntax $e) {
            throw new ExceptionBadSyntax("The html template file ($layoutHtmlFileName) is not valid. Error: {$e->getMessage()}", self::CANONICAL);
        }

        /**
         * Area
         */
        $layoutJsonPath = $layoutDirectory->resolve("$layoutName.json");
        try {
            $jsonString = FileSystems::getContent($layoutJsonPath);
        } catch (ExceptionNotFound $e) {
            throw new ExceptionNotFound("The layout file ($layoutName) does not exist at $layoutJsonPath", self::CANONICAL, 1, $e);
        }
        try {
            $json = Json::createFromString($jsonString);
        } catch (ExceptionBadSyntax $e) {
            throw new ExceptionBadSyntax("The layout file ($layoutJsonPath) could not be loaded as json. Error: {$e->getMessage()}", self::CANONICAL,1,$e);
        }
        $jsonArray = $json->toArray();


        $areas = self::areas;
        foreach ($areas as $areaName) {

            $layoutArea = $this->getOrCreateArea($areaName);

            $attributes = $jsonArray[$areaName];
            $tagAttributes = TagAttributes::createFromCallStackArray($attributes);

            // show
            $showArea = $tagAttributes->getBooleanValueAndRemoveIfPresent("show", true);
            $layoutArea->setShow($showArea);
            if ($showArea === false) {
                continue;
            }

            // Container
            if ($areaName === self::PAGE_CORE_AREA) {
                // Page Header and Footer have a bar that permits to set the container
                // Page core does not have any
                // It's by default contained for all layout
                $container = $tagAttributes->getValueAndRemoveIfPresent("container", true);
                if ($container) {
                    $container = PluginUtility::getConfValue(syntax_plugin_combo_container::DEFAULT_LAYOUT_CONTAINER_CONF, syntax_plugin_combo_container::DEFAULT_LAYOUT_CONTAINER_DEFAULT_VALUE);
                    $tagAttributes->addClassName(syntax_plugin_combo_container::getClassName($container));
                }
            }

            // relative
            // Relative positioning is important for the positioning of the pagetools (page-core), secedit button
            $tagAttributes->addClassName("position-relative");

            switch ($areaName) {
                case self::PAGE_FOOTER_AREA:
                case self::PAGE_HEADER_AREA:
                    // no print
                    $tagAttributes->addClassName("d-print-none");
                    // position relative to place the edit button
                    $tagAttributes->addClassName("position-relative");

                    try {
                        $closestPath = FileSystems::closest($requestedPage->getPath(), $layoutArea->getSlotName() . DokuPath::PAGE_FILE_TXT_EXTENSION);
                    } catch (ExceptionNotFound $e) {
                        $closestPath = self::getDefaultAreaContentPath($areaName);
                        if (!FileSystems::exists($closestPath)) {
                            $closestPath = null;
                            LogUtility::errorIfDevOrTest("The default $areaName page does not exist.");
                        }
                    }
                    $layoutArea->setPath($closestPath);
                    $showArea = $closestPath !== null;
                    break;
                case self::PAGE_CORE_AREA:
                    $tagAttributes->addClassName(tpl_classes());
                    $tagAttributes->addClassName("layout-$layoutName-combo");
                    $tagAttributes->addClassName("dokuwiki"); // used by Third party plugin
                    $showArea = true;
                    break;
                case self::MAIN_FOOTER_AREA:
                case self::PAGE_SIDE_AREA:
                case self::MAIN_SIDE_AREA:
                    $tagAttributes->addComponentAttributeValue("role", "complementary");
                    $tagAttributes->addClassName("d-print-none");
                    try {
                        $closestPath = FileSystems::closest($requestedPage->getPath(), $layoutArea->getSlotName() . DokuPath::PAGE_FILE_TXT_EXTENSION);
                    } catch (ExceptionNotFound $e) {
                        // ok
                    }
                    $showArea = $closestPath !== null && ($ACT === 'show');
                    break;
            }

            $layoutArea->setShow($showArea);
            if ($showArea && $closestPath !== null) {
                $layoutArea->setHtml($this->render($closestPath));
            }
            $layoutArea->setAttributes($tagAttributes->toHtmlArray());

        }
        return "";
    }


    private
    function render(Path $path)
    {
        try {
            $page = Page::createPageFromPathObject($path);
            $html = $page->toXhtml();
            return EditButton::replaceOrDeleteAll($html);
        } catch (Exception $e) {
            return "Rendering the slot ($path), returns an error. {$e->getMessage()}";
        }


    }


    /**
     * @throws ExceptionBadArgument - when the area name is unknown
     * @throws ExceptionCompile - when the strap template is not available
     */
    public static function getSlotNameForArea($area)
    {
        switch ($area) {
            case self::PAGE_HEADER_AREA:
                return Site::getPageHeaderSlotName();
            case self::PAGE_FOOTER_AREA:
                return Site::getPageFooterSlotName();
            default:
                throw new ExceptionBadArgument("The area ($area) is unknown");
        }
    }

    public static function getDefaultAreaContentPath($areaName): DokuPath
    {
        return DokuPath::createComboResource(":pages:$areaName.md");
    }
}
