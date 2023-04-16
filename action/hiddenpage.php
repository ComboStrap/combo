<?php


use ComboStrap\ExceptionCompile;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\SlotSystem;
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
        $pattern = "(" . SlotSystem::getSidebarName() . "|" . PluginUtility::COMBOSTRAP_NAMESPACE_NAME;
        $pattern .= "|" . SlotSystem::getPageHeaderSlotName();
        $pattern .= "|" . SlotSystem::getPageFooterSlotName();
        $pattern .= "|" . SlotSystem::getMainSideSlotName();
        $pattern .= "|" . SlotSystem::getMainFooterSlotName();
        $pattern .= "|" . SlotSystem::getMainHeaderSlotName();
        $pattern .= ")";
        if (preg_match('/' . $pattern . '/ui', ':' . $event->data['id'])) {
            $event->data['hidden'] = true;
        }

    }


}
