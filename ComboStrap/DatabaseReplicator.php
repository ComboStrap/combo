<?php


namespace ComboStrap;

/**
 * The class that manage the replication
 * Class Replicate
 * @package ComboStrap
 */
class DatabaseReplicator
{
    public const DATE_REPLICATION = "date_replication";
    /**
     * @var Page
     */
    private $page;

    /**
     * Replicate constructor.
     * @param Page $page
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
     */
    public function replicate()
    {

        /**
         * Convenient variable
         */
        $page = $this->page;

        /**
         * Render and save on the file system
         */
        $analyticsJson = $this->page->getAnalytics()->getData();

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
                self::DATE_REPLICATION => $replicationDate,
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
            /**
             * Successful
             */
            if (!$res) {
                LogUtility::msg("There was a problem during the upsert: {$sqlite->getAdapter()->getDb()->errorInfo()}");
            } else {
                $this->page->setMetadata(self::DATE_REPLICATION, $replicationDate);
            }


            $sqlite->res_close($res);
        }


    }

    public function shouldReplicate(): bool
    {
        return true;
    }

    public function delete()
    {


        $res = Sqlite::getSqlite()->query('delete from pages where id = ?', $this->page->getId());
        if (!$res) {
            LogUtility::msg("Something went wrong when deleting a page");
        }


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
     * Ask a replication in the background
     * @param $reason - a string with the reason
     */
    public function createReplicationRequest($reason)
    {

        $sqlite = Sqlite::getSqlite();
        if ($sqlite != null) {

            /**
             * Check if exists
             */
            $res = $sqlite->query("select count(1) from ANALYTICS_TO_REFRESH where ID = ?", array('ID' => $this->page->getId()));
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
                    "ID" => $this->page->getId(),
                    "TIMESTAMP" => Iso8601Date::create()->toString(),
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

}
