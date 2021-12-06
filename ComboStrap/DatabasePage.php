<?php


namespace ComboStrap;

/**
 * The class that manage the replication
 * Class Replicate
 * @package ComboStrap
 *
 * The database can also be seen as a {@link MetadataStore}
 * and an {@link Index}
 */
class DatabasePage
{
    /**
     * The attribute in the metadata and in the database
     */
    public const DATE_REPLICATION = "date_replication";


    /**
     * The list of attributes that are set
     * at build time
     * used in the build functions such as {@link DatabasePage::getDatabaseRowFromPage()}
     * to build the sql
     */
    private const PAGE_BUILD_ATTRIBUTES =
        [
            self::ROWID,
            Path::DOKUWIKI_ID_ATTRIBUTE,
            self::ANALYTICS_ATTRIBUTE,
            PageDescription::DESCRIPTION,
            Canonical::CANONICAL_PROPERTY,
            PageName::NAME_PROPERTY,
            PageTitle::TITLE,
            PageH1::H1_PROPERTY,
            PagePublicationDate::DATE_PUBLISHED,
            StartDate::DATE_START,
            EndDate::DATE_END,
            Page::REGION_META_PROPERTY,
            Lang::LANG_ATTRIBUTES,
            PageType::TYPE_META_PROPERTY
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
     * @var array
     */
    private $row;

    /**
     * Replicate constructor.
     */
    public function __construct()
    {

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
        $replicationDate = Iso8601Date::createFromNow()->toString();
        $res = $this->replicatePage($replicationDate);
        if ($res === false) {
            return false;
        }

        $res = $this->replicateBacklinkPages();
        if ($res === false) {
            return false;
        }

        try {
            Aliases::createForPage($this->page)
                ->buildFromStore()
                ->setStore(MetadataDbStore::getOrCreate())
                ->sendToStore();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Error replicating the page aliases " . $e->getMessage(), self::REPLICATION_CANONICAL);
            return false;
        }

        /**
         * Set the replication date
         */
        $this->page->setRuntimeMetadata(self::DATE_REPLICATION, $replicationDate);
        return true;

    }

    private function addPageIdMeta(array &$metaRecord)
    {
        $metaRecord[PageId::PAGE_ID_ATTRIBUTE] = $this->page->getPageIdOrGenerate();
        $metaRecord[Page::PAGE_ID_ABBR_ATTRIBUTE] = $this->page->getPageIdAbbr();
    }

    public static function createFromPageId(string $pageId): DatabasePage
    {
        $databasePage = new DatabasePage();
        $row = $databasePage->getDatabaseRowFromPageId($pageId);
        if ($row != null) {
            $databasePage->buildDatabaseObjectFields($row);
        }
        return $databasePage;
    }

    public static function createFromPageObject(Page $page): DatabasePage
    {

        $databasePage = new DatabasePage();
        $row = $databasePage->getDatabaseRowFromPage($page);
        if ($row != null) {
            $databasePage->buildDatabaseObjectFields($row);
        }
        return $databasePage;
    }

    public static function createFromPageIdAbbr(string $pageIdAbbr): DatabasePage
    {
        $databasePage = new DatabasePage();
        $row = $databasePage->getDatabaseRowFromAttribute(Page::PAGE_ID_ABBR_ATTRIBUTE, $pageIdAbbr);
        if ($row != null) {
            $databasePage->buildDatabaseObjectFields($row);
        }
        return $databasePage;

    }

    /**
     * @param $canonical
     * @return DatabasePage
     */
    public static function createFromCanonical($canonical): DatabasePage
    {

        DokuPath::addRootSeparatorIfNotPresent($canonical);
        $databasePage = new DatabasePage();
        $row = $databasePage->getDatabaseRowFromAttribute(Canonical::CANONICAL_PROPERTY, $canonical);
        if ($row != null) {
            $databasePage->buildDatabaseObjectFields($row);
        }
        return $databasePage;


    }

    public static function createFromAlias($alias): DatabasePage
    {

        DokuPath::addRootSeparatorIfNotPresent($alias);
        $databasePage = new DatabasePage();
        $row = $databasePage->getDatabaseRowFromAlias($alias);
        if ($row != null) {
            $databasePage->buildDatabaseObjectFields($row);
            $databasePage->getPage()->setBuildAliasPath($alias);
        }
        return $databasePage;

    }

    public static function createFromDokuWikiId($id): DatabasePage
    {
        $databasePage = new DatabasePage();
        $row = $databasePage->getDatabaseRowFromDokuWikiId($id);
        if ($row !== null) {
            $databasePage->buildDatabaseObjectFields($row);
        }
        return $databasePage;
    }

    public function getPageId()
    {
        return $this->getFromRow(PageId::PAGE_ID_ATTRIBUTE);
    }

    /**
     * Create a page id before insertion
     * and when the row does not have any
     * (Needed for the replication of the alias)
     */
    private function createPageIdIfNeeded()
    {
        if ($this->page != null) {
            $pageId = $this->page->getPageId();
            if ($pageId === null || !is_string($pageId)
                || preg_match("/[-_A-Z]/", $pageId)
            ) {
                $pageId = PageId::generateUniquePageId();
                $this->page->setPageId($pageId);
            }
        }
    }

    public
    function shouldReplicate(): bool
    {

        /**
         * When the file does not exist
         */
        $modifiedTime = $this->page->getAnalyticsDocument()->getCachePath()->getModifiedTime();
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

        $res = Sqlite::getSqlite()->query('delete from pages where id = ?', $this->page->getDokuwikiId());
        if (!$res) {
            LogUtility::msg("Something went wrong when deleting the page ({$this->page})");
        }
        $this->buildInitObjectFields();

    }

    /**
     * @return Json|null the analytics array or null if not in db
     */
    public
    function getAnalyticsData(): ?Json
    {

        $jsonString = $this->getFromRow(self::ANALYTICS_ATTRIBUTE);
        if ($jsonString === null) {
            return null;
        }
        return Json::createFromString($jsonString);

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
        $res = $this->sqlite->query("select count(1) from PAGES_TO_REPLICATE where ID = ?", array('ID' => $this->page->getDokuwikiId()));
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
            "ID" => $this->page->getDokuwikiId(),
            "TIMESTAMP" => Iso8601Date::createFromNow()->toString(),
            "REASON" => $reason
        );
        $res = $this->sqlite->storeEntry('PAGES_TO_REPLICATE', $entry);
        if (!$res) {
            LogUtility::msg("There was a problem during the insert into PAGES_TO_REPLICATE: {$this->sqlite->getAdapter()->getDb()->errorInfo()}");
        }
        $this->sqlite->res_close($res);


    }

    /**
     * Return the database row
     *
     *
     */
    private
    function getDatabaseRowFromPage(Page $page): ?array
    {

        $this->setPage($page);

        if ($this->sqlite === null) return null;

        // Do we have a page attached to this page id
        $pageId = $page->getPageId();
        if ($pageId != null) {
            $row = $this->getDatabaseRowFromPageId($pageId);
            if ($row !== null) {
                return $row;
            }
        }

        // Do we have a page attached to the canonical
        $canonical = $page->getCanonical();
        if ($canonical != null) {
            $row = $this->getDatabaseRowFromCanonical($canonical);
            if ($row !== null) {
                return $row;
            }
        }

        // Do we have a page attached to the path
        $path = $page->getPath();
        $row = $this->getDatabaseRowFromPath($path);
        if ($row !== null) { // the path may no yet updated in the db
            return $row;
        }

        /**
         * Do we have a page attached to this ID
         */
        $id = $page->getPath()->getDokuwikiId();
        return $this->getDatabaseRowFromDokuWikiId($id);


    }

    public function getReplicationDate()
    {
        $stringReplicationDate = $this->page->getMetadata(DatabasePage::DATE_REPLICATION);
        if (empty($stringReplicationDate)) {
            return null;
        } else {
            try {
                return Iso8601Date::createFromString($stringReplicationDate)->getDateTime();
            } catch (ExceptionCombo $e) {
                LogUtility::msg("The date value should be good when inserting. " . $e->getMessage());
                return null;
            }
        }
    }

    /**
     * @param string $replicationDate
     * @return bool
     */
    public function replicatePage(string $replicationDate): bool
    {

        if (!$this->page->exists()) {
            LogUtility::msg("You can't replicate the page ($this->page) because it does not exists.");
            return false;
        }

        /**
         * Convenient variable
         */
        $page = $this->page;


        /**
         * Render and save on the file system
         */
        $analyticsJson = $this->page->getAnalyticsDocument()->getOrProcessJson();
        $analyticsJsonAsString = $analyticsJson->toPrettyJsonString();
        $analyticsJsonAsArray = $analyticsJson->toArray();
        /**
         * Same data as {@link Page::getMetadataForRendering()}
         */
        $record = $this->getMetaRecord();
        $record[self::ANALYTICS_ATTRIBUTE] = $analyticsJsonAsString;
        $record['IS_LOW_QUALITY'] = ($page->isLowQualityPage() === true ? 1 : 0);
        $record['WORD_COUNT'] = $analyticsJsonAsArray[AnalyticsDocument::WORD_COUNT];
        $record['BACKLINK_COUNT'] = $this->getBacklinkCount();
        $record['IS_HOME'] = ($page->isHomePage() === true ? 1 : 0);
        $record[self::DATE_REPLICATION] = $replicationDate;


        return $this->upsertAttributes($record);

    }

    private function replicateBacklinkPages(): bool
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
            if (in_array($internalPageReference->getDokuwikiId(), array_keys($referencedPagesDb), true)) {
                unset($referencedPagesDb[$internalPageReference->getDokuwikiId()]);
            } else {
                $record = [
                    "SOURCE_ID" => $this->page->getDokuwikiId(),
                    "TARGET_ID" => $internalPageReference->getDokuwikiId()
                ];
                $res = $this->sqlite->storeEntry('PAGE_REFERENCES', $record);
                if ($res === false) {
                    $errorInfo = $this->sqlite->getAdapter()->getDb()->errorInfo();
                    $errorInfoAsString = var_export($errorInfo, true);
                    LogUtility::msg("There was a problem during the page references insert : {$errorInfoAsString}");
                    return $res;
                }
                $reason = "The page ($this->page) has added a backlink to the page {$internalPageReference}";
                $internalPageReference->getDatabasePage()->createReplicationRequest($reason);
            }
        }
        $delete = <<<EOF
delete from PAGE_REFERENCES where SOURCE_ID = ? and TARGET_ID = ?
EOF;

        foreach ($referencedPagesDb as $internalPageReference) {
            $row = [
                "SOURCE_ID" => $this->page->getDokuwikiId(),
                "TARGET_ID" => $internalPageReference->getDokuwikiId()
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
        $res = $this->sqlite->query("select count(1) from PAGE_REFERENCES where TARGET_ID = ? ", $this->page->getPath()->getDokuwikiId());
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
        $res = $this->sqlite->query("select TARGET_ID from PAGE_REFERENCES where SOURCE_ID = ? ", $this->page->getDokuwikiId());
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
     * @return bool when an update as occurred
     *
     * Attribute that are scalar / modifiable in the database
     * (not aliases or json data for instance)
     */
    public function replicateMetaAttributes(): bool
    {

        return $this->upsertAttributes($this->getMetaRecord());

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
            $values[] = $rowId;

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
                LogUtility::msg("The database replication has not updated exactly 1 record but ($countChanges) record", LogUtility::LVL_MSG_ERROR, self::REPLICATION_CANONICAL);
            }
            $this->sqlite->res_close($res);

        } else {


            $values[Path::DOKUWIKI_ID_ATTRIBUTE] = $this->page->getPath()->getDokuwikiId();
            $values[Path::PATH_ATTRIBUTE] = $this->page->getPath()->toAbsolutePath()->toString();
            $this->addPageIdAttribute($values);

            /**
             * Default implements the auto-canonical feature
             */
            $values[Canonical::CANONICAL_PROPERTY] = $this->page->getCanonicalOrDefault();
            $res = $this->sqlite->storeEntry('PAGES', $values);
            $this->sqlite->res_close($res);
            if ($res === false) {
                $errorInfo = $this->sqlite->getAdapter()->getDb()->errorInfo();
                $errorInfoAsString = var_export($errorInfo, true);
                LogUtility::msg("There was a problem during the updateAttributes insert. : {$errorInfoAsString}");
                return false;
            } else {
                /**
                 * rowid is used in {@link DatabasePage::exists()}
                 * to check if the page exists in the database
                 * We update it
                 */
                $this->row[self::ROWID] = $this->sqlite->getAdapter()->getDb()->lastInsertId();
            }
        }
        return true;

    }

    public function getDescription()
    {
        return $this->getFromRow(PageDescription::DESCRIPTION_PROPERTY);
    }


    public function getPageName()
    {
        return $this->getFromRow(PageName::NAME_PROPERTY);
    }

    public function exists(): bool
    {
        return $this->getFromRow(self::ROWID) !== null;
    }

    /**
     * Called when a page is moved
     * @param $targetId
     */
    public function updatePathAndDokuwikiId($targetId)
    {
        if (!$this->exists()) {
            LogUtility::msg("The `database` page ($this) does not exist and cannot be moved to ($targetId)", LogUtility::LVL_MSG_ERROR);
        }

        $path = $targetId;
        DokuPath::addRootSeparatorIfNotPresent($path);
        $attributes = [
            Path::DOKUWIKI_ID_ATTRIBUTE => $targetId,
            Path::PATH_ATTRIBUTE => $path
        ];

        $this->upsertAttributes($attributes);

    }

    public function __toString()
    {
        return $this->page->__toString();
    }


    /**
     * Redirect are now added during a move
     * Not when a duplicate is found.
     * With the advent of the page id, it should never occurs anymore
     * @param Page $page
     * @deprecated 2012-10-28
     */
    private function deleteIfExistsAndAddRedirectAlias(Page $page): void
    {

        if ($this->page != null) {
            $page->getDatabasePage()->deleteIfExist();
            $this->addRedirectAliasWhileBuildingRow($page);
        }

    }

    public function getCanonical()
    {
        return $this->getFromRow(Canonical::CANONICAL_PROPERTY);
    }

    /**
     * Set the field to their values
     * @param $row
     */
    public function buildDatabaseObjectFields($row)
    {
        if ($row === null) {
            LogUtility::msg("A row should not be null");
            return;
        }
        if (!is_array($row)) {
            LogUtility::msg("A row should be an array");
            return;
        }

        /**
         * All get function lookup the row
         */
        $this->row = $row;

        if ($this->page !== null) {
            /**
             * Get back the id from the database if the metadata file was deleted
             */
            if ($this->page->getPageId() === null
                && $this->getPageId() !== ""
                && $this->getPageId() !== null
            ) {
                try {
                    $this->page->setPageId($this->getPageId());
                } catch (ExceptionCombo $e) {
                    $message = "The page id of the page was null and we tried to update it with the page id of the database ({$this->getPageId()}) but we got an error: " . $e->getMessage();
                    if (PluginUtility::isDevOrTest()) {

                    } else {
                        LogUtility::msg($message);
                    }

                }
            }
        }


    }

    private function buildInitObjectFields()
    {
        $this->row = null;

    }

    public function refresh(): DatabasePage
    {

        if ($this->page != null) {
            $this->page->rebuild();
            $row = $this->getDatabaseRowFromPage($this->page);
            $this->buildDatabaseObjectFields($row);
        }
        return $this;

    }

    /**
     * @return array - an array of the fix page metadata (ie not derived)
     * Therefore quick to insert/update
     *
     */
    private function getMetaRecord(): array
    {
        $metaRecord = array(
            Canonical::CANONICAL_PROPERTY => $this->page->getCanonicalOrDefault(),
            Path::PATH_ATTRIBUTE => $this->page->getPath()->toAbsolutePath()->toString(),
            PageName::NAME_PROPERTY => $this->page->getPageNameNotEmpty(),
            PageTitle::TITLE => $this->page->getTitleOrDefault(),
            PageH1::H1_PROPERTY => $this->page->getH1OrDefault(),
            PageDescription::DESCRIPTION => $this->page->getDescriptionOrElseDokuWiki(),
            PageCreationDate::DATE_CREATED => $this->page->getCreatedDateAsString(),
            AnalyticsDocument::DATE_MODIFIED => $this->page->getModifiedDateAsString(),
            PagePublicationDate::DATE_PUBLISHED => $this->page->getPublishedTimeAsString(),
            StartDate::DATE_START => $this->page->getEndDateAsString(),
            EndDate::DATE_END => $this->page->getStartDateAsString(),
            Page::REGION_META_PROPERTY => $this->page->getRegionOrDefault(),
            Lang::LANG_ATTRIBUTES => $this->page->getLangOrDefault(),
            PageType::TYPE_META_PROPERTY => $this->page->getTypeNotEmpty(),
            Path::DOKUWIKI_ID_ATTRIBUTE => $this->page->getPath()->getDokuwikiId(),
        );

        if ($this->page->getPageId() !== null) {
            $this->addPageIdMeta($metaRecord);
        };
        return $metaRecord;
    }

    public function deleteIfExist(): DatabasePage
    {
        if ($this->exists()) {
            $this->delete();
        }
        return $this;
    }

    public function getRowId()
    {
        return $this->rowId;
    }

    private function getDatabaseRowFromPageId(string $pageId)
    {
        $pageIdAttribute = PageId::PAGE_ID_ATTRIBUTE;
        $query = $this->getParametrizedLookupQuery($pageIdAttribute);
        $res = $this->sqlite->query($query, $pageId);
        if (!$res) {
            LogUtility::msg("An exception has occurred with the page search from page id");
        }
        $rows = $this->sqlite->res2arr($res);
        $this->sqlite->res_close($res);
        switch (sizeof($rows)) {
            case 0:
                break;
            case 1:
                $id = $rows[0]["ID"];
                /**
                 * Page Id Collision detection
                 */
                if ($this->page != null && $id !== $this->page->getDokuwikiId()) {
                    $duplicatePage = Page::createPageFromId($id);
                    if (!$duplicatePage->exists()) {
                        // Move
                        LogUtility::msg("The non-existing duplicate page ($id) has been added as redirect alias for the page ($this->page)", LogUtility::LVL_MSG_INFO);
                        $this->addRedirectAliasWhileBuildingRow($duplicatePage);
                    } else {
                        // This can happens if two page were created not on the same website
                        // of if the sqlite database was deleted and rebuilt.
                        // The chance is really, really low
                        $errorMessage = "The page ($this->page) and the page ($id) have the same page id ($pageId)";
                        LogUtility::msg($errorMessage, LogUtility::LVL_MSG_ERROR);
                        // What to do ?
                        // The database does not allow two page id with the same value
                        // If it happens, ugh, ugh, ..., a replication process between website may be.
                        return null;
                    }
                }
                return $rows[0];
            default:
                $existingPages = implode(", ", $rows);
                LogUtility::msg("The pages ($existingPages) have all the same page id ($pageId)", LogUtility::LVL_MSG_ERROR);
        }
        return null;
    }


    private function getParametrizedLookupQuery(string $pageIdAttribute): string
    {
        $databaseFields = implode(self::PAGE_BUILD_ATTRIBUTES, ", ");
        return "select $databaseFields from pages where $pageIdAttribute = ?";
    }


    private function setPage(Page $page)
    {
        $this->page = $page;
    }

    private function getDatabaseRowFromCanonical($canonical)
    {
        $query = $this->getParametrizedLookupQuery(Canonical::CANONICAL_PROPERTY);
        $res = $this->sqlite->query($query, $canonical);
        if (!$res) {
            LogUtility::msg("An exception has occurred with the page search from CANONICAL");
        }
        $rows = $this->sqlite->res2arr($res);
        $this->sqlite->res_close($res);

        switch (sizeof($rows)) {
            case 0:
                return null;
            case 1:
                $id = $rows[0]["ID"];
                if ($this->page !== null && $id !== $this->page->getDokuwikiId()) {
                    $duplicatePage = Page::createPageFromId($id);
                    if (!$duplicatePage->exists()) {
                        $this->addRedirectAliasWhileBuildingRow($duplicatePage);
                        LogUtility::msg("The non-existing duplicate page ($id) has been added as redirect alias for the page ($this->page)", LogUtility::LVL_MSG_INFO);
                    } else {
                        LogUtility::msg("The page ($this->page) and the page ($id) have the same canonical ($canonical)", LogUtility::LVL_MSG_ERROR);
                    }
                }
                return $rows[0];
            default:
                $existingPages = [];
                foreach ($rows as $row) {
                    $id = $row["ID"];
                    $duplicatePage = Page::createPageFromId($id);
                    if (!$duplicatePage->exists()) {

                        $this->deleteIfExistsAndAddRedirectAlias($duplicatePage);

                    } else {

                        /**
                         * Check if the error may come from the auto-canonical
                         * (Never ever save generated data)
                         */
                        $canonicalLastNamesCount = PluginUtility::getConfValue(\action_plugin_combo_metacanonical::CANONICAL_LAST_NAMES_COUNT_CONF);
                        if ($canonicalLastNamesCount > 0) {
                            $this->page->unsetMetadata(Canonical::CANONICAL_PROPERTY);
                            $duplicatePage->unsetMetadata(Canonical::CANONICAL_PROPERTY);
                        }

                        $existingPages[] = $row;
                    }
                }
                if (sizeof($existingPages) === 1) {
                    return $existingPages[0];
                } else {
                    $existingPages = implode(", ", $existingPages);
                    LogUtility::msg("The existing pages ($existingPages) have all the same canonical ($canonical)", LogUtility::LVL_MSG_ERROR);
                    return null;
                }
        }
    }

    private function getDatabaseRowFromPath(string $path): ?array
    {
        return $this->getDatabaseRowFromAttribute(Path::PATH_ATTRIBUTE, $path);
    }

    private function getDatabaseRowFromDokuWikiId(string $id): ?array
    {
        return $this->getDatabaseRowFromAttribute(Path::DOKUWIKI_ID_ATTRIBUTE, $id);
    }

    public function getDatabaseRowFromAttribute(string $attribute, string $value)
    {
        $query = $this->getParametrizedLookupQuery($attribute);
        $res = $this->sqlite->query($query, $value);
        if (!$res) {
            LogUtility::msg("An exception has occurred with the page search from a PATH");
        }
        $rows = $this->sqlite->res2arr($res);
        $this->sqlite->res_close($res);
        switch (sizeof($rows)) {
            case 0:
                return null;
            case 1:
                $value = $rows[0]["ID"];
                if ($this->page != null && $value !== $this->page->getDokuwikiId()) {
                    $duplicatePage = Page::createPageFromId($value);
                    if (!$duplicatePage->exists()) {
                        $this->addRedirectAliasWhileBuildingRow($duplicatePage);
                    } else {
                        LogUtility::msg("The page ($this->page) and the page ($value) have the same $attribute ($value)", LogUtility::LVL_MSG_ERROR);
                    }
                }
                return $rows[0];
            default:
                $existingPages = [];
                foreach ($rows as $row) {
                    $value = $row["ID"];
                    $duplicatePage = Page::createPageFromId($value);
                    if (!$duplicatePage->exists()) {

                        $this->deleteIfExistsAndAddRedirectAlias($duplicatePage);

                    } else {
                        $existingPages[] = $row;
                    }
                }
                if (sizeof($existingPages) === 1) {
                    return $existingPages[0];
                } else {
                    $existingPages = implode(", ", $existingPages);
                    LogUtility::msg("The existing pages ($existingPages) have all the same $attribute ($value)", LogUtility::LVL_MSG_ERROR);
                    return null;
                }
        }
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }

    private function getDatabaseRowFromAlias($alias): ?array
    {

        $pageIdAttribute = PageId::PAGE_ID_ATTRIBUTE;
        $buildFields = self::PAGE_BUILD_ATTRIBUTES;
        $fields = array_reduce($buildFields, function ($carry, $element) {
            if ($carry !== null) {
                return "$carry, p.{$element}";
            } else {
                return "p.{$element}";
            }
        }, null);
        $query = "select {$fields} from PAGES p, PAGE_ALIASES pa where p.{$pageIdAttribute} = pa.{$pageIdAttribute} and pa.PATH = ? ";
        $res = $this->sqlite->query($query, $alias);
        if (!$res) {
            LogUtility::msg("An exception has occurred with the alias selection query");
        }
        $res2arr = $this->sqlite->res2arr($res);
        $this->sqlite->res_close($res);
        switch (sizeof($res2arr)) {
            case 0:
                return null;
            case 1:
                return $res2arr[0];
            default:
                $id = $res2arr[0]['ID'];
                $pages = implode(",",
                    array_map(
                        function ($row) {
                            return $row['ID'];
                        },
                        $res2arr
                    )
                );
                LogUtility::msg("For the alias $alias, there is more than one page defined ($pages), the first one ($id) was used", LogUtility::LVL_MSG_ERROR, Aliases::ALIAS_ATTRIBUTE);
                return $res2arr[0];
        }
    }


    /**
     * Utility function
     * @param Page $pageAlias
     */
    private function addRedirectAliasWhileBuildingRow(Page $pageAlias)
    {

        $aliasPath = $pageAlias->getPath()->toString();
        try {
            Aliases::createForPage($this->page)
                ->addAlias($aliasPath)
                ->sendToStore();
        } catch (ExceptionCombo $e) {
            // we don't throw while getting
            LogUtility::msg("Unable to add the alias ($aliasPath) for the page ($this->page)");
        }

    }

    private function addPageIdAttribute(array &$values)
    {

        $values[PageId::PAGE_ID_ATTRIBUTE] = $this->page->getPageIdOrGenerate();
        $values[Page::PAGE_ID_ABBR_ATTRIBUTE] = $this->page->getPageIdAbbr();
    }

    private function getFromRow(string $attribute)
    {
        // don't know why but the sqlite plugin returns them uppercase
        $name = strtoupper($attribute);
        if ($attribute === self::ROWID) {
            // rowid is returned lowercase from the sqlite plugin
            $name = self::ROWID;
        }
        return $this->row[$name];
    }


}
