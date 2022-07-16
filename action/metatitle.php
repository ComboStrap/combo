<?php

use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\Html;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\PluginUtility;
use ComboStrap\Site;


/**
 * Class action_plugin_combo_metatitle
 * Set and manage the title of an HTML page
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
        $event->data = self::getHtmlTitle();
    }


    static function getHtmlTitle(): string
    {

        // Page Title
        // Root Home page
        $currentPage = MarkupPath::createFromRequestedPage();

        $pageTitle = $currentPage->getTitleOrDefault();

        // Namespace name
        try {
            $parentPage = $currentPage->getParent();
            $pageTitle .= self::TITLE_SEPARATOR . $parentPage->getNameOrDefault();
        } catch (ExceptionNotFound $e) {
            // no parent
        }

        // Site name
        if (!empty(Site::getName())) {
            $pageTitle .= self::TITLE_SEPARATOR . Site::getName();
        }

        return Html::encode($pageTitle);

    }
}
