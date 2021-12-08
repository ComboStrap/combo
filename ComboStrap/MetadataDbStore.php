<?php


namespace ComboStrap;

/**
 * Class MetadataDbStore
 * @package ComboStrap
 * The database store
 */
class MetadataDbStore implements MetadataStore
{

    const ALIAS_TABLE_NAME = "PAGE_ALIASES";

    const CANONICAL = "database";

    private static $metaBdStore;

    /**
     * @var \helper_plugin_sqlite|null
     */
    private $sqlite;

    /**
     * MetadataDbStore constructor.
     */
    public function __construct()
    {
        $this->sqlite = Sqlite::getSqlite();
    }


    public static function getOrCreate(): MetadataDbStore
    {
        if (self::$metaBdStore === null) {
            self::$metaBdStore = new MetadataDbStore();
        }
        return self::$metaBdStore;

    }

    public function set(Metadata $metadata)
    {
        switch ($metadata->getName()) {
            case Aliases::ALIAS_ATTRIBUTE:
                $this->setAliases($metadata);
                return;
            default:
                throw new ExceptionComboRuntime("The metadata ($metadata) is not yet supported on set", self::CANONICAL);
        }
    }

    public function get(Metadata $metadata, $default = null)
    {
        $resource = $metadata->getResource();
        if (!($resource instanceof Page)) {
            throw new ExceptionComboRuntime("The resource type ({$resource->getPageType()}) is not yet supported for the database metadata store", self::CANONICAL);
        }

        $database = DatabasePage::createFromPageObject($resource);
        if(!$database->exists()){
            return null;
        }

        switch ($metadata->getName()) {

            case Aliases::ALIAS_ATTRIBUTE:
                return $this->getAliasesInPersistentValue($metadata);
            default:
                $value = $database->getFromRow($metadata->getName());
                if($value===null){
                    /**
                     * An attribute should be added to {@link DatabasePage::PAGE_BUILD_ATTRIBUTES}
                     * or in the table
                     */
                    throw new ExceptionComboRuntime("The metadata ($metadata) was not found in the returned database row.",self::CANONICAL);
                }
                return $value;

        }
    }

    private function setAliases(Metadata $metadata)
    {

        $aliasesToStore = $metadata->toStoreValue();
        $dbAliases = $this->getAliasesInPersistentValue($metadata);
        $dbAliasMap = [];
        if ($dbAliases !== null) {
            foreach ($dbAliases as $dbAlias) {
                $dbAliasMap[$dbAlias[Alias::ALIAS_PATH_PROPERTY]] = $dbAlias;
            }
        }
        foreach ($aliasesToStore as $aliasToStore) {

            if (isset($dbAliasMap[$aliasToStore[Alias::ALIAS_PATH_PROPERTY]])) {
                unset($dbAliasMap[$aliasToStore[Alias::ALIAS_PATH_PROPERTY]]);
            } else {
                $this->addAlias($aliasToStore, $metadata->getResource());
            }

        }

        foreach ($dbAliasMap as $dbAlias) {
            $this->deleteAlias($dbAlias, $metadata->getResource());
        }

    }


    /**
     * @param array $alias
     * @param Page $page
     * @return void
     */
    private function addAlias(array $alias, ResourceCombo $page): void
    {

        $row = array(
            PageId::PAGE_ID_ATTRIBUTE => $page->getPageId(),
            Alias::ALIAS_PATH_PROPERTY => $alias[Alias::ALIAS_PATH_PROPERTY],
            Alias::ALIAS_TYPE_PROPERTY => $alias[Alias::ALIAS_TYPE_PROPERTY]
        );

        // Page has change of location
        // Creation of an alias

        $res = $this->sqlite->storeEntry(self::ALIAS_TABLE_NAME, $row);
        if (!$res) {
            LogUtility::msg("There was a problem during PAGE_ALIASES insertion");
        }
        $this->sqlite->res_close($res);

    }

    /**
     * @param array $dbAliasPath
     * @param $page
     * @return void
     */
    private function deleteAlias(array $dbAliasPath, $page): void
    {
        $pageIdAttributes = PageId::PAGE_ID_ATTRIBUTE;
        $pathAttribute = PagePath::PATH_ATTRIBUTE;
        $aliasTables = self::ALIAS_TABLE_NAME;
        $delete = <<<EOF
delete from $aliasTables where $pageIdAttributes = ? and $pathAttribute = ?
EOF;

        $row = [
            $pageIdAttributes => $page->getPageId(),
            $pathAttribute => $dbAliasPath[Alias::ALIAS_PATH_PROPERTY]
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

    }


    /**
     * @return null
     * @var Metadata $metadata
     */
    private function getAliasesInPersistentValue(Metadata $metadata)
    {

        if ($this->sqlite === null) {
            return null;
        }
        if ($metadata->getResource() === null) {
            LogUtility::msg("The page resource is unknown. We can't retrieve the aliases");
            return null;
        }

        if ($metadata->getResource()->getUid()->getValue() === null) {
            LogUtility::msg("The page id is null. We can't retrieve the aliases");
            return null;
        }
        $aliases = Aliases::create()
            ->setResource($metadata->getResource());
        $pageIdAttribute = strtoupper(PageId::PAGE_ID_ATTRIBUTE);
        $pathAttribute = strtoupper(Alias::ALIAS_PATH_PROPERTY);
        $typeAttribute = strtoupper(Alias::ALIAS_TYPE_PROPERTY);
        $tableAliases = self::ALIAS_TABLE_NAME;
        $pageId = $metadata->getResource()->getUid()->getValue();
        $res = $this->sqlite->query("select $pathAttribute, $typeAttribute from $tableAliases where $pageIdAttribute = ? ", $pageId);
        if (!$res) {
            LogUtility::msg("An exception has occurred with the PAGE_ALIASES ({$metadata->getResource()}) selection query");
        }
        $rowAliases = $this->sqlite->res2arr($res);
        $this->sqlite->res_close($res);
        foreach ($rowAliases as $row) {
            $aliases->addAlias($row[$pathAttribute], $row[$typeAttribute]);
        }
        return $aliases->toStoreValue();

    }

    public function persist()
    {
        // there is no notion of commit in the sqlite plugin
    }

    public function isTextBased(): bool
    {
        return false;
    }

    public function reset()
    {
        throw new ExceptionComboRuntime("To implement");
    }
}
