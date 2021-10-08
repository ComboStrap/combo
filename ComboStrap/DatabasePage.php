<?php


namespace ComboStrap;

/**
 * The class that manage the replication
 * Class Replicate
 * @package ComboStrap
 */
class DatabasePage
{
    /**
     * The attribute in the metadata and in the database
     */
    public const DATE_REPLICATION = "date_replication";

    /**
     * Attribute that are modifiable in the database
     */
    public const MODIFIABLE_ATTRIBUTES =
        [
            Analytics::DESCRIPTION,
            Analytics::CANONICAL,
            Analytics::NAME,
            Analytics::TITLE,
            Analytics::H1,
            Publication::DATE_PUBLISHED,
            Analytics::DATE_START,
            Analytics::DATE_END,
            Page::COUNTRY_META_PROPERTY,
            Page::LANG_META_PROPERTY,
            Page::TYPE_META_PROPERTY
        ];
    const ANALYTICS_ATTRIBUTE = "ANALYTICS";

    /**
     * For whatever reason, the row id is lowercase
     */
    const ROWID = "rowid";
    const REPLICATION_CANONICAL = "replication";

    /**
     * @var Page
     */
    private $page;
    /**
     * @var \helper_plugin_sqlite|null
     */
    private $sqlite;

    /**
     * Replicate constructor.
     * @param Page $page
     */
    public function __construct(Page $page)
    {
        $this->page = $page;
        /**
         * Persist on the DB
         */
        $this->sqlite = Sqlite::getSqlite();

    }

    /**
     * process all replication request, created with {@link DatabasePage::createReplicationRequest()}
     *
     * by default, there is 5 pages in a default dokuwiki installation in the wiki namespace)
     */
    public static function processReplicationRequest($maxRefresh = 10)
    {

        $sqlite = Sqlite::getSqlite();
        $res = $sqlite->query("SELECT ID FROM PAGES_TO_REPLICATE");
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
        if ($pagesToRefresh > $maxRefresh) {
            LogUtility::msg("There is {$pagesToRefresh} pages to refresh in the queue (table `PAGES_TO_REPLICATE`). This is more than {$maxRefresh} pages. Batch background Analytics refresh was reduced to {$maxRefreshLow} pages to not hit the computer resources.", LogUtility::LVL_MSG_ERROR, "analytics");
            $maxRefresh = $maxRefreshLow;
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
                if ($refreshCounter >= $maxRefresh) {
                    break;
                }
            }
        }

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
    public function replicate(): bool
    {
        if ($this->sqlite === null) {
            return false;
        }

        if (!$this->page->exists()) {
            LogUtility::msg("You can't replicate the non-existing page ($this->page) on the file system");
            return false;
        }

        /**
         * Replication Date
         */
        $replicationDate = Iso8601Date::createFromString()->toString();
        $res = $this->replicatePage($replicationDate);
        if ($res === false) {
            return false;
        }

        $res = $this->replicatePageReference();
        if ($res === false) {
            return false;
        }

        /**
         * Set the replication date
         */
        $this->page->setMetadata(self::DATE_REPLICATION, $replicationDate);
        return true;

    }

    public
    function shouldReplicate(): bool
    {

        /**
         * When the file does not exist
         */
        $modifiedTime = $this->page->getAnalytics()->getModifiedTime();
        if ($modifiedTime === null) {
            return true;
        }
        /**
         * When the file exists
         */
        $dateReplication = $this->getReplicationDate();
        if ($modifiedTime > $dateReplication) {
            return true;
        }

        /**
         * When the database version file is higher
         */
        $version = File::createFromPath(__DIR__ . "/../db/latest.version");
        $versionModifiedTime = $version->getModifiedTime();
        if ($versionModifiedTime > $dateReplication) {
            return true;
        }

        /**
         * When the class date time is higher
         */
        $code = File::createFromPath(__DIR__ . "/DatabasePage.php");
        $codeModified = $code->getModifiedTime();
        if ($codeModified > $dateReplication) {
            return true;
        }

        return false;

    }

    public
    function delete()
    {

        $res = Sqlite::getSqlite()->query('delete from pages where id = ?', $this->page->getId());
        if (!$res) {
            LogUtility::msg("Something went wrong when deleting the page ({$this->page})");
        }

    }

    /**
     * @return Json|null the analytics array or null if not in db
     */
    public
    function getAnalyticsData(): ?Json
    {

        $jsonString = $this->getAttribute(self::ANALYTICS_ATTRIBUTE);
        if ($jsonString !== null) {
            return Json::createFromString($jsonString);
        } else {
            return null;
        }

    }

    /**
     * Ask a replication in the background
     * @param $reason - a string with the reason
     */
    public
    function createReplicationRequest($reason)
    {

        if ($this->sqlite === null) {
            return;
        }

        /**
         * Check if exists
         */
        $res = $this->sqlite->query("select count(1) from PAGES_TO_REPLICATE where ID = ?", array('ID' => $this->page->getId()));
        if (!$res) {
            LogUtility::msg("There was a problem during the select PAGES_TO_REPLICATE: {$this->sqlite->getAdapter()->getDb()->errorInfo()}");
        }
        $result = $this->sqlite->res2single($res);
        $this->sqlite->res_close($res);
        if ($result >= 1) {
            return;
        }

        /**
         * If not present
         */
        $entry = array(
            "ID" => $this->page->getId(),
            "TIMESTAMP" => Iso8601Date::createFromString()->toString(),
            "REASON" => $reason
        );
        $res = $this->sqlite->storeEntry('PAGES_TO_REPLICATE', $entry);
        if (!$res) {
            LogUtility::msg("There was a problem during the insert into PAGES_TO_REPLICATE: {$this->sqlite->getAdapter()->getDb()->errorInfo()}");
        }
        $this->sqlite->res_close($res);


    }

    /**
     * Return the sqlite row id
     * https://www.sqlite.org/autoinc.html
     *
     * If the first element is null, no row was found in the database
     *
     * @return int|null
     */
    private
    function getRowId(): ?int
    {

        if ($this->sqlite === null) {
            return null;
        }

        $page = $this->page;
        // Do we have a page attached to this uuid
        $uuid = $page->getUuid();
        $res = $this->sqlite->query("select ROWID, ID from pages where UUID = ?", $uuid);
        if (!$res) {
            LogUtility::msg("An exception has occurred with the page search from UUID");
        }
        $rows = $this->sqlite->res2arr($res);
        $this->sqlite->res_close($res);
        switch (sizeof($rows)) {
            case 0:
                break;
            case 1:
                $id = $rows[0]["ID"];
                if ($id !== $page->getId()) {
                    LogUtility::msg("The page ($page) and the page ($id) have the same UUID ($uuid)", LogUtility::LVL_MSG_ERROR);
                }
                return intval($rows[0][self::ROWID]);
            default:
                $existingPages = implode(", ", $rows);
                LogUtility::msg("The pages ($existingPages) have all the same UUID ($uuid)", LogUtility::LVL_MSG_ERROR);
        }

        // Do we have a page attached to the canonical
        $canonical = $page->getCanonicalOrDefault();
        if ($canonical != null) {
            $res = $this->sqlite->query("select ROWID, ID from pages where CANONICAL = ?", $canonical);
            if (!$res) {
                LogUtility::msg("An exception has occurred with the page search from CANONICAL");
            }
            $rows = $this->sqlite->res2arr($res);
            $this->sqlite->res_close($res);

            switch (sizeof($rows)) {
                case 0:
                    break;
                case 1:
                    $id = $rows[0]["ID"];
                    if ($id !== $page->getId()) {
                        LogUtility::msg("The page ($page) and the page ($id) have the same canonical ($canonical)", LogUtility::LVL_MSG_ERROR);
                    }
                    return intval($rows[0][self::ROWID]);
                    break;
                default:
                    $existingPages = [];
                    foreach ($rows as $row) {
                        $id = $row["ID"];
                        $pageInDb = Page::createPageFromId($id);
                        if (!$pageInDb->exists()) {

                            /**
                             * TODO: Handle a page move with the move plugin instead
                             */
                            $this->delete();
                            $page->persistPageAlias($canonical, $id);

                        } else {

                            /**
                             * Check if the error may come from the auto-canonical
                             * (Never ever save generated data)
                             */
                            $canonicalLastNamesCount = PluginUtility::getConfValue(\action_plugin_combo_metacanonical::CANONICAL_LAST_NAMES_COUNT_CONF);
                            if ($canonicalLastNamesCount > 0) {
                                $page->unsetMetadata(Page::CANONICAL_PROPERTY);
                                Page::createPageFromQualifiedPath($rows)->unsetMetadata(Page::CANONICAL_PROPERTY);
                            }

                            $existingPages[] = $row;
                        }
                    }
                    if (sizeof($existingPages) === 1) {
                        return $existingPages[0][self::ROWID];
                    } else {
                        $existingPages = implode(", ", $existingPages);
                        LogUtility::msg("The existing pages ($existingPages) have all the same canonical ($canonical)", LogUtility::LVL_MSG_ERROR);
                    }
            }

        }

        // Do we have a page attached to the path
        $path = $page->getPath();
        $res = $this->sqlite->query("select ROWID, ID from pages where PATH = ?", $path);
        if (!$res) {
            LogUtility::msg("An exception has occurred with the page search from a PATH");
        }
        $rows = $this->sqlite->res2arr($res);
        $this->sqlite->res_close($res);
        switch (sizeof($rows)) {
            case 0:
                break;
            case 1:
                $id = $rows[0]["ID"];
                if ($id !== $page->getId()) {
                    LogUtility::msg("The page ($page) and the page ($id) have the same path ($path)", LogUtility::LVL_MSG_ERROR);
                }
                return intval($rows[0][self::ROWID]);
                break;
            default:
                $existingPages = [];
                foreach ($rows as $row) {
                    $pageInDb = Page::createPageFromId($row);
                    if (!$pageInDb->exists()) {

                        /**
                         * TODO: Handle a page move with the move plugin instead
                         */
                        $this->delete();
                        $page->persistPageAlias($canonical, $row);

                    } else {
                        $existingPages[] = $row;
                    }
                }
                if (sizeof($existingPages) === 1) {
                    return intval($existingPages[0][self::ROWID]);
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
        $res = $this->sqlite->query("select ROWID, ID from pages where ID = ?", $id);
        if (!$res) {
            LogUtility::msg("An exception has occurred with the page search from UUID");
        }
        $rows = $this->sqlite->res2arr($res);
        $this->sqlite->res_close($res);
        switch (sizeof($rows)) {
            case 0:
                break;
            case 1:
                return intval($rows[0][self::ROWID]);
            default:
                LogUtility::msg("The database has " . sizeof($rows) . " records with the same id ($id)", LogUtility::LVL_MSG_ERROR);
                break;

        }

        /**
         * No rows found
         */
        return null;

    }

    public function getReplicationDate()
    {
        $stringReplicationDate = $this->page->getMetadata(DatabasePage::DATE_REPLICATION);
        if (empty($stringReplicationDate)) {
            return null;
        } else {
            return Iso8601Date::createFromString($stringReplicationDate)->getDateTime();
        }
    }

    /**
     * @param string $replicationDate
     * @return bool
     */
    public function replicatePage(string $replicationDate): bool
    {
        /**
         * Convenient variable
         */
        $page = $this->page;

        /**
         * Render and save on the file system
         */
        $analyticsJson = $this->page->getAnalytics()->getData();
        $analyticsJsonAsString = $analyticsJson->toString();
        $analyticsJsonAsArray = $analyticsJson->toArray();
        /**
         * Same data as {@link Page::getMetadataForRendering()}
         */
        $record = array(
            Analytics::CANONICAL => $page->getCanonicalOrDefault(),
            self::ANALYTICS_ATTRIBUTE => $analyticsJsonAsString,
            'PATH' => $page->getAbsolutePath(),
            Analytics::NAME => $page->getPageNameNotEmpty(),
            Analytics::TITLE => $page->getTitleNotEmpty(),
            Analytics::H1 => $page->getH1NotEmpty(),
            Analytics::DATE_CREATED => $page->getCreatedDateAsString(),
            Analytics::DATE_MODIFIED => $page->getModifiedDateAsString(),
            Publication::DATE_PUBLISHED => $page->getPublishedTimeAsString(),
            Analytics::DATE_START => $page->getEndDateAsString(),
            Analytics::DATE_END => $page->getStartDateAsString(),
            Page::COUNTRY_META_PROPERTY => $page->getCountryOrDefault(),
            Page::LANG_META_PROPERTY => $page->getLangOrDefault(),
            'IS_LOW_QUALITY' => ($page->isLowQualityPage() === true ? 1 : 0),
            Page::TYPE_META_PROPERTY => $page->getTypeNotEmpty(),
            'WORD_COUNT' => $analyticsJsonAsArray[Analytics::WORD_COUNT],
            'BACKLINK_COUNT' => $this->getBacklinkCount(),
            'IS_HOME' => ($page->isHomePage() === true ? 1 : 0),
            Page::UUID_ATTRIBUTE => $page->getUuid(),
            self::DATE_REPLICATION => $replicationDate,
            'ID' => $page->getId(),
        );

        return $this->upsertAttributes($record);

    }

    private function replicatePageReference(): bool
    {
        $referencedPagesIndex = $this->page->getInternalReferencedPages();
        if ($referencedPagesIndex == null) {
            return true;
        }
        $referencedPagesDb = $this->getInternalReferencedPages();
        foreach ($referencedPagesIndex as $internalPageReference) {
            if (!$internalPageReference->exists()) {
                continue;
            }
            if (in_array($internalPageReference->getId(), array_keys($referencedPagesDb), true)) {
                unset($referencedPagesDb[$internalPageReference->getId()]);
            } else {
                $record = [
                    "SOURCE_ID" => $this->page->getId(),
                    "TARGET_ID" => $internalPageReference->getId()
                ];
                $res = $this->sqlite->storeEntry('PAGE_REFERENCES', $record);
                if ($res === false) {
                    $errorInfo = $this->sqlite->getAdapter()->getDb()->errorInfo();
                    $errorInfoAsString = var_export($errorInfo, true);
                    LogUtility::msg("There was a problem during the page references insert : {$errorInfoAsString}");
                    return $res;
                }
                $reason = "The page ($this->page) has added a a backlink to the page {$internalPageReference}";
                $internalPageReference->getDatabasePage()->createReplicationRequest($reason);
            }
        }
        $delete = <<<EOF
delete from PAGE_REFERENCES where SOURCE_ID = ? and TARGET_ID = ?
EOF;

        foreach ($referencedPagesDb as $internalPageReference) {
            $row = [
                "SOURCE_ID" => $this->page->getId(),
                "TARGET_ID" => $internalPageReference->getId()
            ];
            $res = $this->sqlite->query($delete, $row);

            if ($res === false) {
                $errorInfo = $this->sqlite->getAdapter()->getDb()->errorInfo();
                $message = "";
                $errorCode = $errorInfo[0];
                if ($errorCode === '0000') {
                    $message = ("No rows were deleted");
                }
                $errorInfoAsString = var_export($errorInfo, true);
                LogUtility::msg("There was a problem during the reference delete. $message. : {$errorInfoAsString}");
                return false;
            }

            $reason = "The page ($this->page) has deleted a a backlink to the page {$internalPageReference}";
            $internalPageReference->getDatabasePage()->createReplicationRequest($reason);
        }

        return true;

    }

    /**
     * Sqlite is much quicker than the Dokuwiki Internal Index
     * We use it every time that we can
     *
     * @return int|null
     */
    public function getBacklinkCount(): ?int
    {
        if ($this->sqlite === null) {
            return null;
        }
        $res = $this->sqlite->query("select count(1) from PAGE_REFERENCES where TARGET_ID = ? ", $this->page->getId());
        if (!$res) {
            LogUtility::msg("An exception has occurred with the backlinks count select ({$this->page})");
        }
        $count = $this->sqlite->res2single($res);
        $this->sqlite->res_close($res);
        return intval($count);

    }

    /**
     * @return Page[]
     */
    private function getInternalReferencedPages(): array
    {

        if ($this->sqlite === null) {
            return [];
        }
        $res = $this->sqlite->query("select TARGET_ID from PAGE_REFERENCES where SOURCE_ID = ? ", $this->page->getId());
        if (!$res) {
            LogUtility::msg("An exception has occurred with the PAGE_REFERENCES ({$this->page}) selection query");
        }
        $targetPath = $this->sqlite->res2arr($res);
        $this->sqlite->res_close($res);
        $targetPaths = [];
        foreach ($targetPath as $row) {
            $targetId = $row["TARGET_ID"];
            $targetPaths[$targetId] = Page::createPageFromId($targetId);
        }
        return $targetPaths;

    }

    /**
     * @param array $attributes
     * @return bool when an update as occurred
     */
    public function upsertModifiableAttributes(array $attributes): bool
    {
        $databaseFields = [];
        foreach ($attributes as $key => $value) {
            $lower = strtolower($key);
            if (in_array($lower, DatabasePage::MODIFIABLE_ATTRIBUTES)) {
                $databaseFields[$key] = $value;
            }
        }
        if (!empty($databaseFields)) {
            return $this->upsertAttributes($databaseFields);
        } else {
            return false;
        }
    }

    private function upsertAttributes(array $attributes): bool
    {

        if ($this->sqlite === null) {
            return false;
        }

        if (empty($attributes)) {
            LogUtility::msg("The page database attribute passed should not be empty");
            return false;
        }

        $values = [];
        $columnClauses = [];
        foreach ($attributes as $key => $value) {
            $columnClauses[] = "$key = ?";
            $values[$key] = $value;
        }

        /**
         * Primary key has moved during the time
         * It should be the UUID but not for older version
         *
         * If the primary key is null, no record was found
         */
        $rowId = $this->getRowId();
        if ($rowId != null) {
            /**
             * We just add the primary key
             * otherwise as this is a associative
             * array, we will miss a value for the update statement
             */
            $values[self::ROWID] = $rowId;

            $updateStatement = "update PAGES SET " . implode($columnClauses, ", ") . " where ROWID = ?";
            $res = $this->sqlite->query($updateStatement, $values);
            if ($res === false) {
                $errorInfo = $this->sqlite->getAdapter()->getDb()->errorInfo();
                $message = "";
                $errorCode = $errorInfo[0];
                if ($errorCode === '0000') {
                    $message = ("No rows were updated");
                }
                $errorInfoAsString = var_export($errorInfo, true);
                $this->sqlite->res_close($res);
                LogUtility::msg("There was a problem during the page attribute updates. $message. : {$errorInfoAsString}");
                return false;
            }
            $countChanges = $this->sqlite->countChanges($res);
            if ($countChanges !== 1) {
                LogUtility::msg("The database replication has not update exactly one record but ($countChanges) record", LogUtility::LVL_MSG_ERROR, self::REPLICATION_CANONICAL);
            }
            $this->sqlite->res_close($res);

        } else {
            $values["id"] = $this->page->getId();
            $values[Analytics::PATH] = $this->page->getPath();
            $values[Page::UUID_ATTRIBUTE] = $this->page->getUuid();
            /**
             * TODO: Canonical should be able to be null
             * When the not null constraint on canonical is deleted, we can delete
             * the line below
             */
            $values[Analytics::CANONICAL] = $this->page->getCanonicalOrDefault();
            $res = $this->sqlite->storeEntry('PAGES', $values);
            $this->sqlite->res_close($res);
            if ($res === false) {
                $errorInfo = $this->sqlite->getAdapter()->getDb()->errorInfo();
                $errorInfoAsString = var_export($errorInfo, true);
                LogUtility::msg("There was a problem during the updateAttributes insert. : {$errorInfoAsString}");
                return false;
            }
        }
        return true;

    }

    public function getDescription()
    {
        return $this->getAttribute(Analytics::DESCRIPTION);
    }

    private function getAttribute(string $attribute)
    {
        if ($this->sqlite === null) {
            return null;
        }
        $res = $this->sqlite->query("select $attribute from pages where ID = ? ", $this->page->getId());
        if (!$res) {
            LogUtility::msg("An exception has occurred with the retrieve of the attribute $attribute for the page ({$this->page}) selection query");
        }
        $value = $this->sqlite->res2single($res);
        $this->sqlite->res_close($res);
        if ($value === false) {
            // Sqlite does not have the false datatype (ouff)
            return null;
        } else {
            return $value;
        }
    }

    public function getPageName()
    {
        return $this->getAttribute(Page::NAME_PROPERTY);
    }

    public function exists(): bool
    {
        return $this->getRowId() !== null;
    }

    public function moveTo($targetId)
    {
        if (!$this->exists()) {
            LogUtility::msg("The database page ($this) does not exist and cannot be moved to ($targetId)", LogUtility::LVL_MSG_ERROR);
        }
        $uuid = $this->page->getUuid();
        $attributes = [
            "id" => $targetId,
            Page::PATH_ATTRIBUTE => ":${$targetId}",
            Page::UUID_ATTRIBUTE => $uuid
        ];

        $this->upsertAttributes($attributes);
        /**
         * The UUID is created on page creation
         * We need to update it on the target page
         */
        if ($uuid === null) {
            LogUtility::msg("During a move, the uuid of the page ($this) to ($targetId) was null. It should not be the case as this page exists. The UUID was not passed over to the target page.",LogUtility::LVL_MSG_ERROR,self::REPLICATION_CANONICAL);
            return;
        }
        $targetPage = Page::createPageFromId($targetId);
        $targetPage->setUuid($uuid);

    }

    public function __toString()
    {
        return $this->page->__toString();
    }


}
