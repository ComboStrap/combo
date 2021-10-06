<?php

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\File;
use ComboStrap\LinkUtility;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\Site;


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
        $controller->register_hook('PLUGIN_MOVE_PAGE_RENAME', 'AFTER', $this, 'handle_rename_before', array());
        $controller->register_hook('PLUGIN_MOVE_PAGE_RENAME', 'AFTER', $this, 'handle_rename_after', array());
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
    function handle_rename_before(Doku_Event $event, $params)
    {
        /**
         * Check that the lock file is not older than 10 minutes
         */
        $lockFile = File::createFromPath(Site::getDataDirectory() . "/locks_plugin_move.lock");
        if ($lockFile->exists()) {
            $lockFileDateTimeModified = $lockFile->getModifiedTime();
            $lockFileModifiedTimestamp = $lockFileDateTimeModified->getTimestamp();
            $now = time();

            /**
             * Lock file bigger than 5 minutes
             * Is not really possible
             */
            $ageInMinute = ($now - $lockFileModifiedTimestamp)/60;
            if ($ageInMinute > 5) {
                $event->preventDefault();
                LogUtility::msg("The move lockfile ($lockFile) exists and is older than 10 minutes (exactly $ageInMinute minutes), you should finish the first move or delete this file before a move. The Move was canceled.");
            }
        }

    }

    /**
     * Handle the rename of a page
     * @param Doku_Event $event - https://www.dokuwiki.org/plugin:move#for_plugin_authors
     * @param $params
     *
     */
    function handle_rename_after(Doku_Event $event, $params)
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
