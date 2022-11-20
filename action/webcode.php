<?php


use ComboStrap\Identity;
use ComboStrap\PluginUtility;

if (!defined('DOKU_INC')) die();

/**
 *
 */
class  action_plugin_combo_webcode extends DokuWiki_Action_Plugin
{



    function register(Doku_Event_Handler $controller)
    {

        /**
         * To enforce security
         */
        $controller->register_hook('COMMON_WIKIPAGE_SAVE', 'BEFORE', $this, '_enforceSecurity');

    }


    /**
     * @param $event Doku_Event https://www.dokuwiki.org/devel:event:common_wikipage_save
     * @return void
     */
    function _enforceSecurity(Doku_Event &$event)
    {

        $data = $event->data;
        $text = $data["newContent"];
        $pattern = PluginUtility::getContainerTagPattern(syntax_plugin_combo_webcode::TAG);
        $result = preg_match("/" . $pattern . "/ms", $text);
        if ($result === 0) {
            return;
        }

        $isAdmin = Identity::isAdmin();
        $isMember = Identity::isMember("@" . action_plugin_combo_svg::CONF_SVG_UPLOAD_GROUP_NAME);
        if (!($isAdmin || $isMember)) {
            $event->preventDefault();
        }

    }

}
