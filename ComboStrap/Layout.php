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
    const AREAS = [
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
    private array $layoutAreas = [];

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
            throw new ExceptionBadSyntax("The layout file ($layoutJsonPath) could not be loaded as json. Error: {$e->getMessage()}", self::CANONICAL, 1, $e);
        }
        $jsonArray = $json->toArray();

        $htmlOutputByAreaName = [];
        foreach (self::AREAS as $areaName) {

            $attributes = $jsonArray[$areaName];
            $jsonConfiguration = TagAttributes::createFromCallStackArray($attributes);

            /**
             * If the id is not in the html template
             * we don't show it
             */
            try {
                $areaDomElement = $htmlDocument->querySelector("#$areaName");
            } catch (ExceptionBadSyntax $e) {
                LogUtility::internalError("The selector should not have a bad syntax");
                continue;
            } catch (ExceptionNotFound $e) {
                continue;
            }

            // Container
            if ($areaName === self::PAGE_CORE_AREA) {
                // Page Header and Footer have a bar that permits to set the container
                // Page core does not have any
                // It's by default contained for all layout
                $container = $jsonConfiguration->getValueAndRemoveIfPresent("container", true);
                if ($container) {
                    $container = PluginUtility::getConfValue(syntax_plugin_combo_container::DEFAULT_LAYOUT_CONTAINER_CONF, syntax_plugin_combo_container::DEFAULT_LAYOUT_CONTAINER_DEFAULT_VALUE);
                    $jsonConfiguration->addClassName(syntax_plugin_combo_container::getClassName($container));
                }
            }

            /**
             * Special Classes and attributes
             */
            // relative
            // Relative positioning is important for the positioning of the pagetools (page-core),
            // edit button, ...
            $areaDomElement->addClass("position-relative");
            switch ($areaName) {
                case self::PAGE_FOOTER_AREA:
                case self::PAGE_HEADER_AREA:
                    // no print
                    $areaDomElement->addClass("d-print-none");
                    break;
                case self::PAGE_CORE_AREA:
                    $areaDomElement->addClass(tpl_classes());
                    $areaDomElement->addClass("layout-$layoutName-combo");
                    $areaDomElement->addClass("dokuwiki"); // used by Third party plugin
                    break;
                case self::MAIN_FOOTER_AREA:
                case self::PAGE_SIDE_AREA:
                case self::MAIN_SIDE_AREA:
                    $areaDomElement->setAttribute("role", "complementary");
                    $areaDomElement->addClass("d-print-none");
                    break;
            }

            /**
             * Rendering
             */
            $layoutArea = $this
                ->getOrCreateArea($areaName);
            if ($layoutArea->isContainer()) {
                // no rendering for container area
                // this is a parent
                continue;
            }

            $layoutVariable = $layoutArea->getVariableName();
            $htmlOutputByAreaName[$layoutVariable] = $layoutArea
                ->render();
            /**
             * Add the template variable
             */
            $areaDomElement->appendTextNode('$' . $layoutVariable);

        }

        $htmlDocumentString = $htmlDocument->toHtml();
        return Template::create($htmlDocumentString)->setProperties($htmlOutputByAreaName)->render();


    }


}
