<?php

use ComboStrap\LinkUtility;
use ComboStrap\PluginUtility;

if (!defined('DOKU_INC')) die();
require_once(__DIR__ . '/../class/PluginUtility.php');
require_once(__DIR__ . '/../class/LinkUtility.php');

/**
 * Class action_plugin_combo_move
 * Handle the move of a page in order to update the link
 */
class action_plugin_combo_linkmove extends DokuWiki_Action_Plugin
{

    /**
     * As explained https://www.dokuwiki.org/plugin:move
     * @param Doku_Event_Handler $controller
     */
    function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('PLUGIN_MOVE_HANDLERS_REGISTER', 'BEFORE', $this, 'handle_move', array());
    }

    /**
     * Handle the move of a page
     * @param Doku_Event $event
     * @param $params
     */
    function handle_move(Doku_Event $event, $params)
    {
        /**
         * 'combo_link' refers to the {@link syntax_plugin_combo_link} handler
         * 'rewrite_combo' to the below method
         */
        $event->data['handlers']['combo_link'] = array($this, 'rewrite_combo');
    }

    /**
     *
     * @param $match
     * @param $state
     * @param $pos
     * @param $plugin
     * @param helper_plugin_move_handler $handler
     * @return bool
     */
    public function rewrite_combo($match, $state, $pos, $plugin, helper_plugin_move_handler $handler)
    {
        /**
         * We call the original move method
         * that supports Link rewriting
         */
        $handler->internallink($match, $state, $pos);
        return '';

    }


}
