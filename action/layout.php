<?php

use ComboStrap\DokuPath;
use ComboStrap\EditButton;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionBadState;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotAuthorized;
use ComboStrap\ExceptionNotFound;
use ComboStrap\FileSystems;
use ComboStrap\Layout;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PageLayout;
use ComboStrap\Path;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\TagAttributes;


/**
 * Layout
 *
 * See also: https://1linelayouts.glitch.me/ and https://www.cssportal.com/layout-generator/layout.php
 *
 * Two basic layouts for the web: fixed or liquid
 * A liquid design (also referred to as a fluid or dynamic design) fills the entire browser window by using percentages
 * rather than fixed pixel values to define the width / height
 *
 * dimension =
 *   "fluid" = max-width / min-height
 *   "contained" =
 *
 * In fluid web design, the widths of page elements are set proportional to the width of the screen or browser window.
 * A fluid website expands or contracts based on the width of the current viewport.
 *
 * Contained (ie fixed)
 * https://getbootstrap.com/docs/5.0/layout/containers/
 *
 */
class action_plugin_combo_layout extends DokuWiki_Action_Plugin
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


    public function register(Doku_Event_Handler $controller)
    {

        /**
         * https://www.dokuwiki.org/devel:event:init_lang_load
         */
        $controller->register_hook('COMBO_LAYOUT', 'BEFORE', $this, 'layout', array());


    }

    /**
     * @param Doku_Event $event
     * @param $param
     * @return void
     */
    public function layout(Doku_Event $event, $param)
    {
        /**
         * @var Layout $layoutObject
         */
        $layoutObject = &$event->data;

        $requestedPage = Page::createPageFromRequestedPage();

        global $ACT;
        switch ($ACT) {
            case "preview": // edit preview
            case "edit": // edit
            case "admin": // admin page
            case "media": // media manager
                // Note: the secondary slot will not render because the act is not show
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
            LogUtility::error("The layout directory ($layoutName) does not exist at $layoutDirectory", self::CANONICAL);
            return;
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


        /**
         * Area
         */
        $layoutJsonPath = $layoutDirectory->resolve("$layoutName.json");
        try {
            $jsonString = FileSystems::getContent($layoutJsonPath);
        } catch (ExceptionNotFound $e) {
            LogUtility::error("The layout file ($layoutName) does not exist at $layoutJsonPath", self::CANONICAL);
            return;
        }
        try {
            $json = \ComboStrap\Json::createFromString($jsonString);
        } catch (ExceptionCompile $e) {
            LogUtility::error("The layout file ($layoutJsonPath) could not be loaded as json. Error: {$e->getMessage()}", self::CANONICAL);
            return;
        }
        $jsonArray = $json->toArray();


        $areas = self::areas;
        foreach ($areas as $areaName) {

            $layoutArea = $layoutObject->getOrCreateArea($areaName);

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

            $closesPath = null;
            switch ($areaName) {
                case self::PAGE_FOOTER_AREA:
                case self::PAGE_HEADER_AREA:
                    // no print
                    $tagAttributes->addClassName("d-print-none");
                    // position relative to place the edit button
                    $tagAttributes->addClassName("position-relative");

                    try {
                        $closesPath = FileSystems::closest($requestedPage->getPath(), $layoutArea->getSlotName() . DokuPath::PAGE_FILE_TXT_EXTENSION);
                    } catch (ExceptionNotFound $e) {
                        $closesPath = self::getDefaultAreaContentPath($areaName);
                        if (!FileSystems::exists($closesPath)) {
                            $closesPath = null;
                            LogUtility::errorIfDevOrTest("The default $areaName page could does not exist.");
                        }
                    }
                    $showArea = $closesPath !== null;
                    break;
                case self::PAGE_CORE_AREA:
                    $tagAttributes->addClassName(tpl_classes());
                    $tagAttributes->addClassName("layout-$layoutName-combo");
                    $showArea = true;
                    break;
                case self::MAIN_FOOTER_AREA:
                case self::PAGE_SIDE_AREA:
                case self::MAIN_SIDE_AREA:
                    $tagAttributes->addComponentAttributeValue("role", "complementary");
                    $tagAttributes->addClassName("d-print-none");
                    try {
                        $closesPath = FileSystems::closest($requestedPage->getPath(), $layoutArea->getSlotName() . DokuPath::PAGE_FILE_TXT_EXTENSION);
                    } catch (ExceptionNotFound $e) {
                        // ok
                    }
                    $showArea = $closesPath !== null && ($ACT === 'show');
                    break;
            }

            $layoutArea->setShow($showArea);
            if ($showArea && $closesPath !== null) {
                $layoutArea->setHtml($this->render($closesPath));
            }
            $layoutArea->setAttributes($tagAttributes->toHtmlArray());

        }

    }

    private function render(Path $path)
    {
        try {
            $page = Page::createPageFromPathObject($path);
            $html = $page->toXhtml();
            return EditButton::replaceOrDeleteAll($html);
        } catch (Exception $e) {
            return "Rendering the slot ($path), returns an error. {$e->getMessage()}";
        }



    }


}



