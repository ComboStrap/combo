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


        /**
         * Caching the slot and private namespace
         */
        $namesToHide = [];
        $namesToHide[] = SlotSystem::getSidebarName();
        $namesToHide[] = SlotSystem::getPageHeaderSlotName();
        $namesToHide[] = PluginUtility::COMBOSTRAP_NAMESPACE_NAME;
        $namesToHide[] = SlotSystem::getPageFooterSlotName();
        $namesToHide[] = SlotSystem::getMainSideSlotName();
        $namesToHide[] = SlotSystem::getMainFooterSlotName();
        $namesToHide[] = SlotSystem::getMainHeaderSlotName();
        // Remove empty string elements
        $namesToHidenotEmpty = array_filter($namesToHide, function ($value) {
            return trim($value) !== '';
        });
        $pattern = "(" . implode('|', $namesToHidenotEmpty) . ")";
        if (preg_match('/' . $pattern . '/ui', ':' . $event->data['id'])) {
            $event->data['hidden'] = true;
        }

    }


}
