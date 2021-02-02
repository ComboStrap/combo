<?php

use Combostrap\AnalyticsMenuItem;
use ComboStrap\Auth;
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

require_once(__DIR__ . '/../class/' . 'Analytics.php');
require_once(__DIR__ . '/../class/' . 'Auth.php');
require_once(__DIR__ . '/../class/' . 'AnalyticsMenuItem.php');

/**
 * Class action_plugin_combo_analytics
 * Update the analytics data
 */
class action_plugin_combo_analytics extends DokuWiki_Action_Plugin
{


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
        $controller->register_hook('INDEXER_TASKS_RUN', 'AFTER', $this, 'handle_refresh_analytics', array());

        /**
         * Add a icon in the page tools menu
         * https://www.dokuwiki.org/devel:event:menu_items_assembly
         */
        $controller->register_hook('MENU_ITEMS_ASSEMBLY', 'AFTER', $this, 'handle_page_tools');

    }

    public function handle_refresh_analytics(Doku_Event $event, $param)
    {

        /**
         * Check that the actual page has analytics data
         * (if there is a cache, it's pretty quick)
         */
        global $ID;
        $page = new Page($ID);
        if ($page->shouldAnalyticsProcessOccurs()) {
            $page->processAnalytics();
        }

        /**
         * Process the analytics to refresh
         */
        $this->analyticsBatchBackgroundRefresh();

    }

    public function handle_page_tools(Doku_Event $event, $param)
    {

        if (!Auth::isWriter()) {
            return;
        }

        /**
         * The `view` property defines the menu that is currently built
         * https://www.dokuwiki.org/devel:menus
         * If this is not the page menu, return
         */
        if ($event->data['view'] != 'page') return;

        global $INFO;
        if (!$INFO['exists']) {
            return;
        }
        array_splice($event->data['items'], -1, 0, array(new AnalyticsMenuItem()));

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
        $maxRefresh = 5;
        $maxRefreshLow = 2;
        if (sizeof($rows) > $maxRefresh) {
            LogUtility::msg("There is more than {$maxRefresh} page to refresh in the queue (table `ANALYTICS_TO_REFRESH`). Batch background Analytics refresh was reduced to {$maxRefreshLow} page to not hit the computer resources.", LogUtility::LVL_MSG_ERROR, "analytics");
            $maxRefresh = $maxRefreshLow;
        }
        $refreshCounter = 0;
        foreach ($rows as $row) {
            $page = new Page($row['ID']);
            $page->processAnalytics();
            $refreshCounter++;
            if ($refreshCounter>=$maxRefresh){
                break;
            }
        }

    }
}



