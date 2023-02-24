<?php


use ComboStrap\Identity;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\XmlTagProcessing;

if (!defined('DOKU_INC')) die();

/**
 *
 */
class  action_plugin_combo_webcode extends DokuWiki_Action_Plugin
{


    const YOU_DON_T_HAVE_THE_RIGHT = "You don't have the right to save a webcode component.";

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
        $pattern = XmlTagProcessing::getContainerTagPattern(syntax_plugin_combo_webcode::TAG);
        $result = preg_match("/" . $pattern . "/ms", $text);
        if ($result === 0) {
            return;
        }

        $isAdmin = Identity::isAdmin();
        if ($isAdmin) {
            return;
        }

        $group = "@" . action_plugin_combo_svg::CONF_SVG_UPLOAD_GROUP_NAME;
        $isMember = Identity::isMember($group);
        if ($isMember) {
            return;
        }

        LogUtility::warning(self::YOU_DON_T_HAVE_THE_RIGHT . " You should be admin or part of the ($group) group.");
        $event->preventDefault();


    }

}
