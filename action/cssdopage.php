<?php


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\PluginUtility;
use ComboStrap\Site;


/**
 */
class action_plugin_combo_cssdopage extends DokuWiki_Action_Plugin
{


    public function register(Doku_Event_Handler $controller)
    {

        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'handleCssForDoAction');

    }

    /**
     * @param Doku_Event $event
     */
    public function handleCssForDoAction(Doku_Event &$event)
    {
        if (!Site::isStrapTemplate()) {
            return;
        }
        global $ACT;
        switch ($ACT) {
            case "media":
                PluginUtility::getSnippetManager()->attachCssSnippetForRequest("media");
                break;
        }


    }


}
