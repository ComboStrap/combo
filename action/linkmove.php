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
         * The handlers is the name of the component (ie refers to the {@link syntax_plugin_combo_link} handler)
         * and 'rewrite_combo' to the below method
         */
        $event->data['handlers'][syntax_plugin_combo_link::COMPONENT] = array($this, 'rewrite_combo');
    }

    /**
     *
     * @param $match
     * @param $state
     * @param $pos
     * @param $plugin
     * @param helper_plugin_move_handler $handler
     */
    public function rewrite_combo($match, $state, $pos, $plugin, helper_plugin_move_handler $handler)
    {
        /**
         * The original move method
         * is {@link helper_plugin_move_handler::internallink()}
         * $handler->internallink($match, $state, $pos);
         */
        if ($state == DOKU_LEXER_ENTER) {
            $ref = LinkUtility::parse($match)[LinkUtility::ATTRIBUTE_REF];
            $link = new LinkUtility($ref);
            if ($link->getType() == LinkUtility::TYPE_INTERNAL) {

                $new_id = $handler->resolveMoves($link->getId(), 'page');
                if ($link->isRelative()) {
                    $new_id = $handler->relativeLink($link->getId(), $new_id, 'page');
                }
                if ($link->getId() == $new_id) {
                    $handler->calls .= $match;
                } else {
                    if (!empty($link->getQueries())) {
                        $new_id .= '?' . $link->getQueries();
                    }

                    if (!empty($link->getFragment())) {
                        $new_id .= '#' . $link->getFragment();
                    }

                    $handler->calls .= '[[' . $new_id;
                }
            }
        } else {

            $handler->calls .= $match;

        }
    }


}
