<?php

use ComboStrap\Page;
use ComboStrap\PluginUtility;
use ComboStrap\Site;


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

        // Page Title
        // Root Home page
        $currentPage = Page::createPageFromGlobalDokuwikiId();
        $pageTitle = $currentPage->getTitleOrDefault();

        // Namespace name
        $parentPage = $currentPage->getParentPage();
        if($parentPage!=null){
            $pageTitle .= self::TITLE_SEPARATOR . $parentPage->getNameOrDefault();
        }
        // Site name
        if (!empty(Site::getName())) {
            $pageTitle .= self::TITLE_SEPARATOR . Site::getName();
        }

        return PluginUtility::htmlEncode($pageTitle);
    }
}
