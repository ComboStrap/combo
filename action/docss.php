<?php


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\Color;
use ComboStrap\PluginUtility;
use ComboStrap\Site;


/**
 */
class action_plugin_combo_docss extends DokuWiki_Action_Plugin
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
                PluginUtility::getSnippetManager()->attachCssSnippetForRequest("do-media");
                break;
            case "admin":
                $iconColor = Site::getPrimaryColor("black");

                $css = <<<EOF
ul.admin_tasks, ul.admin_plugins {
    list-style: none;
}
.icon svg {
    color: {$iconColor->toRgbHex()};
    fill: {$iconColor->toRgbHex()};
}
a {
    color: {$iconColor->shade(Color::TEXT_BOOTSTRAP_WEIGHT)->toRgbHex()};
}
EOF;
                PluginUtility::getSnippetManager()->attachCssSnippetForRequest("do-admin", $css);
                break;
        }


    }


}
