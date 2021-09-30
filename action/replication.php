<?php



use Combostrap\AnalyticsMenuItem;
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
         * Process the analytics to refresh
         */
        $this->analyticsBatchBackgroundRefresh();


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


    }


    private function analyticsBatchBackgroundRefresh()
    {
        $sqlite = Sqlite::getSqlite();
        $res = $sqlite->query("SELECT ID FROM ANALYTICS_TO_REFRESH");
        if (!$res) {
            LogUtility::msg("There was a problem during the select: {$sqlite->getAdapter()->getDb()->errorInfo()}");
        }
        $rows = $sqlite->res2arr($res, true);
        $sqlite->res_close($res);

        /**
         * In case of a start or if there is a recursive bug
         * We don't want to take all the resources
         */
        $maxRefresh = 10; // by default, there is 5 pages in a default dokuwiki installation in the wiki namespace
        $maxRefreshLow = 2;
        $pagesToRefresh = sizeof($rows);
        if ($pagesToRefresh > $maxRefresh) {
            LogUtility::msg("There is {$pagesToRefresh} pages to refresh in the queue (table `ANALYTICS_TO_REFRESH`). This is more than {$maxRefresh} pages. Batch background Analytics refresh was reduced to {$maxRefreshLow} pages to not hit the computer resources.", LogUtility::LVL_MSG_ERROR, "analytics");
            $maxRefresh = $maxRefreshLow;
        }
        $refreshCounter = 0;
        foreach ($rows as $row) {
            $page = Page::createPageFromId($row['ID']);
            $page->getReplicator()->replicate();
            $refreshCounter++;
            if ($refreshCounter >= $maxRefresh) {
                break;
            }
        }

    }


}



