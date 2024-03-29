<?php


namespace ComboStrap\Meta\Store;


use ComboStrap\DatabasePageRow;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotExists;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionRuntime;
use ComboStrap\ExceptionRuntimeInternal;
use ComboStrap\ExceptionSqliteNotAvailable;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;
use ComboStrap\Meta\Api\MetadataStore;
use ComboStrap\Meta\Api\MetadataStoreAbs;
use ComboStrap\Meta\Api\MetadataTabular;
use ComboStrap\ResourceCombo;
use ComboStrap\Sqlite;

/**
 * Class MetadataDbStore
 * @package ComboStrap
 * The database store
 * TODO: {@link DatabasePageRow} should be integrated into MetadataDbStore
 *   A tabular metadata should be created to get all {@link DatabasePageRow::getMetaRecord()}
 */
class MetadataDbStore extends MetadataStoreAbs implements MetadataStore
{
    const CANONICAL = "database";

    /**
     * @var DatabasePageRow[]
     */
    private static array $dbRows = [];
    private Sqlite $sqlite;

    /**
     * @var Metadata - the uid metadata
     * They are here to throw at construct time
     */
    private Metadata $resourceUidMeta;
    /**
     * @var mixed - the uid metadata value
     * They are here to throw at construct time
     */
    private $resourceUidMetaValue;

    /**
     * @throws ExceptionSqliteNotAvailable
     * @throws ExceptionNotExists - if the resource does not exist in the database
     */
    public function __construct(ResourceCombo $resource)
    {
        // sqlite in the constructor to handle only one sqlite exception
        $this->sqlite = Sqlite::createOrGetSqlite();

        // uid of the resoure (the old page id)
        $this->resourceUidMeta = $resource->getUid();
        $persistentName = $this->resourceUidMeta::getPersistentName();
        /**
         * If  uid is null, it's not yet in the database
         * and returns the default or null, or empty array
         */
        $this->resourceUidMetaValue = MetadataDokuWikiStore::getOrCreateFromResource($resource)
            ->getFromName($persistentName);

        parent::__construct($resource);
    }


    /**
     * @throws ExceptionNotExists
     * @throws ExceptionSqliteNotAvailable
     */
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

        throw new ExceptionRuntime("The metadata ($metadata) is not yet supported on set", self::CANONICAL);

    }

    public function get(Metadata $metadata, $default = null)
    {

        $resource = $metadata->getResource();
        if (!($resource instanceof MarkupPath)) {
            throw new ExceptionRuntime("The resource type ({$resource->getType()}) is not yet supported for the database metadata store", self::CANONICAL);
        }

        if ($metadata instanceof MetadataTabular) {

            return $this->getDbTabularData($metadata);

        } else {

            $pageMetaFromFileSystem = MarkupPath::createPageFromAbsoluteId($resource->getPathObject()->toAbsoluteId());
            $fsStore = MetadataDokuWikiStore::getOrCreateFromResource($pageMetaFromFileSystem);
            $pageMetaFromFileSystem->setReadStore($fsStore);

            $database = DatabasePageRow::getOrCreateFromPageObject($pageMetaFromFileSystem);
            if (!$database->exists()) {
                return null;
            }
            return $database->getFromRow($metadata->getName());

        }
    }

    /**
     *
     */
    private function syncTabular(MetadataTabular $metadata)
    {

        try {
            $uid = $metadata->getUidObject();
        } catch (ExceptionBadArgument $e) {
            throw new ExceptionRuntimeInternal("The uid class should be defined for the metadata ($metadata)");
        }

        $sourceRows = $metadata->toStoreValue();
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
     * @param array $row
     * @param Metadata $metadata
     * @return void
     */
    private function addRow(array $row, Metadata $metadata): void
    {

        /**
         * Add the id
         */
        $resourceCombo = $metadata->getResource();
        $resourceUidObject = $resourceCombo->getUidObject();
        try {
            $uidValue = $resourceUidObject->getValue();
        } catch (ExceptionNotFound $e) {
            // not yet in db
            return;
        }

        $row[$resourceUidObject::getPersistentName()] = $uidValue;

        $tableName = $this->getTableName($metadata);
        $request = $this->sqlite
            ->createRequest()
            ->setTableRow($tableName, $row);
        try {
            $request->execute();
        } catch (ExceptionCompile $e) {
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
            $row[$resourceIdAttribute] ?? null,
            $row[$metadataIdAttribute] ?? null
        ];
        $request = Sqlite::createOrGetSqlite()
            ->createRequest()
            ->setQueryParametrized($delete, $row);
        try {
            $request->execute();
        } catch (ExceptionCompile $e) {
            LogUtility::msg("There was a problem during the row delete of $tableName. Message: {$e->getMessage()}");
            return;
        } finally {
            $request->close();
        }


    }


    /**
     * @return array - the rows
     * @var Metadata $metadata
     */
    private function getDbTabularData(Metadata $metadata): array
    {

        $uid = $this->resourceUidMeta;
        $uidValue = $this->resourceUidMetaValue;
        if ($uidValue === null) {
            // no yet in the db
            return [];
        }

        $uidAttribute = $uid::getPersistentName();
        $children = $metadata->getChildrenObject();
        if ($children === null) {
            throw new ExceptionRuntimeInternal("The children of the tabular metadata ($metadata) should be set to synchronize into the database");
        }
        $attributes = [];
        foreach ($children as $child) {
            $attributes[] = $child::getPersistentName();
        }
        $tableName = $this->getTableName($metadata);
        $query = Sqlite::createSelectFromTableAndColumns($tableName, $attributes);
        $query = "$query where $uidAttribute = ? ";
        $res = $this->sqlite
            ->createRequest()
            ->setQueryParametrized($query, [$uidValue]);
        $rows = [];
        try {
            $rows = $res
                ->execute()
                ->getRows();
        } catch (ExceptionCompile $e) {
            throw new ExceptionRuntimeInternal("An exception has occurred with the $tableName ({$metadata->getResource()}) selection query. Message: {$e->getMessage()}, Query: ($query", self::CANONICAL, 1, $e);
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
        throw new ExceptionRuntime("To implement");
    }

    public function getFromName(string $name, $default = null)
    {

        if ($this->resourceUidMetaValue === null) {
            // not yet in the db
            return $default;
        }

        $row = $this->getDatabaseRow();
        $value = $row->getFromRow($name);
        if ($value !== null) {
            return $value;
        }
        return $default;
    }

    public function setFromPersistentName(string $name, $value, $default = null)
    {
        throw new ExceptionRuntime("Not implemented");
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
        $mapKey = $this->getResource()->getPathObject()->toAbsoluteId();
        $row = self::$dbRows[$mapKey];
        if ($row === null) {
            $page = $this->getResource();
            $row = DatabasePageRow::getFromPageObject($page);
            self::$dbRows[$mapKey] = $row;
        }
        return $row;
    }


}
