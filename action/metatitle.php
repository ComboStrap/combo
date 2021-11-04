<?php

use ComboStrap\Analytics;
use ComboStrap\Page;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\XhtmlUtility;


/**
 * Class action_plugin_combo_metatitle
 * Set and manage the meta title
 * The event is triggered in the strap template
 */
class action_plugin_combo_metatitle extends DokuWiki_Action_Plugin
{


    const TITLE_SEPARATOR = ' | ';

    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('TPL_TITLE_OUTPUT', 'BEFORE', $this, 'handleTitle', array());
    }

    function handleTitle(&$event, $param)
    {
        $event->data = self::getTitle();
    }

    static function getTitle(): string
    {

        // Root Home page
        $currentPage = Page::createPageFromCurrentId();
        if ($currentPage->isRootHomePage()) {
            $pageTitle = Site::getTagLine();
        } else {
            $pageTitle = $currentPage->getTitleNotEmpty();
        }
        if (!empty(Site::getName())) {
            $pageTitle .= self::TITLE_SEPARATOR . Site::getName();
        }

        return PluginUtility::htmlEncode($pageTitle);
    }
}
