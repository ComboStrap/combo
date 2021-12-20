<?php


namespace ComboStrap;

/**
 * Class Event
 * @package ComboStrap
 * Asynchronous pub/sub system
 *
 * Dokuwiki allows event but they are synchronous
 * because php does not live in multiple thred
 *
 * With the help of Sqlite, we make them asynchronous
 */
class Event
{
    const TABLE_NAME = "PAGES_TO_REPLICATE";

    /**
     * process all replication request, created with {@link Event::createEvent()}
     *
     * by default, there is 5 pages in a default dokuwiki installation in the wiki namespace)
     */
    public static function dispatchEvent($maxEvent = 10)
    {

        $sqlite = Sqlite::createOrGetSqlite();
        $tableName = self::TABLE_NAME;
        $res = $sqlite->query("SELECT ID FROM $tableName");
        if (!$res) {
            LogUtility::msg("There was a problem during the select: {$sqlite->getAdapter()->getDb()->errorInfo()}");
        }
        $rows = $sqlite->res2arr($res, true);
        $sqlite->res_close($res);
        if (sizeof($rows) === 0) {
            LogUtility::msg("No replication requests found", LogUtility::LVL_MSG_INFO);
            return;
        }

        /**
         * In case of a start or if there is a recursive bug
         * We don't want to take all the resources
         */
        $maxRefreshLow = 2;
        $pagesToRefresh = sizeof($rows);
        if ($pagesToRefresh > $maxEvent) {
            LogUtility::msg("There is {$pagesToRefresh} pages to refresh in the queue (table `PAGES_TO_REPLICATE`). This is more than {$maxEvent} pages. Batch background Analytics refresh was reduced to {$maxRefreshLow} pages to not hit the computer resources.", LogUtility::LVL_MSG_ERROR, "analytics");
            $maxEvent = $maxRefreshLow;
        }
        $refreshCounter = 0;
        $totalRequests = sizeof($rows);
        foreach ($rows as $row) {
            $refreshCounter++;
            $id = $row['ID'];
            $page = Page::createPageFromId($id);
            /**
             * The page may have moved
             */
            if ($page->exists()) {
                $result = $page->getDatabasePage()->replicate();
                if ($result) {
                    LogUtility::msg("The page `$page` ($refreshCounter / $totalRequests) was replicated by request", LogUtility::LVL_MSG_INFO);
                    $res = $sqlite->query("DELETE FROM PAGES_TO_REPLICATE where ID = ?", $id);
                    if (!$res) {
                        LogUtility::msg("There was a problem during the delete of the replication request: {$sqlite->getAdapter()->getDb()->errorInfo()}");
                    }
                    $sqlite->res_close($res);
                }
                if ($refreshCounter >= $maxEvent) {
                    break;
                }
            }
        }

    }

    /**
     * Ask a replication in the background
     * @param $reason - a string with the reason
     * @param DatabasePage $databasePage
     */
    public
    function createEvent($reason, DatabasePage $databasePage)
    {

        if ($databasePage->sqlite === null) {
            return;
        }

        /**
         * Check if exists
         */
        $eventTable = self::TABLE_NAME;
        $res = $databasePage->sqlite->query("select count(1) from  where ID = ?", array('ID' => $databasePage->page->getDokuwikiId()));
        if (!$res) {
            LogUtility::msg("There was a problem during the select PAGES_TO_REPLICATE: {$databasePage->sqlite->getAdapter()->getDb()->errorInfo()}");
        }
        $result = $databasePage->sqlite->res2single($res);
        $databasePage->sqlite->res_close($res);
        if ($result >= 1) {
            return;
        }

        /**
         * If not present
         */
        $entry = array(
            "ID" => $databasePage->page->getDokuwikiId(),
            "TIMESTAMP" => Iso8601Date::createFromNow()->toString(),
            "REASON" => $reason
        );
        $res = $databasePage->sqlite->storeEntry('PAGES_TO_REPLICATE', $entry);
        if (!$res) {
            LogUtility::msg("There was a problem during the insert into PAGES_TO_REPLICATE: {$databasePage->sqlite->getAdapter()->getDb()->errorInfo()}");
        }
        $databasePage->sqlite->res_close($res);


    }
}
