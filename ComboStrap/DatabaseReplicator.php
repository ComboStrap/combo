<?php


namespace ComboStrap;

/**
 * The class that manage the replication
 * Class Replicate
 * @package ComboStrap
 */
class DatabaseReplicator
{
    /**
     * The attribute in the metadata and in the database
     */
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
             * Replication Date
             */
            $replicationDate = Iso8601Date::create()->toString();


            /**
             * Same data as {@link Page::getMetadataForRendering()}
             */
            $record = array(
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

            /**
             * Primary key has moved during the time
             * It should be the UUID but not for older version
             *
             * If the primary key is null, no record was found
             */
            list($primaryKey, $primaryKeyValue) = $this->getPrimaryKeyAndItsValue();
            if ($primaryKey != null) {

                $record[$primaryKey] = $primaryKeyValue;
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
    ID = ?
where
    $primaryKey=?
EOF;
                $res = $sqlite->query($update, $record);
            } else {
                $res = $sqlite->storeEntry('PAGES', $record);
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
            LogUtility::msg("Something went wrong when deleting the page ({$this->page})");
        }

    }

    /**
     * @return Json|null the analytics array or null if not in db
     */
    public function getAnalyticsData(): ?Json
    {
        $sqlite = Sqlite::getSqlite();
        if ($sqlite === null) {
            return null;
        }
        $res = $sqlite->query("select ANALYTICS from pages where ID = ? ", $this->page->getId());
        if (!$res) {
            LogUtility::msg("An exception has occurred with the analytics page ({$this->page}) selection query");
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

    /**
     * Return a array where the first element is the primary key column
     * and the second the value
     *
     * If the first element is null, no row was found in the database
     *
     * @return array|null[]|string[]
     */
    private function getPrimaryKeyAndItsValue(): array
    {

        $sqlite = Sqlite::getSqlite();
        if ($sqlite === null) {
            return [null, null];
        }

        $page = $this->page;
        // Do we have a page attached to this uuid
        $uuid = $page->getUuid();
        $res = $sqlite->query("select ID from pages where UUID = ?", $uuid);
        if (!$res) {
            LogUtility::msg("An exception has occurred with the page search from UUID");
        }
        $IdsInDb = $sqlite->res2arr($res);
        $sqlite->res_close($res);
        switch (sizeof($IdsInDb)) {
            case 0:
                break;
            case 1:
                $id = $IdsInDb[0]["ID"];
                if ($id === $page->getId()) {
                    return [Page::UUID_ATTRIBUTE, $uuid];
                } else {
                    LogUtility::msg("The page ($page) and the page ($id) have the same UUID ($uuid)", LogUtility::LVL_MSG_ERROR);
                }
                break;
            default:
                $existingPages = implode(", ", $IdsInDb);
                LogUtility::msg("The pages ($existingPages) have all the same UUID ($uuid)", LogUtility::LVL_MSG_ERROR);
        }

        // Do we have a page attached to the canonical
        $canonical = $page->getCanonical();
        if ($canonical != null) {
            $res = $sqlite->query("select ID from pages where CANONICAL = ?", $canonical);
            if (!$res) {
                LogUtility::msg("An exception has occurred with the page search from CANONICAL");
            }
            $IdsInDb = $sqlite->res2arr($res);
            $sqlite->res_close($res);

            switch (sizeof($IdsInDb)) {
                case 0:
                    break;
                case 1:
                    $id = $IdsInDb[0]["ID"];
                    if ($id === $page->getPath()) {
                        return [Page::CANONICAL_PROPERTY, $canonical];
                    } else {
                        LogUtility::msg("The page ($page) and the page ($id) have the same canonical ($canonical)", LogUtility::LVL_MSG_ERROR);
                    }
                    break;
                default:
                    $existingPages = [];
                    foreach ($IdsInDb as $idInDb) {
                        $pageInDb = Page::createPageFromId($idInDb);
                        if (!$pageInDb->exists()) {

                            /**
                             * TODO: Handle a page move with the move plugin instead
                             */
                            $this->delete();
                            $page->persistPageAlias($canonical, $idInDb);

                        } else {

                            /**
                             * Check if the error may come from the auto-canonical
                             * (Never ever save generated data)
                             */
                            $canonicalLastNamesCount = PluginUtility::getConfValue(\action_plugin_combo_metacanonical::CANONICAL_LAST_NAMES_COUNT_CONF);
                            if ($canonicalLastNamesCount > 0) {
                                $page->unsetMetadata(Page::CANONICAL_PROPERTY);
                                Page::createPageFromQualifiedPath($IdsInDb)->unsetMetadata(Page::CANONICAL_PROPERTY);
                            }

                            $existingPages[] = $idInDb;
                        }
                    }
                    if (sizeof($existingPages) === 1) {
                        return [Page::CANONICAL_PROPERTY, $canonical];
                    } else {
                        $existingPages = implode(", ", $existingPages);
                        LogUtility::msg("The existing pages ($existingPages) have all the same canonical ($canonical)", LogUtility::LVL_MSG_ERROR);
                    }
            }

        }

        // Do we have a page attached to the path
        $path = $page->getPath();
        $res = $sqlite->query("select ID from pages where PATH = ?", $path);
        if (!$res) {
            LogUtility::msg("An exception has occurred with the page search from a PATH");
        }
        $IdsInDb = $sqlite->res2arr($res);
        $sqlite->res_close($res);
        switch (sizeof($IdsInDb)) {
            case 0:
                break;
            case 1:
                $id = $IdsInDb[0]["ID"];
                if ($id === $page->getId()) {
                    return [Page::PATH_ATTRIBUTE, $path];
                } else {
                    LogUtility::msg("The page ($page) and the page ($id) have the same path ($path)", LogUtility::LVL_MSG_ERROR);
                }
                break;
            default:
                $existingPages = [];
                foreach ($IdsInDb as $idInDb) {
                    $pageInDb = Page::createPageFromId($idInDb);
                    if (!$pageInDb->exists()) {

                        /**
                         * TODO: Handle a page move with the move plugin instead
                         */
                        $this->delete();
                        $page->persistPageAlias($canonical, $idInDb);

                    } else {

                        $existingPages[] = $idInDb;
                    }
                }
                if (sizeof($existingPages) === 1) {
                    return [Page::PATH_ATTRIBUTE, $path];
                } else {
                    $existingPages = implode(", ", $existingPages);
                    LogUtility::msg("The existing pages ($existingPages) have all the same path ($path)", LogUtility::LVL_MSG_ERROR);
                }

        }

        /**
         * Do we have a page attached to this ID
         * @deprecated
         */
        $id = $page->getId();
        $res = $sqlite->query("select ID from pages where ID = ?", $id);
        if (!$res) {
            LogUtility::msg("An exception has occurred with the page search from UUID");
        }
        $IdsInDb = $sqlite->res2arr($res);
        $sqlite->res_close($res);
        switch (sizeof($IdsInDb)) {
            case 0:
                break;
            case 1:
                return ["id", $id];
            default:
                LogUtility::msg("The database has " . sizeof($IdsInDb) . " records with the same id ($id)", LogUtility::LVL_MSG_ERROR);
                break;

        }

        /**
         * Nothing
         */
        return [null, null];

    }


}
