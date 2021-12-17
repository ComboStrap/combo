<?php


namespace ComboStrap;

use http\Exception\RuntimeException;

/**
 * Class MetadataDbStore
 * @package ComboStrap
 * The database store
 */
class MetadataDbStore implements MetadataStore
{

    const ALIAS_TABLE_NAME = "PAGE_ALIASES";

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

        return  new MetadataDbStore($resourceCombo);

    }

    public function set(Metadata $metadata)
    {
        switch ($metadata->getName()) {
            case Aliases::PROPERTY_NAME:
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


        switch ($metadata->getName()) {
            case Aliases::PROPERTY_NAME:
                return $this->getAliasesInPersistentValue($metadata);
            default:
                $pageMetaFromFileSystem = Page::createPageFromQualifiedPath($resource->getPath()->toString())

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

    private function setAliases(Metadata $metadata)
    {

        $aliasesToStore = $metadata->toStoreValue();
        if($aliasesToStore===null){
            return;
        }

        $dbAliases = $this->getAliasesInPersistentValue($metadata);
        $dbAliasMap = [];
        if ($dbAliases !== null) {
            foreach ($dbAliases as $dbAlias) {
                $dbAliasMap[$dbAlias[AliasPath::PERSISTENT_NAME]] = $dbAlias;
            }
        }
        foreach ($aliasesToStore as $aliasToStore) {

            if (isset($dbAliasMap[$aliasToStore[AliasPath::PERSISTENT_NAME]])) {
                unset($dbAliasMap[$aliasToStore[AliasPath::PERSISTENT_NAME]]);
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
     * @param array $dbAliasPath
     * @param $page
     * @return void
     */
    private function deleteAlias(array $dbAliasPath, $page): void
    {
        $pageIdAttributes = PageId::PROPERTY_NAME;
        $pathAttribute = PagePath::PROPERTY_NAME;
        $aliasTables = self::ALIAS_TABLE_NAME;
        $delete = <<<EOF
delete from $aliasTables where $pageIdAttributes = ? and $pathAttribute = ?
EOF;

        $row = [
            $pageIdAttributes => $page->getPageId(),
            $pathAttribute => $dbAliasPath[AliasPath::PERSISTENT_NAME]
        ];
        $sqlite = Sqlite::getSqlite();
        $res = $sqlite->query($delete, $row);
        if ($res === false) {
            $message = Sqlite::getErrorMessage();
            LogUtility::msg("There was a problem during the alias delete. $message");
            return;
        }
        $sqlite->res_close($res);

    }


    /**
     * @return null
     * @var Metadata $metadata
     */
    private function getAliasesInPersistentValue(Metadata $metadata)
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
                LogUtility::msg("The resource identifier is not a page id. We can't retrieve the aliases", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                return null;
            }
            $pageId = $uid->getPageIdOrGenerate();
        }
        $aliases = Aliases::create()
            ->setResource($metadata->getResource());
        $pageIdAttribute = strtoupper(PageId::getPersistentName());
        $pathAttribute = strtoupper(AliasPath::getPersistentName());
        $typeAttribute = strtoupper(AliasType::getPersistentName());
        $tableAliases = self::ALIAS_TABLE_NAME;

        $query = "select $pathAttribute, $typeAttribute from $tableAliases where $pageIdAttribute = ? ";
        $res = $sqlite->query($query, $pageId);
        if (!$res) {
            $message = Sqlite::getErrorMessage();
            LogUtility::msg("An exception has occurred with the PAGE_ALIASES ({$metadata->getResource()}) selection query. Message: $message, Query: ($query");
        }
        $rowAliases = $sqlite->res2arr($res);
        $sqlite->res_close($res);
        foreach ($rowAliases as $row) {
            try {
                $aliases->addAlias($row[$pathAttribute], $row[$typeAttribute]);
            } catch (ExceptionCombo $e) {
                LogUtility::msg("Error while building the aliases from the Db." . $e->getMessage(), LogUtility::LVL_MSG_ERROR, $e->getCanonical());
            }
        }
        return $aliases->toStoreValue();

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
        throw new RuntimeException("Not implemented");
    }

    public function setFromPersistentName(string $name, $value)
    {
        throw new RuntimeException("Not implemented");
    }

    public function getResource(): ResourceCombo
    {
        return $this->resource;
    }
}
