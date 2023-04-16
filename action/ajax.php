<?php

use ComboStrap\Api\ApiRouter;

require_once(__DIR__ . '/../vendor/autoload.php');


/**
 * Ajax search data
 */
class action_plugin_combo_ajax extends DokuWiki_Action_Plugin
{


    /**
     * @param Doku_Event_Handler $controller
     */
    function register(Doku_Event_Handler $controller)
    {

        /**
         * The ajax api to return data
         * We do a AFTER because {@link action_plugin_move_rename} use the before to
         * set data to check if it will add a menu item
         */
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'ajaxHandler');

    }

    /**
     * @param Doku_Event $event
     */
    function ajaxHandler(Doku_Event $event)
    {

        ApiRouter::handle($event);

    }


}
