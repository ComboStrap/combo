<?php


namespace ComboStrap;

/**
 * The class that manage the replication
 * Class Replicate
 * @package ComboStrap
 */
class DatabaseReplicate
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
        $analyticsJson = $this->page->getAnalytics()->getJsonData();

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

}
