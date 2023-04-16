<?php


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\BrandingColors;
use ComboStrap\ColorSystem;
use ComboStrap\ExecutionContext;
use ComboStrap\PluginUtility;
use ComboStrap\Site;


/**
 */
class action_plugin_combo_docss extends DokuWiki_Action_Plugin
{


    public function register(Doku_Event_Handler $controller)
    {

        /**
         * See {@link \ComboStrap\SnippetSystem::attachCssInternalStylesheet()}
         * for more explanation on the choice of the event
         */
        $controller->register_hook('ACTION_HEADERS_SEND', 'BEFORE', $this, 'handleCssForDoAction');

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
                PluginUtility::getSnippetManager()->attachCssInternalStylesheet("do-media");
                break;
            case "edit":
            case "preview":
                PluginUtility::getSnippetManager()->attachCssInternalStylesheet("do-edit");
                break;
            case "admin":
                $defaultColor = "black";
                $config = ExecutionContext::getActualOrCreateFromEnv()->getConfig();
                $iconColor = $config->getPrimaryColorOrDefault($defaultColor);
                $colorText = ColorSystem::toTextColor($iconColor);
                $css = <<<EOF
ul.admin_tasks, ul.admin_plugins {
    list-style: none;
}
ul.admin_tasks li, ul.admin_plugins li{
    margin: 0.5rem;
}
ul.admin_tasks a, ul.admin_plugins a {
    text-decoration: none;
    color: black
}
ul.admin_tasks a:hover, ul.admin_plugins a:hover{
    text-decoration: underline;
    color: {$colorText->toRgbHex()};
}
.icon svg {
    color: {$iconColor->toRgbHex()};
    fill: {$iconColor->toRgbHex()};
}
EOF;
                PluginUtility::getSnippetManager()->attachCssInternalStylesheet("do-admin", $css);
                break;
        }


    }


}
