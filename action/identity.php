<?php

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\Identity;


/**
 *
 */
class action_plugin_combo_identity extends DokuWiki_Action_Plugin
{


    public function register(Doku_Event_Handler $controller)
    {

        /**
         * Add logged in indicator for Javascript
         */
        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, 'handleAnonymousJsIndicator');


    }





    /**
     * @noinspection SpellCheckingInspection
     * Adding an information to know if the user is signed or not
     */
    function handleAnonymousJsIndicator(&$event, $param)
    {

        global $JSINFO;
        if (!Identity::isLoggedIn()) {
            $navigation = Identity::JS_NAVIGATION_ANONYMOUS_VALUE;
        } else {
            $navigation = Identity::JS_NAVIGATION_SIGNED_VALUE;
        }
        $JSINFO[Identity::JS_NAVIGATION_INDICATOR] = $navigation;


    }


}
