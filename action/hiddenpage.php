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
        $pattern = "(" . $conf['sidebar'] . "|" . PluginUtility::COMBOSTRAP_NAMESPACE_NAME;
        if ($conf['template'] == PluginUtility::TEMPLATE_STRAP_NAME) {
            try {
                Site::loadStrapUtilityTemplateIfPresentAndSameVersion();
            } catch (ExceptionCompile $e) {
                return;
            }

            $pattern .= "|" . TplUtility::getFooterSlotPageName();

            $pattern .= "|" . TplUtility::getHeaderSlotPageName();
            $name = TplUtility::getSideKickSlotPageName();
            if (!empty($name)) {
                $pattern .= "|" . $name;
            }

        }
        $pattern .= "|" . Site::getPrimaryFooterSlotName();
        $pattern .= "|" . Site::getPrimaryHeaderSlotName();
        $pattern .= "|" . Site::getPrimarySideSlotName();
        $pattern .= ")";
        if (preg_match('/' . $pattern . '/ui', ':' . $event->data['id'])) {
            $event->data['hidden'] = true;
        }

    }


}
