<?php


namespace ComboStrap;


/**
 * Class MetadataDbStore
 * @package ComboStrap
 * The database store
 * TODO: {@link DatabasePageRow} should be integrated into MetadataDbStore
 *   A tabular metadata should be created to get all {@link DatabasePageRow::getMetaRecord()}
 */
class MetadataDbStore extends MetadataStoreAbs
{
    const CANONICAL = "database";

    /**
     * @var DatabasePageRow[]
     */
    private static $dbRows = [];


    static function getOrCreateFromResource(ResourceCombo $resourceCombo): MetadataStore
    {
        return new MetadataDbStore($resourceCombo);
    }

    public static function resetAll()
    {
        self::$dbRows = [];
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
            $fsStore = MetadataDokuWikiStore::getOrCreateFromResource($pageMetaFromFileSystem);
            $pageMetaFromFileSystem->setReadStore($fsStore);

            $database = DatabasePageRow::createFromPageObject($pageMetaFromFileSystem);
            if (!$database->exists()) {
                return null;
            }
            return $database->getFromRow($metadata->getName());

        }
    }

    /**
     * @throws ExceptionCombo
     */
    private function syncTabular(MetadataTabular $metadata)
    {


        $uid = $metadata->getUidObject();
        if ($uid === null) {
            throw new ExceptionCombo("The uid class should be defined for the metadata ($metadata)");
        }


        $sourceRows = $metadata->toStoreValue();
        if ($sourceRows === null) {
            return;
        }

        $targetRows = $this->getDbTabularData($metadata);
        if ($targetRows !== null) {
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
     * @param Metadata $metadata
     * @return void
     * @throws ExceptionCombo - if page id is null
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
            throw new ExceptionCombo("The id ($resourceUidObject) is null for the resource $resourceCombo. We can't add a row in the database.");
        }
        $row[$resourceUidObject::getPersistentName()] = $uidValue;

        $tableName = $this->getTableName($metadata);
        $request = Sqlite::createOrGetSqlite()
            ->createRequest()
            ->setTableRow($tableName, $row);
        try {
            $request->execute();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("There was a problem during rows insertion for the table ($tableName)" . $e->getMessage());
        } finally {
            $request->close();
        }


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
        $request = Sqlite::createOrGetSqlite()
            ->createRequest()
            ->setQueryParametrized($delete, $row);
        try {
            $request->execute();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("There was a problem during the row delete of $tableName. Message: {$e->getMessage()}");
            return;
        } finally {
            $request->close();
        }


    }


    /**
     * @return null
     * @throws ExceptionCombo
     * @var Metadata $metadata
     */
    private function getDbTabularData(Metadata $metadata): ?array
    {

        $sqlite = Sqlite::createOrGetSqlite();
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

            LogUtility::msg("The resource identifier has no id. We can't retrieve the database data", LogUtility::LVL_MSG_ERROR, $this->getCanonical());
            return null;

        }

        $uidAttribute = $uid::getPersistentName();
        $children = $metadata->getChildrenObject();
        if ($children === null) {
            throw new ExceptionCombo("The children of the tabular metadata ($metadata) should be set to synchronize into the database");
        }
        $attributes = [];
        foreach ($children as $child) {
            $attributes[] = $child::getPersistentName();
        }
        $tableName = $this->getTableName($metadata);
        $query = Sqlite::createSelectFromTableAndColumns($tableName, $attributes);
        $query = "$query where $uidAttribute = ? ";
        $res = $sqlite
            ->createRequest()
            ->setQueryParametrized($query, [$pageId]);
        $rows = [];
        try {
            $rows = $res
                ->execute()
                ->getRows();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("An exception has occurred with the $tableName ({$metadata->getResource()}) selection query. Message: {$e->getMessage()}, Query: ($query");
            return null;
        } finally {
            $res->close();
        }
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
        $row = $this->getDatabaseRow();
        $value = $row->getFromRow($name);
        if ($value !== null) {
            return $value;
        }
        return $default;
    }

    public function setFromPersistentName(string $name, $value)
    {
        throw new ExceptionComboRuntime("Not implemented");
    }


    private function getTableName(Metadata $metadata): string
    {
        return $metadata->getResource()->getType() . "_" . $metadata::getPersistentName();

    }

    public function getCanonical(): string
    {
        return self::CANONICAL;
    }

    private function getDatabaseRow(): DatabasePageRow
    {
        $mapKey = $this->getResource()->getPath()->toString();
        $row = self::$dbRows[$mapKey];
        if ($row === null) {
            $page = $this->getResource();
            if (!($page instanceof Page)) {
                throw new ExceptionComboRuntime("The resource should be a page, {$page->getType()} is not supported");
            }
            $row = DatabasePageRow::createFromPageObject($page);
            self::$dbRows[$mapKey] = $row;
        }
        return $row;
    }


}
