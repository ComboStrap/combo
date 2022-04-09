<?php

use ComboStrap\DatabasePageRow;
use ComboStrap\ExceptionNotFound;
use ComboStrap\FileSystems;
use ComboStrap\Layout;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PageLayout;
use ComboStrap\PageUrlPath;
use ComboStrap\PluginUtility;


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
        if (in_array($ACT, ["login", "resendpwd", "register", "profile"])) {
            $layoutName = "median";
        } else {
            $requestedPage = Page::createPageFromRequestedPage();
            $layoutName = PageLayout::createFromPage($requestedPage)
                ->getValueOrDefault();
        }

        try {
            $layoutObject->load($layoutName);
        } catch (ExceptionNotFound $e) {
            LogUtility::error("Error while loading the layout ($layoutName). Error: {$e->getMessage()}", self::CANONICAL);
        }



    }


}



