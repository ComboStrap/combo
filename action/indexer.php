<?php


use ComboStrap\Console;
use ComboStrap\Event;
use ComboStrap\ExceptionCompile;
use ComboStrap\FileSystems;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\PluginUtility;

/**
 * Copyright (c) 2021. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */
class action_plugin_combo_indexer extends DokuWiki_Action_Plugin
{


    const CANONICAL = "replication";

    public function register(Doku_Event_Handler $controller)
    {


        /**
         * We do it after because if there is an error
         * We will not stop the Dokuwiki Processing
         */
        $controller->register_hook('INDEXER_PAGE_ADD', 'AFTER', $this, 'handle_db_replication', array());


        $controller->register_hook('INDEXER_TASKS_RUN', 'AFTER', $this, 'handle_async_event', array());

        $controller->register_hook('INDEXER_TASKS_RUN', 'BEFORE', $this, 'handle_markup_extension', array());

    }

    /**
     * @throws ExceptionCompile
     */
    public function handle_db_replication(Doku_Event $event, $param)
    {


        /**
         * Check that the actual page has analytics data
         * (if there is a cache, it's pretty quick)
         */
        global $ID;
        if ($ID == null) {
            $id = $event->data['page'];
        } else {
            $id = $ID;
        }
        $page = MarkupPath::createMarkupFromId($id);

        /**
         * From {@link idx_addPage}
         * They receive even the deleted page
         */
        $databasePage = $page->getDatabasePage();
        if (!FileSystems::exists($page)) {
            $databasePage->delete();
            return;
        }

        if ($databasePage->shouldReplicate()) {
            try {
                $databasePage->replicate();
            } catch (ExceptionCompile $e) {
                if (PluginUtility::isDevOrTest()) {
                    // to get the stack trace
                    throw $e;
                }
                $message = "Error with the database replication for the page ($page). " . $e->getMessage();
                if (Console::isConsoleRun()) {
                    throw new ExceptionCompile($message);
                } else {
                    LogUtility::error($message);
                }
            }
        }


    }

    /**
     */
    public function handle_async_event(Doku_Event $event, $param)
    {


        /**
         * Process the async event
         */
        Event::dispatchEvent();


    }

    /**
     * We support other extension for markup
     */
    public function handle_markup_extension(Doku_Event $event, $param)
    {





    }


}



