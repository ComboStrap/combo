<?php
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

namespace ComboStrap;


use dokuwiki\Cache\CacheRenderer;
use renderer_plugin_combo_analytics;

class Analytics
{


    const DATE_MODIFIED = 'date_modified';
    /**
     * Constant in Key or value
     */
    const HEADER_POSITION = 'header_id';
    const INTERNAL_BACKLINK_COUNT = "internal_backlink_count";
    const WORD_COUNT = 'word_count';
    const INTERNAL_LINK_DISTANCE = 'internal_link_distance';
    const INTERWIKI_LINK_COUNT = "interwiki_link_count";
    const EDITS_COUNT = 'edits_count';
    const INTERNAL_LINK_BROKEN_COUNT = 'internal_broken_link_count';
    const TITLE = 'title';
    const INTERNAL_LINK_COUNT = 'internal_link_count';
    const LOCAL_LINK_COUNT = "local_link_count"; // ie fragment #hallo
    const EXTERNAL_MEDIA_COUNT = 'external_media_count';
    const CHAR_COUNT = 'char_count';
    const MEDIA_COUNT = 'media_count';
    const INTERNAL_MEDIA_COUNT = 'internal_media_count';
    const INTERNAL_BROKEN_MEDIA_COUNT = 'internal_broken_media_count';
    const EXTERNAL_LINK_COUNT = 'external_link_count';
    const HEADING_COUNT = 'heading_count';
    const SYNTAX_COUNT = "syntax_count";
    const QUALITY = 'quality';
    const STATISTICS = "statistics";
    const EMAIL_COUNT = "email_count";
    const WINDOWS_SHARE_COUNT = "windows_share_count";

    /**
     * An array of info for errors mostly
     */
    const INFO = "info";
    const H1 = "h1";
    const LOW = "low";
    const RULES = "rules";
    const DETAILS = 'details';
    const FAILED_MANDATORY_RULES = 'failed_mandatory_rules';
    const NAME = "name";
    const PATH = "path";
    const DESCRIPTION = "description";
    const METADATA = 'metadata';
    const CANONICAL = "canonical";
    const DATE_CREATED = 'date_created';
    const DATE_END = "date_end";
    const DATE_START = "date_start";
    const DATE_REPLICATION = "date_replication";
    private $page;

    /**
     *
     * Analytics constructor.
     */
    public function __construct(Page $page)
    {
        $this->page = $page;
    }

    /**
     * Delete the cache,
     * Process the analytics
     * Save it in the Db
     * Delete from the page to refresh if any
     *
     * If you want the analytics:
     *   * from the cache use {@link self::getAnalyticsFromFs()}
     *   * from the db use {@link self::getAnalyticsFromDb()}
     *
     *
     * @return mixed analytics as array
     */
    public function replicate()
    {

        /**
         * Convenient variable
         */
        $page = $this->page;

        /**
         * Refresh and cache
         * (The delete is normally not needed, just to be sure)
         */
        $this->deleteRenderCache();

        /**
         * Render and save on the file system
         */
        $analyticsJson = $this->render();

        /**
         * Persist on the DB
         */
        $sqlite = Sqlite::getSqlite();
        if ($sqlite != null) {
            /**
             * Sqlite Plugin installed
             */
            $json = $analyticsJson->toString();
            $jsonAsArray = $analyticsJson->toArray();
            /**
             * Same data as {@link Page::getMetadataForRendering()}
             */

            $replicationDate = Iso8601Date::create()->toString();
            $this->page->setMetadata(Analytics::DATE_REPLICATION, $replicationDate);

            $entry = array(
                'CANONICAL' => $page->getCanonical(),
                'ANALYTICS' => $json,
                'PATH' => $page->getAbsolutePath(),
                'NAME' => $page->getName(),
                'TITLE' => $page->getTitleNotEmpty(),
                'H1' => $page->getH1NotEmpty(),
                'DATE_CREATED' => $page->getCreatedDateString(),
                'DATE_MODIFIED' => $page->getModifiedDateString(),
                'DATE_PUBLISHED' => $page->getPublishedTimeAsString(),
                'DATE_START' => $page->getEndDateAsString(),
                'DATE_END' => $page->getStartDateAsString(),
                'COUNTRY' => $page->getCountry(),
                'LANG' => $page->getLang(),
                'IS_LOW_QUALITY' => ($page->isLowQualityPage() === true ? 1 : 0),
                'TYPE' => $page->getType(),
                'WORD_COUNT' => $jsonAsArray[Analytics::WORD_COUNT],
                'BACKLINK_COUNT' => $jsonAsArray[Analytics::INTERNAL_BACKLINK_COUNT],
                'IS_HOME' => ($page->isNamespaceHomePage() === true ? 1 : 0),
                Page::UUID_ATTRIBUTE => $page->getUuid(),
                Analytics::DATE_REPLICATION => $replicationDate,
                'ID' => $page->getId(),
            );
            $res = $sqlite->query("SELECT count(*) FROM PAGES where ID = ?", $page->getId());
            if ($sqlite->res2single($res) == 1) {
                // Upset not supported on all version
                //$upsert = 'insert into PAGES (ID,CANONICAL,ANALYTICS) values (?,?,?) on conflict (ID,CANONICAL) do update set ANALYTICS = EXCLUDED.ANALYTICS';
                $update = <<<EOF
update
    PAGES
SET
    CANONICAL = ?,
    ANALYTICS = ?,
    PATH = ?,
    NAME = ?,
    TITLE = ?,
    H1 = ?,
    DATE_CREATED = ?,
    DATE_MODIFIED = ?,
    DATE_PUBLISHED = ?,
    DATE_START = ?,
    DATE_END = ?,
    COUNTRY = ?,
    LANG = ?,
    IS_LOW_QUALITY = ?,
    TYPE = ?,
    WORD_COUNT = ?,
    BACKLINK_COUNT = ?,
    IS_HOME = ?,
    UUID = ?,
    DATE_REPLICATION = ?
where
    ID=?
EOF;
                $res = $sqlite->query($update, $entry);
            } else {
                $res = $sqlite->storeEntry('PAGES', $entry);
            }
            if (!$res) {
                LogUtility::msg("There was a problem during the upsert: {$sqlite->getAdapter()->getDb()->errorInfo()}");
            }
            $sqlite->res_close($res);
        }


        /**
         * Delete from the refresh table
         */
        $sqlite = Sqlite::getSqlite();
        if ($sqlite != null) {
            $res = $sqlite->query("DELETE FROM ANALYTICS_TO_REFRESH where ID = ?", $page->getId());
            if (!$res) {
                LogUtility::msg("There was a problem during the delete: {$sqlite->getAdapter()->getDb()->errorInfo()}");
            }
            $sqlite->res_close($res);

        }
        return $analyticsJson;

    }


    /**
     * @return bool - if a {@link Analytics::replicate()} for the page should occurs
     */
    public
    function shouldAnalyticsProcessOccurs(): bool
    {
        /**
         * If render cache is on
         */
        if (Site::isRenderCacheOn()) {
            /**
             * If there is no cache
             */
            if (!$this->isCached()) {
                return true;
            }
        }

        /**
         * Check if Analytics is null in Db
         */
        $sqlite = Sqlite::getSqlite();
        if ($sqlite != null) {

            $res = $sqlite->query("select count(1) from pages where ID = ? and ANALYTICS is null", $this->page->getId());
            if (!$res) {
                LogUtility::msg("An exception has occurred with the analytics detection");
            }
            $count = intval($sqlite->res2single($res));
            $sqlite->res_close($res);
            if ($count == 0) {
                return true;
            }

        }

        /**
         * Check the refresh table
         */
        if ($sqlite != null) {
            return $this->isInRefreshTable();
        }

        return false;
    }

    public function isInRefreshTable(): bool
    {
        $sqlite = Sqlite::getSqlite();
        $res = $sqlite->query("SELECT count(*) FROM ANALYTICS_TO_REFRESH where ID = ?", $this->page->getId());
        if (!$res) {
            LogUtility::msg("There was a problem during the select analytics to refresh: {$sqlite->getAdapter()->getDb()->errorInfo()}");
        }
        $value = $sqlite->res2single($res);
        $sqlite->res_close($res);
        return $value === "1";

    }

    /**
     * @param $reason - a string with the reason
     * @param Page $instance
     */
    public static
    function deleteCacheAndAskAnalyticsRefresh($reason, Page $instance)
    {
        $instance->deleteCache(renderer_plugin_combo_analytics::RENDERER_NAME_MODE);
        $sqlite = Sqlite::getSqlite();
        if ($sqlite != null) {

            /**
             * Check if exists
             */
            $res = $sqlite->query("select count(1) from ANALYTICS_TO_REFRESH where ID = ?", array('ID' => $instance->getId()));
            if (!$res) {
                LogUtility::msg("There was a problem during the insert: {$sqlite->getAdapter()->getDb()->errorInfo()}");
            }
            $result = $sqlite->res2single($res);
            $sqlite->res_close($res);

            /**
             * If not insert
             */
            if ($result != 1) {
                $entry = array(
                    "ID" => $instance->getId(),
                    "TIMESTAMP" => date('Y-m-d H:i:s', time()),
                    "REASON" => $reason
                );
                $res = $sqlite->storeEntry('ANALYTICS_TO_REFRESH', $entry);
                if (!$res) {
                    LogUtility::msg("There was a problem during the insert: {$sqlite->getAdapter()->getDb()->errorInfo()}");
                }
                $sqlite->res_close($res);
            }

        }

    }

    public function isCached()
    {
        $cache = new CacheRenderer($this->page->getId(), $this->page->getFileSystemPath(), renderer_plugin_combo_analytics::RENDERER_NAME_MODE);
        $cacheFile = $cache->cache;
        return file_exists($cacheFile);
    }

    /**
     * @return Json|null the analytics array or null if not in db
     */
    public function getJsonDataFromDb(): ?Json
    {
        $sqlite = Sqlite::getSqlite();
        if ($sqlite === null) {
            return null;
        }
        $res = $sqlite->query("select ANALYTICS from pages where ID = ? ", $this->page->getId());
        if (!$res) {
            LogUtility::msg("An exception has occurred with the pages selection query");
        }
        $jsonString = trim($sqlite->res2single($res));
        $sqlite->res_close($res);
        if (!empty($jsonString)) {
            return Json::createFromString($jsonString);
        } else {
            return null;
        }

    }


    /**
     * @param bool $cache - if true, the analytics json rendering file will be retrieved from cache
     * @return null|Json
     *
     * The p_render function was seen from the {@link p_cached_output} function
     * used the in the switch of the {@link \dokuwiki\Action\Export::preProcess()} function
     */
    function render($cache = false): ?Json
    {
        if (!$this->page->exists()) {
            return null;
        }
        global $ID;
        $oldId = $ID;
        $ID = $this->page->getId();
        if (!$cache) {
            $this->deleteCache();
        }

        $result = p_cached_output($this->page->getFileSystemPath(), renderer_plugin_combo_analytics::RENDERER_NAME_MODE, $this->page->getId());

        $ID = $oldId;
        return Json::createFromString($result);

    }

    public function getJsonData(bool $bool = true): ?Json
    {
        return $this->render($bool);
    }

    private function deleteRenderCache()
    {
        $this->page->deleteRenderCache(renderer_plugin_combo_analytics::RENDERER_NAME_MODE);
    }

    private function deleteCache()
    {
        $this->page->deleteCache(renderer_plugin_combo_analytics::RENDERER_NAME_MODE);
    }


}
