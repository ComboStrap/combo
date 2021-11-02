<?php


namespace ComboStrap;

use Exception;
use http\Exception\RuntimeException;

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
     * The list of attributes that are set
     * at build time
     * used in the build function {@link DatabasePage::buildDatabaseObject()}
     * to build the sql
     */
    private const BUILD_ATTRIBUTES =
        [
            self::ROWID,
            "ID",
            self::ANALYTICS_ATTRIBUTE,
            Analytics::DESCRIPTION,
            Analytics::CANONICAL,
            Analytics::NAME,
            Analytics::TITLE,
            Analytics::H1,
            Publication::DATE_PUBLISHED,
            Analytics::DATE_START,
            Analytics::DATE_END,
            Page::REGION_META_PROPERTY,
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
    private $rowId;
    /**
     * @var mixed
     */
    private $description;
    /**
     * @var mixed
     */
    private $canonical;
    private $json;
    private $pageName;

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

        $this->buildDatabaseObject();

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

        $res = $this->replicateBacklinkPages();
        if ($res === false) {
            return false;
        }

        $res = $this->replicateAliases();
        if ($res === false) {
            return false;
        }

        /**
         * Set the replication date
         */
        $this->page->setMetadata(self::DATE_REPLICATION, $replicationDate);
        return true;

    }

    /**
     * For, there is no real replication between website.
     *
     * Therefore, the source of truth is the value in the {@link syntax_plugin_combo_frontmatter}
     * Therefore, the page id generation should happen after the rendering of the page
     * at the database level
     *
     * Return a page id collision free
     * for the page already {@link DatabasePage::replicatePage() replicated}
     *
     * https://zelark.github.io/nano-id-cc/
     *
     * 1000 id / hour = ~35 years needed, in order to have a 1% probability of at least one collision.
     *
     * We don't rely on a sequence because
     *    - the database may be refreshed
     *    - sqlite does have only auto-increment support
     * https://www.sqlite.org/autoinc.html
     *
     * @return string
     */
    public static function generateUniquePageId(): string
    {
        /**
         * Collision detection happens just after the use of this function on the
         * creation of the {@link DatabasePage::buildDatabaseObject() databasePage object}
         *
         */
        $nanoIdClient = new \Hidehalo\Nanoid\Client();
        $pageId = ($nanoIdClient)->formattedId(Page::PAGE_ID_ALPHABET, Page::PAGE_ID_LENGTH);
        while (Page::getPageFromPageId($pageId) != null) {
            $pageId = ($nanoIdClient)->formattedId(Page::PAGE_ID_ALPHABET, Page::PAGE_ID_LENGTH);
        }
        return $pageId;
    }

    private function addPageIdMeta(array &$metaRecord)
    {
        $metaRecord[Page::PAGE_ID_ATTRIBUTE] = $this->page->getPageId();
        $metaRecord[Page::PAGE_ID_ABBR_ATTRIBUTE] = $this->page->getPageIdAbbr();
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

        $res = Sqlite::getSqlite()->query('delete from pages where id = ?', $this->page->getDokuwikiId());
        if (!$res) {
            LogUtility::msg("Something went wrong when deleting the page ({$this->page})");
        }
        $this->rowId = null;
        $this->description = null;
        $this->pageName = null;
        $this->json = null;
        $this->canonical = null;

    }

    /**
     * @return Json|null the analytics array or null if not in db
     */
    public
    function getAnalyticsData(): ?Json
    {

        return $this->json;

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
     */
    private
    function buildDatabaseObject(): void
    {

        if ($this->sqlite === null) return;

        $databaseFields = implode(self::BUILD_ATTRIBUTES, ", ");
        $page = $this->page;
        // Do we have a page attached to this page id
        $pageId = $page->getPageId();
        $pageIdAttribute = Page::PAGE_ID_ATTRIBUTE;

        if($pageId!=null) {
            $res = $this->sqlite->query("select $databaseFields from pages where $pageIdAttribute = ?", $pageId);
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
                    if ($id !== $page->getDokuwikiId()) {
                        $duplicatePage = Page::createPageFromId($id);
                        if (!$duplicatePage->exists()) {
                            // Move
                            LogUtility::msg("The non-existing duplicate page ($id) has been added as redirect alias for the page ($page)", LogUtility::LVL_MSG_INFO);
                            $this->deleteIfExistsAndAddDuplicateAsRedirect($duplicatePage);
                        } else {
                            // This can happens if two page were created not on the same website
                            // of if the sqlite database was deleted and rebuilt.
                            // The chance is really, really low
                            $errorMessage = "The page ($page) and the page ($id) have the same page id ($pageId)";
                            LogUtility::msg($errorMessage, LogUtility::LVL_MSG_ERROR);
                            // What to do ?
                            // We just throw an error for now
                            // The database does not allow two page id with the same value
                            // If it happens, ugh, ugh, ..., a replication process between website may be.
                            throw new RuntimeException($errorMessage);
                        }
                    }
                    $this->buildDatabaseObjectFields($rows[0]);
                    return;
                default:
                    $existingPages = implode(", ", $rows);
                    LogUtility::msg("The pages ($existingPages) have all the same page id ($pageId)", LogUtility::LVL_MSG_ERROR);
            }
        }

        // Do we have a page attached to the canonical
        $canonical = $page->getCanonical();
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
                    if ($id !== $page->getDokuwikiId()) {
                        $duplicatePage = Page::createPageFromId($id);
                        if (!$duplicatePage->exists()) {
                            $this->deleteIfExistsAndAddDuplicateAsRedirect($duplicatePage);
                            LogUtility::msg("The non-existing duplicate page ($id) has been added as redirect alias for the page ($page)", LogUtility::LVL_MSG_INFO);
                        } else {
                            LogUtility::msg("The page ($page) and the page ($id) have the same canonical ($canonical)", LogUtility::LVL_MSG_ERROR);
                        }
                    }
                    return;
                default:
                    $existingPages = [];
                    foreach ($rows as $row) {
                        $id = $row["ID"];
                        $duplicatePage = Page::createPageFromId($id);
                        if (!$duplicatePage->exists()) {

                            $this->deleteIfExistsAndAddDuplicateAsRedirect($duplicatePage);

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
                        return;
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
                if ($id !== $page->getDokuwikiId()) {
                    LogUtility::msg("The page ($page) and the page ($id) have the same path ($path)", LogUtility::LVL_MSG_ERROR);
                }
                return;
            default:
                $existingPages = [];
                foreach ($rows as $row) {
                    $id = $row["ID"];
                    $duplicatePage = Page::createPageFromId($id);
                    if (!$duplicatePage->exists()) {

                        $this->deleteIfExistsAndAddDuplicateAsRedirect($duplicatePage);

                    } else {
                        $existingPages[] = $row;
                    }
                }
                if (sizeof($existingPages) === 1) {
                    return;
                } else {
                    $existingPages = implode(", ", $existingPages);
                    LogUtility::msg("The existing pages ($existingPages) have all the same path ($path)", LogUtility::LVL_MSG_ERROR);
                }

        }

        /**
         * Do we have a page attached to this ID
         * @deprecated for the page id
         * Last resort
         */
        $id = $page->getDokuwikiId();
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
                return;
            default:
                LogUtility::msg("The database has " . sizeof($rows) . " records with the same id ($id)", LogUtility::LVL_MSG_ERROR);
                break;

        }

        /**
         * No rows found
         */

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
        $analyticsJson = $this->page->getAnalytics()->getData();
        $analyticsJsonAsString = $analyticsJson->toString();
        $analyticsJsonAsArray = $analyticsJson->toArray();
        /**
         * Same data as {@link Page::getMetadataForRendering()}
         */
        $record = $this->getMetaRecord();
        $record[self::ANALYTICS_ATTRIBUTE] = $analyticsJsonAsString;
        $record['IS_LOW_QUALITY'] = ($page->isLowQualityPage() === true ? 1 : 0);
        $record['WORD_COUNT'] = $analyticsJsonAsArray[Analytics::WORD_COUNT];
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
        $res = $this->sqlite->query("select count(1) from PAGE_REFERENCES where TARGET_ID = ? ", $this->page->getDokuwikiId());
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
     * @param array $attributes
     * @return bool when an update as occurred
     *
     * Attribute that are scalar / modifiable in the database
     * (not aliases or json data for instance)
     *
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
        $rowId = $this->rowId;
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

            /**
             * Page Id
             */
            $pageId = $this->page->getPageId();
            if ($pageId === null || !is_string($pageId)
                || preg_match("/[-_A-Z]/", $pageId)
            ) {
                $pageId = self::generateUniquePageId();
                $this->page->setPageId($pageId);
            }

            $values[PAGE::DOKUWIKI_ID_ATTRIBUTE] = $this->page->getDokuwikiId();
            $values[Analytics::PATH] = $this->page->getPath();
            $values[Page::PAGE_ID_ATTRIBUTE] = $this->page->getPageId();
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
            } else {
                /**
                 * rowid is used in {@link DatabasePage::exists()}
                 * to check if the page exists in the database
                 * We update it
                 */
                $this->rowId = $this->sqlite->getAdapter()->getDb()->lastInsertId();
            }
        }
        return true;

    }

    public function getDescription()
    {
        return $this->description;
    }


    public function getPageName()
    {
        return $this->pageName;
    }

    public function exists(): bool
    {
        return $this->rowId !== null;
    }

    public function moveTo($targetId)
    {
        if (!$this->exists()) {
            LogUtility::msg("The `database` page ($this) does not exist and cannot be moved to ($targetId)", LogUtility::LVL_MSG_ERROR);
        }
        $pageId = $this->page->getPageId();
        $attributes = [
            Page::DOKUWIKI_ID_ATTRIBUTE => $targetId,
            Page::PATH_ATTRIBUTE => ":${$targetId}",
            Page::PAGE_ID_ATTRIBUTE => $pageId
        ];

        $this->upsertAttributes($attributes);
        /**
         * The page id is created on page creation
         * We need to update it on the target page
         */
        if ($pageId === null) {
            LogUtility::msg("During a move, the uuid of the page ($this) to ($targetId) was null. It should not be the case as this page exists. The UUID was not passed over to the target page.", LogUtility::LVL_MSG_ERROR, self::REPLICATION_CANONICAL);
            return;
        }
        $targetPage = Page::createPageFromId($targetId);
        $targetPage->setPageId($pageId);

    }

    public function __toString()
    {
        return $this->page->__toString();
    }


    /**
     * @param Alias $alias
     * @return $this
     */
    public function addAlias(Alias $alias): DatabasePage
    {

        $row = array(
            Page::PAGE_ID_ATTRIBUTE => $this->page->getPageId(),
            Alias::ALIAS_PATH_PROPERTY => $alias->getPath(),
            Alias::ALIAS_TYPE_PROPERTY => $alias->getType()
        );

        // Page has change of location
        // Creation of an alias
        $sqlite = Sqlite::getSqlite();
        $res = $sqlite->storeEntry('PAGE_ALIASES', $row);
        if (!$res) {
            LogUtility::msg("There was a problem during PAGE_ALIASES insertion");
        }
        $sqlite->res_close($res);

        return $this;
    }

    private function replicateAliases(): bool
    {

        $fileSystemAliases = $this->page->getAliases();
        $dbAliases = $this->getAliases();
        foreach ($fileSystemAliases as $fileSystemAlias) {

            if (key_exists($fileSystemAlias->getPath(), $dbAliases)) {
                unset($dbAliases[$fileSystemAlias->getPath()]);
            } else {
                $this->addAlias($fileSystemAlias);
            }
        }

        if (sizeof($dbAliases) > 0) {

            foreach ($dbAliases as $dbAlias) {
                $this->deleteAlias($dbAlias);
            }
        }

        return true;
    }

    /**
     * @return Alias[]
     */
    public function getAliases(): array
    {
        if ($this->sqlite === null) {
            return [];
        }
        $pageIdAttribute = Page::PAGE_ID_ATTRIBUTE;
        $res = $this->sqlite->query("select PATH, TYPE from PAGE_ALIASES where $pageIdAttribute = ? ", $this->page->getPageId());
        if (!$res) {
            LogUtility::msg("An exception has occurred with the PAGE_ALIASES ({$this->page}) selection query");
        }
        $rowAliases = $this->sqlite->res2arr($res);
        $this->sqlite->res_close($res);
        $dbAliases = [];
        foreach ($rowAliases as $row) {
            $dbAliases[$row['PATH']] = Alias::create($this->page, $row['PATH'])
                ->setType($row["TYPE"]);
        }
        return $dbAliases;
    }

    /**
     * @param Alias $dbAliasPath
     * @return $this
     */
    public function deleteAlias(Alias $dbAliasPath): DatabasePage
    {
        $delete = <<<EOF
delete from PAGE_ALIASES where UUID = ? and PATH = ?
EOF;
        $row = [
            "UUID" => $this->page->getPageId(),
            "PATH" => $dbAliasPath->getPath()
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
            LogUtility::msg("There was a problem during the alias delete. $message. : {$errorInfoAsString}");
        }
        return $this;

    }

    /**
     * Redirect are now added during a move
     * Not when a duplicate is found.
     * With the advent of the page id, it should never occurs anymore
     * @param Page $page
     * @deprecated 2012-10-28
     */
    private function deleteIfExistsAndAddDuplicateAsRedirect(Page $page): void
    {
        $page->getDatabasePage()->deleteIfExist();
        $alias = $this->page->addAndGetAlias($page->getDokuwikiId(), Alias::REDIRECT);
        $this->addAlias($alias);
    }

    public function getCanonical()
    {
        return $this->canonical;
    }

    /**
     * Set the field to their values
     * @param $row
     */
    private function buildDatabaseObjectFields($row)
    {
        foreach ($row as $key => $value) {
            $key = strtolower($key);
            switch ($key) {
                case self::ROWID:
                    $this->rowId = $value;
                    continue 2;
                case PAGE::DESCRIPTION_PROPERTY:
                    $this->description = $value;
                    continue 2;
                case PAGE::CANONICAL_PROPERTY:
                    $this->canonical = $value;
                    continue 2;
                case self::ANALYTICS_ATTRIBUTE:
                    $this->json = Json::createFromString($value);
                    continue 2;
                case Page::NAME_PROPERTY:
                    $this->pageName = $value;
            }
        }

    }

    public function refresh(): DatabasePage
    {

        $this->page->refresh();
        $this->buildDatabaseObject();
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
            Analytics::CANONICAL => $this->page->getCanonicalOrDefault(),
            'PATH' => $this->page->getAbsolutePath(),
            Analytics::NAME => $this->page->getPageNameNotEmpty(),
            Analytics::TITLE => $this->page->getTitleNotEmpty(),
            Analytics::H1 => $this->page->getH1NotEmpty(),
            Analytics::DATE_CREATED => $this->page->getCreatedDateAsString(),
            Analytics::DATE_MODIFIED => $this->page->getModifiedDateAsString(),
            Publication::DATE_PUBLISHED => $this->page->getPublishedTimeAsString(),
            Analytics::DATE_START => $this->page->getEndDateAsString(),
            Analytics::DATE_END => $this->page->getStartDateAsString(),
            Page::REGION_META_PROPERTY => $this->page->getRegionOrDefault(),
            Page::LANG_META_PROPERTY => $this->page->getLangOrDefault(),
            Page::TYPE_META_PROPERTY => $this->page->getTypeNotEmpty(),
            'ID' => $this->page->getDokuwikiId(),
        );

        if ($this->page->getPageId() != null) {
            $this->addPageIdMeta($metaRecord);
        };
        return $metaRecord;
    }

    public function deleteIfExist()
    {
        if ($this->exists()) {
            $this->delete();
        }
    }

    public function getRowId()
    {
        return $this->rowId;
    }


}
