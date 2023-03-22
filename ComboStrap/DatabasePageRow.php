<?php


namespace ComboStrap;

use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataStore;
use ComboStrap\Meta\Field\Aliases;
use ComboStrap\Meta\Field\BacklinkCount;
use ComboStrap\Meta\Field\PageH1;
use ComboStrap\Meta\Field\Region;
use ComboStrap\Meta\Store\MetadataDbStore;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;
use DateTime;
use renderer_plugin_combo_analytics;

/**
 * The class that manage the replication
 * Class Replicate
 * @package ComboStrap
 *
 * The database can also be seen as a {@link MetadataStore}
 * and an {@link Index}
 */
class DatabasePageRow
{


    /**
     * The list of attributes that are set
     * at build time
     * used in the build functions such as {@link DatabasePageRow::getDatabaseRowFromPage()}
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
            PageId::PROPERTY_NAME,
            PageId::PAGE_ID_ABBR_ATTRIBUTE,
            ReplicationDate::PROPERTY_NAME,
            BacklinkCount::PROPERTY_NAME
        ];
    const ANALYTICS_ATTRIBUTE = "analytics";

    /**
     * For whatever reason, the row id is lowercase
     */
    const ROWID = "rowid";

    const CANONICAL = MetadataDbStore::CANONICAL;

    const IS_HOME_COLUMN = "is_home";
    const IS_INDEX_COLUMN = "is_index";

    /**
     * @var MarkupPath
     */
    private $markupPath;
    /**
     * @var Sqlite|null
     */
    private $sqlite;

    /**
     * @var array
     */
    private $row;

    /**
     * Replicate constructor.
     * @throws ExceptionSqliteNotAvailable
     */
    public function __construct()
    {
        /**
         * Persist on the DB
         */
        $this->sqlite = Sqlite::createOrGetSqlite();


    }

    public static function getFromPageObject(MarkupPath $page): DatabasePageRow
    {
        $databasePage = new DatabasePageRow();
        try {
            $row = $databasePage->getDatabaseRowFromPage($page);
            $databasePage->setRow($row);
            return $databasePage;
        } catch (ExceptionNotExists $e) {
            //
        }
        return $databasePage;
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
     * @throws ExceptionCompile
     */
    public function replicate(): DatabasePageRow
    {

        if (!FileSystems::exists($this->markupPath)) {
            throw new ExceptionCompile("You can't replicate the non-existing page ($this->markupPath) on the file system");
        }


        /**
         * Page Replication should appears
         */
        $this->replicatePage();

        /**
         * @var Metadata $tabularMetadataToSync
         */
        $tabularMetadataToSync = [
            (new References()),
            (new Aliases())
        ];
        $fsStore = MetadataDokuWikiStore::getOrCreateFromResource($this->markupPath);
        $dbStore = MetadataDbStore::getOrCreateFromResource($this->markupPath);
        foreach ($tabularMetadataToSync as $tabular) {
            $tabular
                ->setResource($this->markupPath)
                ->setReadStore($fsStore)
                ->buildFromReadStore()
                ->setWriteStore($dbStore)
                ->persist();
        }

        /**
         * Analytics (derived)
         * Should appear at the end of the replication because it is based
         * on the previous replication (ie backlink count)
         */
        $this->replicateAnalytics();


        return $this;

    }

    /**
     * @throws ExceptionCompile
     */
    public function replicateAndRebuild(): DatabasePageRow
    {
        $this->replicate();
        $this->rebuild();
        return $this;
    }

    /**
     * @throws ExceptionNotExists - no page id to add
     */
    private function addPageIdMeta(array &$metaRecord)
    {
        try {
            $pageId = $this->markupPath->getPageId();
        } catch (ExceptionNotFound $e) {
            $pageId = PageId::generateAndStorePageId($this->markupPath);
        }
        $metaRecord[PageId::PROPERTY_NAME] = $pageId;
        $metaRecord[PageId::PAGE_ID_ABBR_ATTRIBUTE] = PageId::getAbbreviated($pageId);
    }

    public static function createFromPageId(string $pageId): DatabasePageRow
    {
        $databasePage = new DatabasePageRow();
        try {
            $row = $databasePage->getDatabaseRowFromPageId($pageId);
            $databasePage->setRow($row);
        } catch (ExceptionNotFound|ExceptionSqliteNotAvailable $e) {
            // not found
        }

        return $databasePage;
    }

    /**
     * @param MarkupPath $page
     * @return DatabasePageRow
     * @throws ExceptionSqliteNotAvailable - if there is no sqlite available
     * @noinspection PhpDocRedundantThrowsInspection
     */
    public static function getOrCreateFromPageObject(MarkupPath $page): DatabasePageRow
    {

        $databasePage = new DatabasePageRow();
        try {
            $row = $databasePage->getDatabaseRowFromPage($page);
            $databasePage->setRow($row);
            return $databasePage;
        } catch (ExceptionNotExists $e) {
            // page copied on the local system
            try {
                ComboFs::createIfNotExists($page);
                $row = $databasePage->getDatabaseRowFromPage($page);
                $databasePage->setRow($row);
                return $databasePage;
            } catch (ExceptionNotExists $e) {
                throw ExceptionRuntimeInternal::withMessageAndError("The row should exists as we created it specifically", $e);
            }
        }

    }


    /**
     *
     */
    public
    static function createFromPageIdAbbr(string $pageIdAbbr): DatabasePageRow
    {
        $databasePage = new DatabasePageRow();
        try {
            $row = $databasePage->getDatabaseRowFromAttribute(PageId::PAGE_ID_ABBR_ATTRIBUTE, $pageIdAbbr);
            $databasePage->setRow($row);
        } catch (ExceptionNotFound $e) {
            // ok
        }
        return $databasePage;

    }

    /**
     * @param $canonical
     * @return DatabasePageRow
     */
    public
    static function createFromCanonical($canonical): DatabasePageRow
    {

        WikiPath::addRootSeparatorIfNotPresent($canonical);
        $databasePage = new DatabasePageRow();
        try {
            $row = $databasePage->getDatabaseRowFromAttribute(Canonical::PROPERTY_NAME, $canonical);
            $databasePage->setRow($row);
        } catch (ExceptionNotFound $e) {
            // ok
        }
        return $databasePage;


    }

    public
    static function createFromAlias($alias): DatabasePageRow
    {

        WikiPath::addRootSeparatorIfNotPresent($alias);
        $databasePage = new DatabasePageRow();
        $row = $databasePage->getDatabaseRowFromAlias($alias);
        if ($row != null) {
            $databasePage->setRow($row);
            $page = $databasePage->getMarkupPath();
            if ($page !== null) {
                // page may be null in production
                // PHP Fatal error:  Uncaught Error: Call to a member function setBuildAliasPath() on null in
                // /opt/www/bytle/farmer.bytle.net/lib/plugins/combo/ComboStrap/DatabasePageRow.php:220
                $page->setBuildAliasPath($alias);
            }
        }
        return $databasePage;

    }

    /**
     * @throws ExceptionNotFound
     */
    public
    static function getFromDokuWikiId($id): DatabasePageRow
    {
        $databasePage = new DatabasePageRow();
        $row = $databasePage->getDatabaseRowFromDokuWikiId($id);
        $databasePage->setRow($row);
        return $databasePage;
    }

    public
    function getPageId()
    {
        return $this->getFromRow(PageId::PROPERTY_NAME);
    }


    public
    function shouldReplicate(): bool
    {


        $dateReplication = $this->getReplicationDate();
        if ($dateReplication === null) {
            return true;
        }

        /**
         * When the replication date is older than the actual document
         */
        try {
            $modifiedTime = FileSystems::getModifiedTime($this->markupPath->getPathObject());
            if ($modifiedTime > $dateReplication) {
                return true;
            }
        } catch (ExceptionNotFound $e) {
            return false;
        }


        $path = $this->markupPath->fetchAnalyticsPath();

        /**
         * When the file does not exist
         */
        $exist = FileSystems::exists($path);
        if (!$exist) {
            return true;
        }

        /**
         * When the analytics document is older
         */
        try {

            $modifiedTime = FileSystems::getModifiedTime($path);
            if ($modifiedTime > $dateReplication) {
                return true;
            }
        } catch (ExceptionNotFound $e) {
            //
        }


        /**
         * When the database version file is higher
         */
        $version = LocalPath::createFromPathString(__DIR__ . "/../db/latest.version");
        try {
            $versionModifiedTime = FileSystems::getModifiedTime($version);
        } catch (ExceptionNotFound $e) {
            return false;
        }
        if ($versionModifiedTime > $dateReplication) {
            return true;
        }

        /**
         * When the class date time is higher
         */
        $code = LocalPath::createFromPathString(__DIR__ . "/DatabasePageRow.php");
        try {
            $codeModified = FileSystems::getModifiedTime($code);
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntime("The database file does not exist");
        }
        if ($codeModified > $dateReplication) {
            return true;
        }

        return false;

    }

    public
    function delete()
    {

        $request = $this->sqlite
            ->createRequest()
            ->setQueryParametrized('delete from pages where id = ?', [$this->markupPath->getWikiId()]);
        try {
            $request->execute();
        } catch (ExceptionCompile $e) {
            LogUtility::msg("Something went wrong when deleting the page ({$this->markupPath}) from the database");
        } finally {
            $request->close();
        }
        $this->buildInitObjectFields();

    }

    /**
     * @return Json the analytics array or null if not in db
     */
    public
    function getAnalyticsData(): Json
    {

        $jsonString = $this->getFromRow(self::ANALYTICS_ATTRIBUTE);
        if ($jsonString === null) {
            // we put an empty json to not get any problem with the json database function
            // on an empty string / null (for sqlite)
            return Json::createEmpty();
        }
        try {
            return Json::createFromString($jsonString);
        } catch (ExceptionCompile $e) {
            throw ExceptionRuntimeInternal::withMessageAndError("Error while building back the analytics JSON object. {$e->getMessage()}", $e);
        }

    }

    /**
     * Return the database row
     *
     *
     * @throws ExceptionNotExists - if the row does not exists
     */
    public
    function getDatabaseRowFromPage(MarkupPath $markupPath): array
    {

        $this->setMarkupPath($markupPath);

        /**
         * Generated identifier
         */
        try {
            $pageId = $markupPath->getPageId();
            return $this->getDatabaseRowFromPageId($pageId);
        } catch (ExceptionNotFound $e) {
            // no page id
        }

        /**
         * Named identifier: path
         */
        try {
            $path = $markupPath->getPathObject();
            return $this->getDatabaseRowFromPath($path);
        } catch (ExceptionNotFound $e) {
            // not found
        }

        /**
         * Named identifier: id (ie path)
         */
        try {
            $id = $markupPath->getPathObject()->toWikiPath()->getWikiId();
            return $this->getDatabaseRowFromDokuWikiId($id);
        } catch (ExceptionCast|ExceptionNotFound $e) {
        }

        /**
         * Named identifier: canonical
         * (Note that canonical should become a soft link and therefore a path)
         */
        try {
            $canonical = Canonical::createForPage($markupPath)->getValue();
            return $this->getDatabaseRowFromCanonical($canonical->toAbsoluteId());
        } catch (ExceptionNotFound $e) {
            // no canonical
        }

        // we send a not exist
        throw new ExceptionNotExists("No row could be found");


    }


    /**
     * @return DateTime|null
     */
    public
    function getReplicationDate(): ?DateTime
    {
        $dateString = $this->getFromRow(ReplicationDate::getPersistentName());
        if ($dateString === null) {
            return null;
        }
        try {
            return Iso8601Date::createFromString($dateString)->getDateTime();
        } catch (ExceptionCompile $e) {
            LogUtility::msg("Error while reading the replication date in the database. {$e->getMessage()}");
            return null;
        }

    }

    /**
     *
     * @throws ExceptionBadState
     * @throws ExceptionSqliteNotAvailable
     */
    public
    function replicatePage(): void
    {

        if (!FileSystems::exists($this->markupPath)) {
            throw new ExceptionBadState("You can't replicate the page ($this->markupPath) because it does not exists.");
        }

        /**
         * Replication Date
         */
        $replicationDate = ReplicationDate::createFromPage($this->markupPath)
            ->setWriteStore(MetadataDbStore::class)
            ->setValue(new DateTime());

        /**
         * Same data as {@link MarkupPath::getMetadataForRendering()}
         */
        $record = $this->getMetaRecord();
        $record[$replicationDate::getPersistentName()] = $replicationDate->toStoreValue();
        $this->upsertAttributes($record);

    }


    /**
     *
     *
     * Attribute that are scalar / modifiable in the database
     * (not aliases or json data for instance)
     *
     * @throws ExceptionBadState
     * @throws ExceptionSqliteNotAvailable
     */
    public
    function replicateMetaAttributes(): void
    {

        $this->upsertAttributes($this->getMetaRecord());

    }

    /**
     * @throws ExceptionBadState - if the array is empty
     */
    public
    function upsertAttributes(array $attributes): void
    {

        if (empty($attributes)) {
            throw new ExceptionBadState("The page database attribute passed should not be empty");
        }

        $values = [];
        $columnClauses = [];
        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                throw new ExceptionRuntime("The attribute ($key) has value that is an array (" . implode(", ", $value) . ")");
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

            $updateStatement = "update PAGES SET " . implode(", ", $columnClauses) . " where ROWID = ?";
            $request = $this->sqlite
                ->createRequest()
                ->setQueryParametrized($updateStatement, $values);
            $countChanges = 0;
            try {
                $countChanges = $request
                    ->execute()
                    ->getChangeCount();
            } catch (ExceptionCompile $e) {
                throw new ExceptionBadState("There was a problem during the page attribute updates. : {$e->getMessage()}");
            } finally {
                $request->close();
            }
            if ($countChanges !== 1) {
                // internal error
                LogUtility::error("The database replication has not updated exactly 1 record but ($countChanges) record", \action_plugin_combo_indexer::CANONICAL);
            }

        } else {

            /**
             * Creation
             */
            if ($this->markupPath === null) {
                throw new ExceptionBadState("The page should be defined to create a page database row");
            }

            /**
             * If the user copy a frontmatter with the same page id abbr, we got a problem
             */
            $pageIdAbbr = $values[PageId::PAGE_ID_ABBR_ATTRIBUTE];
            if ($pageIdAbbr == null) {
                $pageId = $values[PageId::getPersistentName()];
                if ($pageId === null) {
                    throw new ExceptionBadState("You can't insert a page in the database without a page id");
                }
                $pageIdAbbr = PageId::getAbbreviated($pageId);
                $values[PageId::PAGE_ID_ABBR_ATTRIBUTE] = $pageIdAbbr;
            }

            $databasePage = DatabasePageRow::createFromPageIdAbbr($pageIdAbbr);
            if ($databasePage->exists()) {
                $duplicatePage = $databasePage->getMarkupPath();
                if ($duplicatePage->getPathObject()->toUriString() === $this->markupPath->toUriString()) {
                    $message = "The page ($this->markupPath) is already in the database with the uid ($pageIdAbbr).";
                } else {
                    $message = "The page ($this->markupPath) cannot be replicated to the database because it has the same page id abbreviation ($pageIdAbbr) than the page ($duplicatePage)";
                }
                throw new ExceptionBadState($message);
            }

            $values[DokuwikiId::DOKUWIKI_ID_ATTRIBUTE] = $this->markupPath->getPathObject()->getWikiId();
            $values[PagePath::PROPERTY_NAME] = $this->markupPath->getPathObject()->toAbsolutePath()->toAbsoluteId();
            /**
             * Default implements the auto-canonical feature
             */
            try {
                $values[Canonical::PROPERTY_NAME] = $this->markupPath->getCanonicalOrDefault();
            } catch (ExceptionNotFound $e) {
                $values[Canonical::PROPERTY_NAME] = null;
            }

            /**
             * Analytics
             */
            if (!isset($values[self::ANALYTICS_ATTRIBUTE])) {
                // otherwise we get an empty string
                // and a json function will not work
                $values[self::ANALYTICS_ATTRIBUTE] = Json::createEmpty()->toPrettyJsonString();
            }

            /**
             * Page Id / Abbr are mandatory for url redirection
             */
            $this->addPageIdAttributeIfNeeded($values);

            $request = $this->sqlite
                ->createRequest()
                ->setTableRow('PAGES', $values);
            try {
                /**
                 * rowid is used in {@link DatabasePageRow::exists()}
                 * to check if the page exists in the database
                 * We update it
                 */
                $this->row[self::ROWID] = $request
                    ->execute()
                    ->getInsertId();
                $this->row = array_merge($values, $this->row);
            } catch (ExceptionCompile $e) {
                throw new ExceptionBadState("There was a problem during the updateAttributes insert. : {$e->getMessage()}");
            } finally {
                $request->close();
            }

        }

    }

    public
    function getDescription()
    {
        return $this->getFromRow(PageDescription::DESCRIPTION_PROPERTY);
    }


    public
    function getPageName()
    {
        return $this->getFromRow(ResourceName::PROPERTY_NAME);
    }

    public
    function exists(): bool
    {
        return $this->getFromRow(self::ROWID) !== null;
    }

    /**
     * Called when a page is moved
     * @param $targetId
     */
    public
    function updatePathAndDokuwikiId($targetId)
    {
        if (!$this->exists()) {
            LogUtility::error("The `database` page ($this) does not exist and cannot be moved to ($targetId)");
        }

        $path = $targetId;
        WikiPath::addRootSeparatorIfNotPresent($path);
        $attributes = [
            DokuwikiId::DOKUWIKI_ID_ATTRIBUTE => $targetId,
            PagePath::PROPERTY_NAME => $path
        ];

        $this->upsertAttributes($attributes);

    }

    public
    function __toString()
    {
        return $this->markupPath->__toString();
    }


    /**
     * Redirect are now added during a move
     * Not when a duplicate is found.
     * With the advent of the page id, it should never occurs anymore
     * @param MarkupPath $page
     * @deprecated 2012-10-28
     */
    private
    function deleteIfExistsAndAddRedirectAlias(MarkupPath $page): void
    {

        if ($this->markupPath != null) {
            $page->getDatabasePage()->deleteIfExist();
            $this->addRedirectAliasWhileBuildingRow($page);
        }

    }

    public
    function getCanonical()
    {
        return $this->getFromRow(Canonical::PROPERTY_NAME);
    }

    /**
     * Set the field to their values
     * @param $row
     */
    public
    function setRow($row)
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


    }

    private
    function buildInitObjectFields()
    {
        $this->row = null;

    }

    public
    function rebuild(): DatabasePageRow
    {

        if ($this->markupPath != null) {
            $this->markupPath->rebuild();
            try {
                $row = $this->getDatabaseRowFromPage($this->markupPath);
                $this->setRow($row);
            } catch (ExceptionNotExists $e) {
                // ok
            }
        }
        return $this;

    }

    /**
     * @return array - an array of the fix page metadata (ie not derived)
     * Therefore quick to insert/update
     *
     */
    private
    function getMetaRecord(): array
    {
        $sourceStore = MetadataDokuWikiStore::getOrCreateFromResource($this->markupPath);
        $targetStore = MetadataDbStore::getOrCreateFromResource($this->markupPath);

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
            PageLevel::PROPERTY_NAME
        );
        $metaRecord = [];
        foreach ($record as $name) {
            try {
                $metadata = Meta\Api\MetadataSystem::getForName($name);
            } catch (ExceptionNotFound $e) {
                LogUtility::internalError("The metadata ($name) is unknown", self::CANONICAL);
                continue;
            }
            $metaRecord[$name] = $metadata
                ->setResource($this->markupPath)
                ->setReadStore($sourceStore)
                ->buildFromReadStore()
                ->setWriteStore($targetStore)
                ->toStoreValueOrDefault(); // used by the template, the value is or default

        }

        try {
            $this->addPageIdMeta($metaRecord);
        } catch (ExceptionNotExists $e) {
            // no page id for non-existent page ok
        }

        // Is index
        $metaRecord[self::IS_INDEX_COLUMN] = ($this->markupPath->isIndexPage() === true ? 1 : 0);

        return $metaRecord;
    }

    public
    function deleteIfExist(): DatabasePageRow
    {
        if ($this->exists()) {
            $this->delete();
        }
        return $this;
    }

    public
    function getRowId()
    {
        return $this->getFromRow(self::ROWID);
    }

    /**
     * @throws ExceptionNotFound
     */
    private
    function getDatabaseRowFromPageId(string $pageIdValue)
    {

        $pageIdAttribute = PageId::PROPERTY_NAME;
        $query = $this->getParametrizedLookupQuery($pageIdAttribute);
        $request = Sqlite::createOrGetSqlite()
            ->createRequest()
            ->setQueryParametrized($query, [$pageIdValue]);
        $rows = [];
        try {
            $rows = $request
                ->execute()
                ->getRows();
        } catch (ExceptionCompile $e) {
            throw new ExceptionRuntimeInternal("Error while retrieving the object by id", self::CANONICAL, 1, $e);
        } finally {
            $request->close();
        }

        switch (sizeof($rows)) {
            case 0:
                throw new ExceptionNotFound("No object by page id");
            case 1:
                /**
                 * Page Id Collision detection
                 */
                $rowId = $rows[0][DokuwikiId::DOKUWIKI_ID_ATTRIBUTE];
                $this->checkCollision($rowId, $pageIdAttribute, $pageIdValue);
                return $rows[0];
            default:
                $existingPages = implode(", ", $rows);
                $message = "The pages ($existingPages) have all the same page id ($pageIdValue)";
                throw new ExceptionRuntimeInternal($message, self::CANONICAL);
        }

    }


    private
    function getParametrizedLookupQuery(string $attributeName): string
    {
        $select = Sqlite::createSelectFromTableAndColumns("pages", self::PAGE_BUILD_ATTRIBUTES);
        return "$select where $attributeName = ?";
    }


    public
    function setMarkupPath(MarkupPath $page)
    {
        $this->markupPath = $page;
        return $this;
    }

    /**
     * @throws ExceptionNotFound
     */
    private
    function getDatabaseRowFromCanonical($canonicalValue)
    {
        $canoncialName = Canonical::PROPERTY_NAME;
        $query = $this->getParametrizedLookupQuery($canoncialName);
        $request = $this->sqlite
            ->createRequest()
            ->setQueryParametrized($query, [$canonicalValue]);
        $rows = [];
        try {
            $rows = $request
                ->execute()
                ->getRows();
        } catch (ExceptionCompile $e) {
            throw new ExceptionRuntime("An exception has occurred with the page search from CANONICAL. " . $e->getMessage());
        } finally {
            $request->close();
        }

        switch (sizeof($rows)) {
            case 0:
                throw new ExceptionNotFound("No canonical row was found");
            case 1:
                $id = $rows[0][DokuwikiId::DOKUWIKI_ID_ATTRIBUTE];
                $this->checkCollision($id, $canoncialName, $canonicalValue);
                return $rows[0];
            default:
                $existingPages = [];
                foreach ($rows as $row) {
                    $id = $row[DokuwikiId::DOKUWIKI_ID_ATTRIBUTE];
                    $duplicatePage = MarkupPath::createMarkupFromId($id);
                    if (!$duplicatePage->exists()) {

                        $this->deleteIfExistsAndAddRedirectAlias($duplicatePage);

                    } else {

                        /**
                         * Check if the error may come from the auto-canonical
                         * (Never ever save generated data)
                         */
                        $canonicalLastNamesCount = SiteConfig::getConfValue(Canonical::CONF_CANONICAL_LAST_NAMES_COUNT, 0);
                        if ($canonicalLastNamesCount > 0) {
                            $this->markupPath->unsetMetadata($canoncialName);
                            $duplicatePage->unsetMetadata($canoncialName);
                        }

                        $existingPages[] = $row;
                    }
                }
                if (sizeof($existingPages) > 1) {
                    $existingPages = implode(", ", $existingPages);
                    $message = "The existing pages ($existingPages) have all the same canonical ($canonicalValue), return the first one";
                    LogUtility::error($message, self::CANONICAL);
                }
                return $existingPages[0];
        }
    }

    /**
     * @throws ExceptionNotFound
     */
    private
    function getDatabaseRowFromPath(string $path): ?array
    {
        WikiPath::addRootSeparatorIfNotPresent($path);
        return $this->getDatabaseRowFromAttribute(PagePath::PROPERTY_NAME, $path);
    }

    /**
     * @throws ExceptionNotFound
     */
    private
    function getDatabaseRowFromDokuWikiId(string $id): array
    {
        return $this->getDatabaseRowFromAttribute(DokuwikiId::DOKUWIKI_ID_ATTRIBUTE, $id);
    }

    /**
     * @throws ExceptionNotFound
     */
    public
    function getDatabaseRowFromAttribute(string $attribute, string $value)
    {
        $query = $this->getParametrizedLookupQuery($attribute);
        $request = $this->sqlite
            ->createRequest()
            ->setQueryParametrized($query, [$value]);
        $rows = [];
        try {
            $rows = $request
                ->execute()
                ->getRows();
        } catch (ExceptionCompile $e) {
            $message = "Internal Error: An exception has occurred with the page search from a PATH: " . $e->getMessage();
            LogUtility::log2file($message);
            throw new ExceptionNotFound($message);
        } finally {
            $request->close();
        }

        switch (sizeof($rows)) {
            case 0:
                throw new ExceptionNotFound("No database row found for the page");
            case 1:
                $value = $rows[0][DokuwikiId::DOKUWIKI_ID_ATTRIBUTE];
                if ($this->markupPath != null && $value !== $this->markupPath->getWikiId()) {
                    $duplicatePage = MarkupPath::createMarkupFromId($value);
                    if (!$duplicatePage->exists()) {
                        $this->addRedirectAliasWhileBuildingRow($duplicatePage);
                    } else {
                        LogUtility::msg("The page ($this->markupPath) and the page ($value) have the same $attribute ($value)", LogUtility::LVL_MSG_ERROR);
                    }
                }
                return $rows[0];
            default:
                $existingPages = [];
                foreach ($rows as $row) {
                    $value = $row[DokuwikiId::DOKUWIKI_ID_ATTRIBUTE];
                    $duplicatePage = MarkupPath::createMarkupFromId($value);
                    if (!$duplicatePage->exists()) {

                        $this->deleteIfExistsAndAddRedirectAlias($duplicatePage);

                    } else {
                        $existingPages[] = $row;
                    }
                }
                if (sizeof($existingPages) === 1) {
                    return $existingPages[0];
                } else {
                    $existingPageIds = array_map(
                        function ($row) {
                            return $row[DokuwikiId::DOKUWIKI_ID_ATTRIBUTE];
                        },
                        $existingPages);
                    $existingPages = implode(", ", $existingPageIds);
                    throw new ExceptionNotFound("The existing pages ($existingPages) have all the same attribute $attribute with the value ($value)", LogUtility::LVL_MSG_ERROR);
                }
        }
    }

    public
    function getMarkupPath(): ?MarkupPath
    {
        if (
            $this->markupPath === null
            && $this->row[DokuwikiId::DOKUWIKI_ID_ATTRIBUTE] !== null
        ) {
            $this->markupPath = MarkupPath::createMarkupFromId($this->row[DokuwikiId::DOKUWIKI_ID_ATTRIBUTE]);
        }
        return $this->markupPath;
    }

    private
    function getDatabaseRowFromAlias($alias): ?array
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
        /** @noinspection SqlResolve */
        $query = "select {$fields} from PAGES p, PAGE_ALIASES pa where p.{$pageIdAttribute} = pa.{$pageIdAttribute} and pa.PATH = ? ";
        $request = $this->sqlite
            ->createRequest()
            ->setQueryParametrized($query, [$alias]);
        $rows = [];
        try {
            $rows = $request
                ->execute()
                ->getRows();
        } catch (ExceptionCompile $e) {
            LogUtility::msg("An exception has occurred with the alias selection query. {$e->getMessage()}");
            return null;
        } finally {
            $request->close();
        }
        switch (sizeof($rows)) {
            case 0:
                return null;
            case 1:
                return $rows[0];
            default:
                $id = $rows[0]['ID'];
                $pages = implode(",",
                    array_map(
                        function ($row) {
                            return $row['ID'];
                        },
                        $rows
                    )
                );
                LogUtility::msg("For the alias $alias, there is more than one page defined ($pages), the first one ($id) was used", LogUtility::LVL_MSG_ERROR, Aliases::PROPERTY_NAME);
                return $rows[0];
        }
    }


    /**
     * Utility function
     * @param MarkupPath $pageAlias
     */
    private
    function addRedirectAliasWhileBuildingRow(MarkupPath $pageAlias)
    {

        $aliasPath = $pageAlias->getPathObject()->toAbsoluteId();
        try {
            Aliases::createForPage($this->markupPath)
                ->addAlias($aliasPath)
                ->sendToWriteStore();
        } catch (ExceptionCompile $e) {
            // we don't throw while getting
            LogUtility::msg("Unable to add the alias ($aliasPath) for the page ($this->markupPath)");
        }

    }

    private
    function addPageIdAttributeIfNeeded(array &$values)
    {
        if (!isset($values[PageId::getPersistentName()])) {
            $values[PageId::getPersistentName()] = $this->markupPath->getPageId();
        }
        if (!isset($values[PageId::PAGE_ID_ABBR_ATTRIBUTE])) {
            $values[PageId::PAGE_ID_ABBR_ATTRIBUTE] = $this->markupPath->getPageIdAbbr();
        }
    }

    public
    function getFromRow(string $attribute)
    {
        if ($this->row === null) {
            return null;
        }

        if (!array_key_exists($attribute, $this->row)) {
            /**
             * An attribute should be added to {@link DatabasePageRow::PAGE_BUILD_ATTRIBUTES}
             * or in the table
             */
            throw new ExceptionRuntime("The metadata ($attribute) was not found in the returned database row.", $this->getCanonical());
        }

        $value = $this->row[$attribute];

        if ($value !== null) {
            return $value;
        }

        // don't know why but the sqlite plugin returns them uppercase
        // rowid is returned lowercase from the sqlite plugin
        $upperAttribute = strtoupper($attribute);
        return $this->row[$upperAttribute];

    }


    /**
     * @throws ExceptionCompile
     */
    public
    function replicateAnalytics()
    {

        try {
            $fetchPath = $this->markupPath->fetchAnalyticsPath();
            $analyticsJson = Json::createFromPath($fetchPath);
        } catch (ExceptionCompile $e) {
            if (PluginUtility::isDevOrTest()) {
                throw $e;
            }
            throw new ExceptionCompile("Unable to get the analytics document", self::CANONICAL, 0, $e);
        }

        /**
         * Replication Date
         */
        $replicationDateMeta = ReplicationDate::createFromPage($this->markupPath)
            ->setWriteStore(MetadataDbStore::class)
            ->setValue(new DateTime());

        /**
         * Analytics
         */
        $analyticsJsonAsString = $analyticsJson->toPrettyJsonString();
        $analyticsJsonAsArray = $analyticsJson->toArray();

        /**
         * Record
         */
        $record[self::ANALYTICS_ATTRIBUTE] = $analyticsJsonAsString;
        $record['IS_LOW_QUALITY'] = ($this->markupPath->isLowQualityPage() === true ? 1 : 0);
        $record['WORD_COUNT'] = $analyticsJsonAsArray[renderer_plugin_combo_analytics::STATISTICS][renderer_plugin_combo_analytics::WORD_COUNT];
        $record[BacklinkCount::getPersistentName()] = $analyticsJsonAsArray[renderer_plugin_combo_analytics::STATISTICS][BacklinkCount::getPersistentName()];
        $record[$replicationDateMeta::getPersistentName()] = $replicationDateMeta->toStoreValue();
        $this->upsertAttributes($record);
    }

    private
    function checkCollision($wikiIdInDatabase, $attribute, $value)
    {
        if ($this->markupPath === null) {
            return;
        }
        try {
            $markupWikiId = $this->markupPath->toWikiPath()->getWikiId();
        } catch (ExceptionCast $e) {
            return;
        }
        if ($wikiIdInDatabase !== $markupWikiId) {
            $duplicatePage = MarkupPath::createMarkupFromId($wikiIdInDatabase);
            if (!FileSystems::exists($duplicatePage)) {
                // Move
                LogUtility::info("The non-existing duplicate page ($wikiIdInDatabase) has been added as redirect alias for the page ($this->markupPath)", self::CANONICAL);
                $this->addRedirectAliasWhileBuildingRow($duplicatePage);
            } else {
                // This can happens if two page were created not on the same website
                // of if the sqlite database was deleted and rebuilt.
                // The chance is really, really low
                $errorMessage = "The page ($this->markupPath) and the page ($wikiIdInDatabase) have the same $attribute value ($value)";
                throw new ExceptionRuntimeInternal($errorMessage, self::CANONICAL);
                // What to do ?
                // The database does not allow two page id with the same value
                // If it happens, ugh, ugh, ..., a replication process between website may be.
            }
        }
    }


}
