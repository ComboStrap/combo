<?php


namespace ComboStrap\Meta\Field;


use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionSqliteNotAvailable;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataSystem;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;
use ComboStrap\Meta\Api\MetadataTabular;
use ComboStrap\MetaManagerForm;
use ComboStrap\Sqlite;
use ComboStrap\StringUtility;
use ComboStrap\WikiPath;

class Aliases extends MetadataTabular
{

    public const PROPERTY_NAME = "aliases";


    public static function createForPage(MarkupPath $page): Aliases
    {

        return (new Aliases())->setResource($page);

    }

    public static function create(): Aliases
    {
        return new Aliases();
    }

    /**
     * @return Alias[]|null
     * @throws ExceptionNotFound
     */
    public function getValueAsAlias(): array
    {
        $rows = $this->getValue();
        $aliases = [];
        foreach ($rows as $row) {
            /**
             * @var AliasPath $aliasMeta
             */
            $aliasMeta = $row[AliasPath::getPersistentName()];
            $alias = Alias::create($this->getResource(), $aliasMeta->getValue());
            /**
             * @var AliasType $aliasType
             */
            $aliasType = $row[AliasType::getPersistentName()] ?? null;
            if ($aliasType !== null) {
                try {
                    $alias->setType($aliasType->getValue());
                } catch (ExceptionNotFound $e) {
                    // ok
                }

            }
            $aliases[] = $alias;
        }
        return $aliases;
    }


    /**
     * @param array|null $aliasesPersistentValues
     * return Alias[]
     */
    private function toNativeAliasArray(?array $aliasesPersistentValues): array
    {
        if ($aliasesPersistentValues === null) {
            return [];
        }
        $aliases = [];
        foreach ($aliasesPersistentValues as $key => $value) {
            if (is_array($value)) {
                $path = $value[AliasPath::PERSISTENT_NAME];
                if (empty($path)) {
                    if (is_string($key)) {
                        // Old way (deprecated)
                        $path = $key;
                    } else {
                        LogUtility::msg("The path of the alias should not be empty to create a path", Alias::CANONICAL);
                    }
                }
                $type = $value[AliasType::PERSISTENT_NAME];

                /**
                 * We don't create via the {@link Aliases::addAlias()}
                 * to not persist for each each alias value
                 **/
                $aliases[] = Alias::create($this->getResource(), $path)
                    ->setType($type);
            } else {
                $path = $value;
                if (empty($path)) {
                    LogUtility::msg("The value of the alias array should not be empty as it's the alias path", Alias::CANONICAL);
                }
                if (!is_string($path)) {
                    $path = StringUtility::toString($path);
                    LogUtility::error("The alias element ($path) is not a string", Alias::CANONICAL);
                }
                $path = WikiPath::createMarkupPathFromId($path);
                $aliases[] = Alias::create($this->getResource(), $path);
            }
        }
        return $aliases;
    }


    /**
     * @param Alias[] $aliases
     * @return null|array - the array to be saved in a text/json file
     */
    public static function toMetadataArray(?array $aliases): ?array
    {
        if ($aliases === null) {
            return null;
        }
        $array = [];
        foreach ($aliases as $alias) {
            $array[$alias->getPath()->toAbsoluteId()] = [
                AliasPath::PERSISTENT_NAME => $alias->getPath()->toAbsoluteId(),
                AliasType::PERSISTENT_NAME => $alias->getType()
            ];
        }
        return array_values($array);
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }


    static public function getPersistenceType(): string
    {
        return MetadataDokuWikiStore::PERSISTENT_DOKUWIKI_KEY;
    }

    /**
     * Code refactoring
     * This method is not in the database page
     * because it would create a cycle
     *
     * The old data was saved in the database
     * but should have been saved on the file system
     *
     * Once
     * @return Alias[]
     * @throws ExceptionSqliteNotAvailable
     * @deprecated 2021-10-31
     */
    private
    function getAndDeleteDeprecatedAlias(): array
    {
        $sqlite = Sqlite::createOrGetSqlite();

        try {
            $canonicalOrDefault = $this->getResource()->getCanonicalOrDefault();
        } catch (ExceptionNotFound $e) {
            return [];
        }
        /** @noinspection SqlResolve */
        $request = $sqlite
            ->createRequest()
            ->setQueryParametrized("select ALIAS from DEPRECATED_PAGES_ALIAS where CANONICAL = ?", [$canonicalOrDefault]);
        $deprecatedAliases = [];
        $deprecatedAliasInDb = [];
        try {
            $deprecatedAliasInDb = $request
                ->execute()
                ->getRows();
        } catch (ExceptionCompile $e) {
            LogUtility::msg("An exception has occurred with the deprecated alias selection query. {$e->getMessage()}");
            return [];
        } finally {
            $request->close();
        }

        array_map(
            function ($row) use ($deprecatedAliases) {
                $alias = $row['ALIAS'];
                $deprecatedAliases[$alias] = Alias::create($this->getResource(), $alias)
                    ->setType(AliasType::REDIRECT);
            },
            $deprecatedAliasInDb
        );

        /**
         * Delete them
         */

        if (sizeof($deprecatedAliasInDb) > 0) {
            /** @noinspection SqlResolve */
            $request = $sqlite
                ->createRequest()
                ->setQueryParametrized("delete from DEPRECATED_PAGE_ALIASES where CANONICAL = ?", [$canonicalOrDefault]);
            try {
                $request->execute();
            } catch (ExceptionCompile $e) {
                LogUtility::msg("An exception has occurred with the delete deprecated alias statement. {$e->getMessage()}", LogUtility::LVL_MSG_ERROR);
            } finally {
                $request->close();
            }
        }


        /**
         * Return
         */
        return $deprecatedAliases;

    }


    /**
     * @return Metadata[][] - an list of rows of metadata columns
     */
    public function getDefaultValue(): array
    {
        return
            [
                [
                    AliasPath::getPersistentName() => AliasPath::createForParent($this),
                    AliasType::getPersistentName() => AliasType::createForParent($this)->setFromStoreValueWithoutException(AliasType::DEFAULT)
                ]
            ];
    }


    public function getValue(): array
    {
        $this->buildCheck();

        /**
         * We don't do that on build because
         * we are using a set a metadata method that creates
         * a cycle via the {@link MetadataMutation::PAGE_METADATA_MUTATION_EVENT}
         */
        if (
            !$this->valueIsNotNull()
            &&
            $this->getReadStore() !== null
            &&
            $this->getReadStore() instanceof MetadataDokuWikiStore
        ) {
            $this->getAndDeleteDeprecatedAlias();
            /**
             * To validate the migration we set a value
             * (the array may be empty)
             */
            try {
                $this->sendToWriteStore();
            } catch (ExceptionCompile $e) {
                LogUtility::msg("Error while persisting the new data");
            }
        }

        return parent::getValue();
    }

    /**
     * @throws ExceptionCompile
     */
    public
    function addAlias(string $aliasPath, $aliasType = null): Aliases
    {
        $this->addAndGetAlias($aliasPath, $aliasType);
        return $this;
    }

    /**
     * @throws ExceptionCompile
     */
    public
    function addAndGetAlias($aliasPath, $aliasType = null): Alias
    {
        $this->buildCheck();
        /**
         * @var AliasPath $path
         */
        $path = MetadataSystem::toMetadataObject(AliasPath::class, $this)
            ->setFromStoreValue($aliasPath);
        $row[$path::getPersistentName()] = $path;

        $alias = Alias::create($this->getResource(), $path->getValue());

        if ($aliasType !== null) {
            $aliasObject = MetadataSystem::toMetadataObject(AliasType::class, $this)
                ->setFromStoreValue($aliasType);
            $row[$aliasObject::getPersistentName()] = $aliasObject;
            $alias->setType($aliasType);
        }
        $this->rows[$path->getValue()->toAbsoluteId()] = $row;

        return $alias;
    }


    static public
    function getCanonical(): string
    {
        return Alias::CANONICAL;
    }

    static public
    function getTab(): string
    {
        return MetaManagerForm::TAB_REDIRECTION_VALUE;
    }

    static public
    function getDescription(): string
    {
        return "Aliases that will redirect to this page.";
    }

    static public function getLabel(): string
    {
        return "Page Aliases";
    }


    static public function isMutable(): bool
    {
        return true;
    }

    public function getUidClass(): ?string
    {
        return AliasPath::class;
    }

    /**
     * @return Metadata[]
     */
    static public function getChildrenClass(): array
    {
        return [AliasPath::class, AliasType::class];
    }


    static public function isOnForm(): bool
    {
        return true;
    }
}
