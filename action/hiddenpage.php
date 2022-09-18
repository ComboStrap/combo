<?php


use ComboStrap\ExceptionCompile;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\TplUtility;

/**
 * Class action_plugin_combo_hidden
 * Hide page
 *
 *
 * More on the official [[doku>config:hidepages|DokuWiki documentation]]
 */
class action_plugin_combo_hiddenpage extends DokuWiki_Action_Plugin
{


    const CANONICAL = "";

    public function register(Doku_Event_Handler $controller)
    {
        /**
         * https://www.dokuwiki.org/devel:event:pageutils_id_hidepage
         */
        $controller->register_hook('PAGEUTILS_ID_HIDEPAGE', 'BEFORE', $this, 'handleIsHidden', array());
    }

    function handleIsHidden(&$event, $param)
    {
        global $conf;

        /**
         * Caching the slot and private namespace
         */
        $pattern = "(" . Site::getSidebarName() . "|" . PluginUtility::COMBOSTRAP_NAMESPACE_NAME;
        $pattern .= "|" . Site::getPageHeaderSlotName();
        $pattern .= "|" . Site::getPageFooterSlotName();
        $pattern .= "|" . Site::getMainSideSlotName();
        $pattern .= "|" . Site::getMainFooterSlotName();
        $pattern .= "|" . Site::getMainHeaderSlotName();
        $pattern .= ")";
        if (preg_match('/' . $pattern . '/ui', ':' . $event->data['id'])) {
            $event->data['hidden'] = true;
        }

    }


}
