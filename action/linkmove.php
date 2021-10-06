<?php

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\LinkUtility;
use ComboStrap\Page;


/**
 * Class action_plugin_combo_move
 * Handle the move of a page in order to update the link
 */
class action_plugin_combo_linkmove extends DokuWiki_Action_Plugin
{

    /**
     * As explained https://www.dokuwiki.org/plugin:move#support_for_other_plugins
     * @param Doku_Event_Handler $controller
     */
    function register(Doku_Event_Handler $controller)
    {

        /**
         * To rewrite the page meta in the database
         */
        $controller->register_hook('PLUGIN_MOVE_PAGE_RENAME', 'AFTER', $this, 'handle_rename', array());
        /**
         * To rewrite the link
         */
        $controller->register_hook('PLUGIN_MOVE_HANDLERS_REGISTER', 'BEFORE', $this, 'handle_link', array());
    }

    /**
     * Handle the rename of a page
     * @param Doku_Event $event - https://www.dokuwiki.org/plugin:move#for_plugin_authors
     * @param $params
     *
     */
    function handle_rename(Doku_Event $event, $params)
    {
        /**
         * $event->data
         * src_id ⇒ string – the original ID of the page
         * dst_id ⇒ string – the new ID of the page
         */
        $id = $event->data["src_id"];
        $targetId = $event->data["dst_id"];
        $page = Page::createPageFromId($id);
        $page->getDatabasePage()->moveTo($targetId);

    }

    /**
     * Handle the move of a page
     * @param Doku_Event $event
     * @param $params
     */
    function handle_link(Doku_Event $event, $params)
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
         *
         */
        if ($state == DOKU_LEXER_ENTER) {
            $ref = LinkUtility::parse($match)[LinkUtility::ATTRIBUTE_REF];
            $link = new LinkUtility($ref);
            if ($link->getType() == LinkUtility::TYPE_INTERNAL) {

                $handler->internallink($match, $state, $pos);
                $suffix = "]]";
                if (substr($handler->calls, -strlen($suffix)) == $suffix) {
                    $handler->calls = substr($handler->calls, 0, strlen($handler->calls) - strlen($suffix));
                }

            } else {

                // Other type of links
                $handler->calls .= $match;

            }
        } else {

            // Description and ending
            $handler->calls .= $match;

        }
    }


}
