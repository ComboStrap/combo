<?php


use ComboStrap\PluginUtility;
use ComboStrap\TplConstant;

/**
 * Class action_plugin_combo_hidden
 * Hide page
 */
class action_plugin_combo_hiddenpage extends DokuWiki_Action_Plugin
{


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
         * Caching the strap bars and private namespace
         */
        $pattern = "(" . $conf['sidebar'] . "|" . PluginUtility::COMBOSTRAP_NAMESPACE_NAME;
        if ($conf['template'] == PluginUtility::TEMPLATE_STRAP_NAME) {
            $constantFile = __DIR__ . '/../../../tpl/strap/class/TplConstant.php';
            if (file_exists($constantFile)) {
                /** @noinspection PhpIncludeInspection */
                require_once($constantFile);
                $footer = tpl_getConf(TplConstant::CONF_FOOTER);
                $sidekick = tpl_getConf(TplConstant::CONF_SIDEKICK);
                $header = tpl_getConf(TplConstant::CONF_HEADER);
                $pattern .= "|" . $footer . "|" . $sidekick . "|" . $header;
            }
        }
        $pattern .= ")";
        if (preg_match('/' . $pattern . '/ui', ':' . $event->data['id'])) {
            $event->data['hidden'] = true;
        }

    }


}
