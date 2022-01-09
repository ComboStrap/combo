<?php


use ComboStrap\Console;
use ComboStrap\Event;
use ComboStrap\ExceptionCombo;
use ComboStrap\LogUtility;
use ComboStrap\Page;

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


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * Class action_plugin_combo_analytics
 * Replicate the file system to the sqlite database
 */
class action_plugin_combo_fulldatabasereplication extends DokuWiki_Action_Plugin
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

    }

    /**
     * @throws ExceptionCombo
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
        $page = Page::createPageFromId($id);

        /**
         * From {@link idx_addPage}
         * They receive even the deleted page
         */
        $databasePage = $page->getDatabasePage();
        if (!$page->exists()) {
            $databasePage->delete();
            return;
        }

        if ($databasePage->shouldReplicate()) {
            try {
                $databasePage->replicate();
            } catch (ExceptionCombo $e) {
                $message = "Error with the database replication for the page ($page). ".$e->getMessage();
                if(Console::isConsoleRun()) {
                    throw new ExceptionCombo($message);
                } else {
                    LogUtility::msg($message);
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





}



