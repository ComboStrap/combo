<?php


namespace ComboStrap;


/**
 * Class MetadataDbStore
 * @package ComboStrap
 * The database store
 */
class MetadataDbStore extends MetadataStoreAbs
{


    const CANONICAL = "database";
    private $resource;

    /**
     * MetadataDbStore constructor.
     */
    public function __construct($resourceCombo)
    {
        $this->resource = $resourceCombo;
    }


    public static function createForPage(ResourceCombo $resourceCombo): MetadataDbStore
    {

        return new MetadataDbStore($resourceCombo);

    }

    public function set(Metadata $metadata)
    {
        switch ($metadata->getName()) {
            case Aliases::PROPERTY_NAME:
                $this->syncTabular($metadata);
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


        switch ($metadata->getName()) {
            case Aliases::PROPERTY_NAME:
                return $this->getDbTabularData($metadata);
            default:
                $pageMetaFromFileSystem = Page::createPageFromQualifiedPath($resource->getPath()->toString());
                $fsStore = MetadataDokuWikiStore::createForPage($pageMetaFromFileSystem);
                $pageMetaFromFileSystem->setReadStore($fsStore);

                $database = DatabasePage::createFromPageObject($pageMetaFromFileSystem);
                if (!$database->exists()) {
                    return null;
                }
                $value = $database->getFromRow($metadata->getName());
                if ($value === null) {
                    /**
                     * An attribute should be added to {@link DatabasePage::PAGE_BUILD_ATTRIBUTES}
                     * or in the table
                     */
                    throw new ExceptionComboRuntime("The metadata ($metadata) was not found in the returned database row.", self::CANONICAL);
                }
                return $value;

        }
    }

    private function syncTabular(MetadataTabular $metadata)
    {

        $uid = Metadata::toChildMetadataObject($metadata->getUidClass(), $metadata->getResource());
        $sourceRows = $metadata->getValue();
        if ($sourceRows === null) {
            return;
        }

        $targetRows = $this->getDbTabularData($metadata);
        foreach ($targetRows as $targetRow) {

            $targetRowId = $targetRow[$uid::getPersistentName()];
            if (isset($sourceRows[$targetRowId])) {
                unset($sourceRows[$targetRowId]);
            } else {
                $this->deleteRow($targetRow, $metadata);
            }

        }

        foreach ($sourceRows as $sourceRow) {
            $this->addRow($sourceRow, $metadata);
        }

    }


    /**
     * @param array $alias
     * @param Page $page
     * @return void
     */
    private function addRow(array $alias, ResourceCombo $page): void
    {

        $row = array(
            PageId::PROPERTY_NAME => $page->getPageId(),
            AliasPath::PERSISTENT_NAME => $alias[AliasPath::PERSISTENT_NAME],
            AliasType::PERSISTENT_NAME => $alias[AliasType::PERSISTENT_NAME]
        );

        // Page has change of location
        // Creation of an alias

        $sqlite = Sqlite::getSqlite();
        $res = $sqlite->storeEntry(self::ALIAS_TABLE_NAME, $row);
        if (!$res) {
            LogUtility::msg("There was a problem during PAGE_ALIASES insertion");
        }
        $sqlite->res_close($res);

    }

    /**
     * @param array $row
     * @param Metadata $metadata
     */
    private function deleteRow(array $row, Metadata $metadata): void
    {
        $tableName = $this->getTableName($metadata);
        $resourceIdAttribute = $metadata->getResource()->getUidObject()::getPersistentName();
        $metadataIdAttribute = $metadata->getUidObject()::getPersistentName();
        $delete = <<<EOF
delete from $tableName where $resourceIdAttribute = ? and $metadataIdAttribute = ?
EOF;

        $row = [
            $resourceIdAttribute => $row[$resourceIdAttribute],
            $metadataIdAttribute => $row[$metadataIdAttribute]
        ];
        $sqlite = Sqlite::getSqlite();
        $res = $sqlite->query($delete, $row);
        if ($res === false) {
            $message = Sqlite::getErrorMessage();
            LogUtility::msg("There was a problem during the row delete of $tableName. Message: $message");
            return;
        }
        $sqlite->res_close($res);

    }


    /**
     * @return null
     * @var Metadata $metadata
     */
    private function getDbTabularData(Metadata $metadata)
    {

        $sqlite = Sqlite::getSqlite();
        if ($sqlite === null) {
            return null;
        }
        if ($metadata->getResource() === null) {
            LogUtility::msg("The page resource is unknown. We can't retrieve the aliases");
            return null;
        }

        $uid = $metadata->getResource()->getUid();
        $pageId = $uid->getValue();
        if ($uid->getValue() === null) {
            if (!($uid instanceof PageId)) {
                LogUtility::msg("The resource identifier has no id. We can't retrieve the database data", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                return null;
            }
            $pageId = $uid->getPageIdOrGenerate();
        }

        $uidAttribute = strtoupper($uid::getPersistentName());
        $columns = [];
        foreach ($metadata->getChildren() as $children) {
            $columns[] = strtoupper($children::getPersistentName());
        }
        $tableName = $this->getTableName($metadata);

        $query = "select " . implode(", ", $columns) . " from $tableName where $uidAttribute = ? ";
        $res = $sqlite->query($query, $pageId);
        if (!$res) {
            $message = Sqlite::getErrorMessage();
            LogUtility::msg("An exception has occurred with the PAGE_ALIASES ({$metadata->getResource()}) selection query. Message: $message, Query: ($query");
        }
        $rows = $sqlite->res2arr($res);
        $sqlite->res_close($res);
        return $rows;

    }

    public function persist()
    {
        // there is no notion of commit in the sqlite plugin
    }

    public function isHierarchicalTextBased(): bool
    {
        return false;
    }

    public function reset()
    {
        throw new ExceptionComboRuntime("To implement");
    }

    public function getFromPersistentName(string $name, $default = null)
    {
        throw new ExceptionComboRuntime("Not implemented");
    }

    public function setFromPersistentName(string $name, $value)
    {
        throw new ExceptionComboRuntime("Not implemented");
    }

    public function getResource(): ResourceCombo
    {
        return $this->resource;
    }

    private function getTableName(Metadata $metadata): string
    {
        return $metadata->getResource()->getType() . "_" . $metadata::getPersistentName();

    }



}
