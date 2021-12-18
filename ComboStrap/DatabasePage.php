<?php


namespace ComboStrap;

use http\Exception\RuntimeException;
use ModificationDate;
use ReplicationDate;

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
     * The list of attributes that are set
     * at build time
     * used in the build functions such as {@link DatabasePage::getDatabaseRowFromPage()}
     * to build the sql
     */
    private const PAGE_BUILD_ATTRIBUTES =
        [
            self::ROWID,
            DokuwikiId::DOKUWIKI_ID_ATTRIBUTE,
            self::ANALYTICS_ATTRIBUTE,
            PageDescription::PROPERTY_NAME,
            Canonical::PROPERTY_NAME,
            ResourceName::PROPERTY_NAME,
            PageTitle::TITLE,
            PageH1::PROPERTY_NAME,
            PagePublicationDate::PROPERTY_NAME,
            ModificationDate::PROPERTY_NAME,
            PageCreationDate::PROPERTY_NAME,
            PagePath::PROPERTY_NAME,
            StartDate::PROPERTY_NAME,
            EndDate::PROPERTY_NAME,
            Region::PROPERTY_NAME,
            Lang::PROPERTY_NAME,
            PageType::PROPERTY_NAME,
            PageId::PROPERTY_NAME
        ];
    const ANALYTICS_ATTRIBUTE = "ANALYTICS";

    /**
     * For whatever reason, the row id is lowercase
     */
    const ROWID = "rowid";

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
     * @throws ExceptionCombo
     */
    public function replicate(): DatabasePage
    {
        if ($this->sqlite === null) {
            throw new ExceptionCombo("Sqlite is mandatory for database replication");
        }

        if (!$this->page->exists()) {
            throw new ExceptionCombo("You can't replicate the non-existing page ($this->page) on the file system");
        }

        /**
         * Replication Date
         */
        $replicationDateMeta = ReplicationDate::createFromPage($this->page)
            ->setValue(new \DateTime());

        $this->replicatePage($replicationDateMeta);

        /**
         * @var Metadata $tabularMetadataToSync
         */
        $tabularMetadataToSync = [
            ( new References()),
            (new Aliases())
        ];
        $fsStore = MetadataDokuWikiStore::createFromResource($this->page);
        $dbStore = MetadataDbStore::createFromResource($this->page);
        foreach($tabularMetadataToSync as $tabular){
            $tabular
                ->setResource($this->page)
                ->setReadStore($fsStore)
                ->buildFromReadStore()
                ->setWriteStore($dbStore)
                ->persist();
        }

        /**
         * Set the replication date
         */
        $replicationDateMeta
            ->persist();

        return $this;

    }

    /**
     * @throws ExceptionCombo
     */
    public function replicateAndRebuild(){
        $this->replicate();
        $this->rebuild();
    }

    private function addPageIdMeta(array &$metaRecord)
    {
        $metaRecord[PageId::PROPERTY_NAME] = $this->page->getPageIdOrGenerate();
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
        if ($row !== null) {
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
        $row = $databasePage->getDatabaseRowFromAttribute(Canonical::PROPERTY_NAME, $canonical);
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
        return $this->getFromRow(PageId::PROPERTY_NAME);
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

    public function getReplicationDate(): ?\DateTime
    {
        return ReplicationDate::createFromPage($this->page)
            ->getValue();

    }

    /**
     * @param ReplicationDate $replicationDate
     * @return bool
     * @throws ExceptionCombo
     */
    public function replicatePage(ReplicationDate $replicationDate): bool
    {

        if (!$this->page->exists()) {
            throw new ExceptionCombo("You can't replicate the page ($this->page) because it does not exists.");
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
        $record[ReplicationDate::PROPERTY_NAME] = $replicationDate->toStoreValue();


        return $this->upsertAttributes($record);

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
        $res = $this->sqlite->query("select count(1) from PAGE_REFERENCES where REFERENCE = ? ", $this->page->getPath()->toString());
        if (!$res) {
            LogUtility::msg("An exception has occurred with the backlinks count select ({$this->page})");
        }
        $count = $this->sqlite->res2single($res);
        $this->sqlite->res_close($res);
        return intval($count);

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
            if (is_array($value)) {
                throw new ExceptionComboRuntime("The attribute ($key) has value that is an array (" . implode(", ", $value) . ")");
            }
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
        if ($rowId !== null) {
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
                LogUtility::msg("The database replication has not updated exactly 1 record but ($countChanges) record", LogUtility::LVL_MSG_ERROR, ReplicationDate::REPLICATION_CANONICAL);
            }
            $this->sqlite->res_close($res);

        } else {


            $values[DokuwikiId::DOKUWIKI_ID_ATTRIBUTE] = $this->page->getPath()->getDokuwikiId();
            $values[PagePath::PROPERTY_NAME] = $this->page->getPath()->toAbsolutePath()->toString();
            $this->addPageIdAttribute($values);

            /**
             * Default implements the auto-canonical feature
             */
            $values[Canonical::PROPERTY_NAME] = $this->page->getCanonicalOrDefault();
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
        return $this->getFromRow(ResourceName::PROPERTY_NAME);
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
            DokuwikiId::DOKUWIKI_ID_ATTRIBUTE => $targetId,
            PagePath::PROPERTY_NAME => $path
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
        return $this->getFromRow(Canonical::PROPERTY_NAME);
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
                        throw new ExceptionComboRuntime($message);
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

    public function rebuild(): DatabasePage
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
        $sourceStore = MetadataDokuWikiStore::createFromResource($this->page);
        $targetStore = MetadataDbStore::createFromResource($this->page);

        $record = array(
            Canonical::PROPERTY_NAME,
            PagePath::PROPERTY_NAME,
            ResourceName::PROPERTY_NAME,
            PageTitle::TITLE,
            PageH1::PROPERTY_NAME,
            PageDescription::PROPERTY_NAME,
            PageCreationDate::PROPERTY_NAME,
            ModificationDate::PROPERTY_NAME,
            PagePublicationDate::PROPERTY_NAME,
            StartDate::PROPERTY_NAME,
            EndDate::PROPERTY_NAME,
            Region::PROPERTY_NAME,
            Lang::PROPERTY_NAME,
            PageType::PROPERTY_NAME,
            DokuwikiId::DOKUWIKI_ID_ATTRIBUTE,
        );
        $metaRecord = [];
        foreach ($record as $name) {
            $metadata = Metadata::getForName($name);
            if ($metadata === null) {
                throw new ExceptionComboRuntime("The metadata ($name) is unknown");
            }
            $metaRecord[$name] = $metadata
                ->setResource($this->page)
                ->setReadStore($sourceStore)
                ->buildFromReadStore()
                ->setWriteStore($targetStore)
                ->toStoreValueOrDefault();
        }

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
        return $this->getFromRow(self::ROWID);
    }

    private function getDatabaseRowFromPageId(string $pageId)
    {
        $pageIdAttribute = PageId::PROPERTY_NAME;
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
        $query = $this->getParametrizedLookupQuery(Canonical::PROPERTY_NAME);
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
                        $canonicalLastNamesCount = PluginUtility::getConfValue(Canonical::CONF_CANONICAL_LAST_NAMES_COUNT, 0);
                        if ($canonicalLastNamesCount > 0) {
                            $this->page->unsetMetadata(Canonical::PROPERTY_NAME);
                            $duplicatePage->unsetMetadata(Canonical::PROPERTY_NAME);
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
        DokuPath::addRootSeparatorIfNotPresent($path);
        return $this->getDatabaseRowFromAttribute(PagePath::PROPERTY_NAME, $path);
    }

    private function getDatabaseRowFromDokuWikiId(string $id): ?array
    {
        return $this->getDatabaseRowFromAttribute(DokuwikiId::DOKUWIKI_ID_ATTRIBUTE, $id);
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

        $pageIdAttribute = PageId::PROPERTY_NAME;
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
                LogUtility::msg("For the alias $alias, there is more than one page defined ($pages), the first one ($id) was used", LogUtility::LVL_MSG_ERROR, Aliases::PROPERTY_NAME);
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
                ->sendToWriteStore();
        } catch (ExceptionCombo $e) {
            // we don't throw while getting
            LogUtility::msg("Unable to add the alias ($aliasPath) for the page ($this->page)");
        }

    }

    private function addPageIdAttribute(array &$values)
    {

        $values[PageId::PROPERTY_NAME] = $this->page->getPageIdOrGenerate();
        $values[Page::PAGE_ID_ABBR_ATTRIBUTE] = $this->page->getPageIdAbbr();
    }

    public function getFromRow(string $attribute)
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
