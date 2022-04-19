<?php

use ComboStrap\DokuPath;
use ComboStrap\EditButton;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\FileSystems;
use ComboStrap\Layout;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PageLayout;
use ComboStrap\PluginUtility;
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

    const rowAreas = [
        self::PAGE_CORE_AREA,
        self::PAGE_HEADER_AREA,
        self::PAGE_FOOTER_AREA,
    ];

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

        // for the identity forms
        global $ACT;
        switch ($ACT) {
            case "login":
            case "resendpwd":
            case "register":
            case "profile":
                $layoutName = "median";
                break;
            case "preview":
                $layoutName = "landing";
                break;
            case "show":
                $requestedPage = Page::createPageFromRequestedPage();
                $layoutName = PageLayout::createFromPage($requestedPage)
                    ->getValueOrDefault();
                break;
            default:
                return;
        }

        $layoutDirectory = DokuPath::createDokuPath(":layout:$layoutName:", DokuPath::COMBO_DRIVE);
        if (!FileSystems::exists($layoutDirectory)) {
            LogUtility::error("The layout directory ($layoutName) does not exist at $layoutDirectory", self::CANONICAL);
            return;
        }


        /**
         * Css and Js
         */
        $layoutCssPath = $layoutDirectory->resolve("$layoutName.css");
        try {
            $content = FileSystems::getContent($layoutCssPath);
            PluginUtility::getSnippetManager()->attachCssInternalStylesheetForRequest(self::CANONICAL, $content);
        } catch (ExceptionNotFound $e) {
            // not a problem
        }
        $layoutJsPath = $layoutDirectory->resolve("$layoutName.js");
        try {
            $content = FileSystems::getContent($layoutJsPath);
            PluginUtility::getSnippetManager()->attachJavascriptInternalForRequest(self::CANONICAL, $content);
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

            // container
            if (in_array($areaName, self::rowAreas)) {
                $container = $tagAttributes->getValueAndRemoveIfPresent("container", true);
                if ($container) {
                    $container = PluginUtility::getConfValue(syntax_plugin_combo_container::DEFAULT_LAYOUT_CONTAINER_CONF, syntax_plugin_combo_container::DEFAULT_LAYOUT_CONTAINER_DEFAULT_VALUE);
                    $containerPrefix = "";
                    if ($container !== "sm") {
                        $containerPrefix = "-$container";
                    }
                    $tagAttributes->addClassName("container{$containerPrefix}");
                }
            }

            // relative
            // Relative positioning is important for the positioning of the pagetools (page-core), secedit button
            $tagAttributes->addClassName("position-relative");

            $wikiIdArea = "";
            switch ($areaName) {
                case self::PAGE_FOOTER_AREA:
                case self::PAGE_HEADER_AREA:
                    $tagAttributes->addClassName("d-print-none");
                    // no print
                    $tagAttributes->addClassName("position-relative");
                    //position relative to place the edit button
                    $wikiIdArea = page_findnearest($layoutArea->getSlotName());
                    $showArea = $wikiIdArea !== false;
                    break;
                case self::PAGE_CORE_AREA:
                    $tagAttributes->addClassName(tpl_classes());
                    break;
                case self::MAIN_FOOTER_AREA:
                case self::PAGE_SIDE_AREA:
                case self::MAIN_SIDE_AREA:
                    $tagAttributes->addComponentAttributeValue("role", "complementary");
                    $tagAttributes->addClassName("d-print-none");
                    $wikiIdArea = page_findnearest($layoutArea->getSlotName());
                    $showArea = $wikiIdArea !== false && ($ACT === 'show');
                    break;
            }

            $layoutArea->setShow($showArea);
            if ($showArea) {
                $layoutArea->setHtml($this->render($wikiIdArea));
            }
            $layoutArea->setAttributes($tagAttributes->toHtmlArray());

        }

    }

    private function render(string $wikiId)
    {
        try {
            $page = Page::createPageFromId($wikiId);
            $html = $page->toXhtml();
            $finalHtml = EditButton::replaceAll($html);
        } catch (Exception $e) {
            $finalHtml = "Rendering the slot ($wikiId), returns an error. {$e->getMessage()}";
        }
        return $finalHtml;
    }


}



