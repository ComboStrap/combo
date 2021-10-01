<?php



use Combostrap\AnalyticsMenuItem;
use ComboStrap\DatabasePage;
use ComboStrap\Identity;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\Sqlite;

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
 * Update the analytics data
 */
class action_plugin_combo_replication extends DokuWiki_Action_Plugin
{

    /**
     * @var array
     */
    protected $linksBeforeByPage = array();

    public function register(Doku_Event_Handler $controller)
    {

        /**
         * Analytics to refresh because they have lost or gain a backlinks
         * are done via Sqlite table (The INDEXER_TASKS_RUN gives a way to
         * manipulate this queue)
         *
         * There is no need to do it at page write
         * https://www.dokuwiki.org/devel:event:io_wikipage_write
         * because after the page is written, the page is shown and trigger the index tasks run
         *
         * We do it after because if there is an error
         * We will not stop the Dokuwiki Processing
         */
        $controller->register_hook('INDEXER_TASKS_RUN', 'AFTER', $this, 'handle_background_refresh_analytics', array());



    }

    public function handle_background_refresh_analytics(Doku_Event $event, $param)
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
        $replicator = $page->getReplicator();
        if (!$page->exists()) {

            $replicator->delete();
            return;
        }

        if ($replicator->shouldReplicate()) {
            $replicator->replicate();
            /**
             * TODO: Add reference
             */
        }


        /**
         * Process the page to replicate
         */
        DatabasePage::processReplicationRequest();


    }





}



