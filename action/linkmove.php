<?php

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\Alias;
use ComboStrap\Aliases;
use ComboStrap\DatabasePageRow;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionRuntime;
use ComboStrap\File;
use ComboStrap\FileSystems;
use ComboStrap\LinkMarkup;
use ComboStrap\LogUtility;
use ComboStrap\MarkupRef;
use ComboStrap\MetadataDbStore;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\MarkupPath;
use ComboStrap\PageId;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\WikiRequest;


/**
 * Class action_plugin_combo_move
 * Handle the move of a page in order to update:
 *   * the link
 *   * the data in the database
 */
class action_plugin_combo_linkmove extends DokuWiki_Action_Plugin
{


    const CANONICAL = "move";

    private static function checkAndSendAMessageIfLockFilePresent(): bool
    {
        $lockFile = Site::getDataDirectory()->resolve("locks_plugin_move.lock");
        if (!FileSystems::exists($lockFile)) {
            return false;
        }
        $lockFileDateTimeModified = FileSystems::getModifiedTime($lockFile);
        $lockFileModifiedTimestamp = $lockFileDateTimeModified->getTimestamp();
        $now = time();

        $distance = $now - $lockFileModifiedTimestamp;
        $lockFileAgeInMinute = ($distance) / 60;
        if ($lockFileAgeInMinute > 5) {
            LogUtility::msg("The move lockfile ($lockFile) exists and is older than 5 minutes (exactly $lockFileAgeInMinute minutes). If you are no more in a move, you should delete this file otherwise it will disable the move of page and the cache.");
            return true;
        }
        return false;
    }

    /**
     * As explained https://www.dokuwiki.org/plugin:move#support_for_other_plugins
     * @param Doku_Event_Handler $controller
     */
    function register(Doku_Event_Handler $controller)
    {

        /**
         * To rewrite the page meta in the database
         */
        $controller->register_hook('PLUGIN_MOVE_PAGE_RENAME', 'BEFORE', $this, 'handle_rename_before', array());
        $controller->register_hook('PLUGIN_MOVE_PAGE_RENAME', 'AFTER', $this, 'handle_rename_after', array());

        /**
         * To rewrite the link
         */
        $controller->register_hook('PLUGIN_MOVE_HANDLERS_REGISTER', 'BEFORE', $this, 'handle_link', array());


        /**
         * Check for the presence of a lock file
         */
        $controller->register_hook('PARSER_WIKITEXT_PREPROCESS', 'BEFORE', $this, 'check_lock_file_age', array());


    }

    /**
     * @param Doku_Event $event
     * @param $params
     *
     * When a lock file is present,
     * the move plugin will purge the data in {@link action_plugin_move_rewrite::handle_cache()}
     * making the rendering fucking slow
     * We check that the lock file is not
     */
    function check_lock_file_age(Doku_Event $event, $params)
    {
        self::checkAndSendAMessageIfLockFilePresent();

    }

    /**
     * Handle the path modification of a page
     * @param Doku_Event $event - https://www.dokuwiki.org/plugin:move#for_plugin_authors
     * @param $params
     *
     */
    function handle_rename_before(Doku_Event $event, $params)
    {
        /**
         * Check that the lock file is not older
         * Lock file bigger than 5 minutes
         * Is not really possible
         */
        $result = self::checkAndSendAMessageIfLockFilePresent();
        if ($result === true) {
            $event->preventDefault();
            LogUtility::msg("The move lock file is present, the move was canceled.");
        }

    }

    /**
     * Handle the path modification of a page after
     *
     * The metadata file should also have been moved
     *
     * @param Doku_Event $event - https://www.dokuwiki.org/plugin:move#for_plugin_authors
     * @param $params
     *
     */
    function handle_rename_after(Doku_Event $event, $params)
    {
        /**
         *
         * $event->data
         * src_id ⇒ string – the original ID of the page
         * dst_id ⇒ string – the new ID of the page
         */
        $sourceId = $event->data["src_id"];
        $targetId = $event->data["dst_id"];
        try {

            /**
             * Update the dokuwiki id and path
             */
            $databasePage = DatabasePageRow::createFromDokuWikiId($sourceId);
            if (!$databasePage->exists()) {
                return;
            }
            $databasePage->updatePathAndDokuwikiId($targetId);

            /**
             * Check page id
             */
            $targetPage = MarkupPath::createPageFromId($targetId);
            $targetPageId = PageId::createForPage($targetPage);
            $targetPageIdValue = $targetPageId->getValueFromStore();
            $databasePageIdValue = $databasePage->getPageId();

            if ($databasePageIdValue !== $targetPageIdValue) {
                // this should never happened in test/dev
                $targetPageId->setValueForce($targetPageIdValue);
            }

            /**
             * Add the alias
             */
            Aliases::createForPage($targetPage)
                ->addAlias($sourceId)
                ->setWriteStore(MetadataDokuWikiStore::class)
                ->sendToWriteStore()
                ->persist()
                ->setReadStore(MetadataDbStore::class)
                ->sendToWriteStore()
                ->persist();


        } catch (Exception $exception) {
            // We catch the errors if any to not stop the move
            // There is no transaction feature (it happens or not)
            $message = "An error occurred during the move replication to the database. Error message was: " . $exception->getMessage();
            if (PluginUtility::isDevOrTest()) {
                throw new RuntimeException($exception);
            } else {
                LogUtility::msg($message, LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            }
        }

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
         * The goal is to recreate the document
         * in the {@link helper_plugin_move_handler::$calls}
         * variable (this is a string, not an array of calls)
         *
         * We got the {@link syntax_plugin_combo_link::handle() render match of the handle function}
         *
         * Unfortunately, all environemnt propertes of {@link helper_plugin_move_handler}
         * such as {@link helper_plugin_move_handler::$id} are private
         *
         * The code below calls then the rewrite function {@link helper_plugin_move_handler::internallink()}
         * and change the content modified in the {@link helper_plugin_move_handler::$calls} variable
         *
         */
        if ($state !== DOKU_LEXER_ENTER) {
            // Description and ending
            $handler->calls .= $match;
            return;
        }

        /**
         * All environment on the {@link helper_plugin_move_handler handler} are private
         * We can't get it, we just hack around the move of the handler then
         */
        $parseAttributes = syntax_plugin_combo_link::parse($match);
        $ref = $parseAttributes[syntax_plugin_combo_link::MARKUP_REF_ATTRIBUTE];
        try {
            $link = LinkMarkup::createFromRef($ref);
            $isWikiUri = $link->getMarkupRef()->getSchemeType() === MarkupRef::WIKI_URI;
        } catch (ExceptionCompile $e) {
            LogUtility::error("Unable to rewrite the markup reference for a link move. The markup ref ($ref) could not be parsed. Error: {$e->getMessage()}");
            $handler->calls .= $match;
            return;
        }

        if (!$isWikiUri) {
            // Other type of links
            $handler->calls .= $match;
            return;
        }

        /**
         * This function will modify and add the link to the new output (ie calls)
         * {@link helper_plugin_move_handler::$calls}
         */
        $handler->internallink($match, $state, $pos);

        /**
         * Internal Link Calls Hack
         * that delete the ]]
         */
        $suffix = "]]";
        if (substr($handler->calls, -strlen($suffix)) === $suffix) {
            $handler->calls = substr($handler->calls, 0, strlen($handler->calls) - strlen($suffix));
        }


    }


}
