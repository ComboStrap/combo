<?php

use ComboStrap\DatabasePageRow;
use ComboStrap\DokuPath;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\FileSystems;
use ComboStrap\Layout;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PageLayout;
use ComboStrap\PageUrlPath;
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


        $areas = ["page-core"];
        foreach ($areas as $areaName) {


            $layoutArea = $layoutObject->getOrCreateArea($areaName);

            $attributes = $jsonArray[$areaName];
            $tagAttributes = TagAttributes::createFromCallStackArray($attributes);

            // show
            $show = $tagAttributes->getBooleanValueAndRemoveIfPresent("show", true);
            $layoutArea->setShow($show);

            // container
            $container = $tagAttributes->getValueAndRemoveIfPresent("container", true);
            if ($container) {
                $container = PluginUtility::getConfValue(syntax_plugin_combo_container::DEFAULT_LAYOUT_CONTAINER_CONF, syntax_plugin_combo_container::DEFAULT_LAYOUT_CONTAINER_DEFAULT_VALUE);
                $containerPrefix = "";
                if ($container !== "sm") {
                    $containerPrefix = "-$container";
                }
                $tagAttributes->addClassName("container{$containerPrefix}");
            }

            // relative
            // Relative positioning is important for the positioning of the pagetools (page-core), secedit button
            $tagAttributes->addClassName("position-relative");

            if ($areaName === "page-core") {
                $tagAttributes->addClassName(tpl_classes());
            }

            $layoutArea->setAttributes($tagAttributes->toCallStackArray());


        }


    }


}



