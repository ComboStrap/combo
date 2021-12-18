<?php


namespace ComboStrap;


use http\Exception\RuntimeException;

/**
 * Class MetadataDbStore
 * @package ComboStrap
 * The database store
 */
class MetadataDbStore extends MetadataStoreAbs
{


    static function createFromResource(ResourceCombo $resourceCombo): MetadataStore
    {
        return new MetadataDbStore($resourceCombo);
    }

    public function set(Metadata $metadata)
    {
        if ($metadata instanceof MetadataTabular) {

            $this->syncTabular($metadata);
            return;
        }

        throw new ExceptionComboRuntime("The metadata ($metadata) is not yet supported on set", self::CANONICAL);

    }

    public function get(Metadata $metadata, $default = null)
    {
        $resource = $metadata->getResource();
        if (!($resource instanceof Page)) {
            throw new ExceptionComboRuntime("The resource type ({$resource->getType()}) is not yet supported for the database metadata store", self::CANONICAL);
        }


        if ($metadata instanceof MetadataTabular) {

            return $this->getDbTabularData($metadata);

        } else {

            $pageMetaFromFileSystem = Page::createPageFromQualifiedPath($resource->getPath()->toString());
            $fsStore = MetadataDokuWikiStore::createFromResource($pageMetaFromFileSystem);
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

    /**
     * @throws ExceptionCombo
     */
    private function syncTabular(MetadataTabular $metadata)
    {


        $uidClass = $metadata->getUidClass();
        if ($uidClass === null) {
            throw new ExceptionCombo("The uid class should be defined for the metadata ($metadata)");
        }
        $uid = Metadata::toMetadataObject($uidClass)
            ->setResource($metadata->getResource());

        $sourceRows = $metadata->toStoreValue();
        if ($sourceRows === null) {
            return;
        }

        $targetRows = $this->getDbTabularData($metadata);
        if($targetRows!==null) {
            foreach ($targetRows as $targetRow) {

                $targetRowId = $targetRow[$uid::getPersistentName()];
                if (isset($sourceRows[$targetRowId])) {
                    unset($sourceRows[$targetRowId]);
                } else {
                    $this->deleteRow($targetRow, $metadata);
                }

            }
        }

        foreach ($sourceRows as $sourceRow) {
            $this->addRow($sourceRow, $metadata);
        }

    }


    /**
     * @param array $row
     * @param Page $resource
     * @return void
     */
    private function addRow(array $row, Metadata $metadata): void
    {

        /**
         * Add the id
         */
        $resourceCombo = $metadata->getResource();
        $resourceUidObject = $resourceCombo->getUidObject();
        $uidValue = $resourceUidObject->getValue();
        if ($uidValue === null) {
            throw new ExceptionComboRuntime("The id ($resourceUidObject) is null for the resource $resourceCombo. We can't add a row in the database.");
        }
        $row[$resourceUidObject::getPersistentName()] = $uidValue;

        // Page has change of location
        // Creation of an alias

        $sqlite = Sqlite::getSqlite();
        $res = $sqlite->storeEntry($this->getTableName($metadata), $row);
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

        $uidAttribute = $uid::getPersistentName();
        $columns = [];
        $children = $metadata->getChildren();
        if($children===null){
            throw new ExceptionCombo("The children of the tabular metadata ($metadata) should be set to synchronize into the database");
        }
        foreach ($children as $child) {
            $columns[] = $child::getPersistentName() . " as \"" . $child::getPersistentName() . "\"";
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

    public function getCanonical(): string
    {
        return "database";
    }


}
