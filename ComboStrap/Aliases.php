<?php


namespace ComboStrap;


class Aliases extends MetadataTabular
{

    public const PROPERTY_NAME = "aliases";


    /**
     * @var Alias[]
     */
    private $aliases;


    public static function createForPage(Page $page): Aliases
    {

        return (new Aliases())->setResource($page);

    }

    public static function create(): Aliases
    {
        return new Aliases();
    }

    /**
     * @return Alias[]|null
     */
    public function getValueAsAlias(): ?array
    {
        $rows = $this->getValue();
        if ($rows === null) {
            return null;
        }
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
            $aliasType = $row[AliasType::getPersistentName()];
            if ($aliasType !== null) {
                $aliasTypeValue = $aliasType->getValue();
                if ($aliasTypeValue !== null) {
                    $alias->setType($aliasType->getValue());
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
                    LogUtility::msg("The alias element ($path) is not a string", Alias::CANONICAL);
                }
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
            $array[$alias->getPath()] = [
                AliasPath::PERSISTENT_NAME => $alias->getPath(),
                AliasType::PERSISTENT_NAME => $alias->getType()
            ];
        }
        return array_values($array);
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }


    public function getPersistenceType(): string
    {
        return MetadataDokuWikiStore::PERSISTENT_METADATA;
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
     * @deprecated 2021-10-31
     */
    private
    function getAndDeleteDeprecatedAlias(): array
    {
        $sqlite = Sqlite::createOrGetSqlite();
        if ($sqlite === null) return [];

        $canonicalOrDefault = $this->getResource()->getCanonicalOrDefault();
        $request = $sqlite
            ->createRequest()
            ->setQueryParametrized("select ALIAS from DEPRECATED_PAGES_ALIAS where CANONICAL = ?", [$canonicalOrDefault]);
        $deprecatedAliases = [];
        $deprecatedAliasInDb = [];
        try {
            $deprecatedAliasInDb = $request
                ->execute()
                ->getRows();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("An exception has occurred with the deprecated alias selection query. {$e->getMessage()}", LogUtility::LVL_MSG_ERROR);
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
            $request = $sqlite
                ->createRequest()
                ->setQueryParametrized("delete from DEPRECATED_PAGE_ALIASES where CANONICAL = ?", [$canonicalOrDefault]);
            try {
                $request->execute();
            } catch (ExceptionCombo $e) {
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

    public function getDefaultValue(): array
    {
        return
            [
                [
                    AliasPath::getPersistentName() => null,
                    AliasType::getPersistentName() => AliasType::createForParent($this)->buildFromStoreValue(AliasType::DEFAULT)
                ]
            ];
    }


    public function getValue(): ?array
    {
        $this->buildCheck();

        /**
         * We don't do that on build because
         * we are using a set a metadata method that creates
         * a cycle via the {@link MetadataDokuWikiStore::PAGE_METADATA_MUTATION_EVENT}
         */
        if (
            !$this->valueIsNotNull()
            &&
            $this->getReadStore() !== null
            &&
            $this->getReadStore() instanceof MetadataDokuWikiStore
        ) {
            $this->aliases = $this->getAndDeleteDeprecatedAlias();
            /**
             * To validate the migration we set a value
             * (the array may be empty)
             */
            try {
                $this->sendToWriteStore();
            } catch (ExceptionCombo $e) {
                LogUtility::msg("Error while persisting the new data");
            }
        }

        return parent::getValue();
    }

    /**
     * @throws ExceptionCombo
     */
    public
    function addAlias(string $aliasPath, $aliasType = null): Aliases
    {
        $this->addAndGetAlias($aliasPath, $aliasType);
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public
    function addAndGetAlias($aliasPath, $aliasType = null): Alias
    {
        $this->buildCheck();
        $path = Metadata::toMetadataObject(AliasPath::class, $this)
            ->setFromStoreValue($aliasPath);
        $row[$path::getPersistentName()] = $path;

        $alias = Alias::create($this->getResource(), $path->getValue());

        if ($aliasType !== null) {
            $aliasObject = Metadata::toMetadataObject(AliasType::class, $this)
                ->setFromStoreValue($aliasType);
            $row[$aliasObject::getPersistentName()] = $aliasObject;
            $alias->setType($aliasType);
        }
        $this->rows[$path->getValue()] = $row;

        return $alias;
    }


    public
    function getCanonical(): string
    {
        return Alias::CANONICAL;
    }

    public
    function getTab(): string
    {
        return MetaManagerForm::TAB_REDIRECTION_VALUE;
    }

    public
    function getDescription(): string
    {
        return "Aliases that will redirect to this page.";
    }

    public
    function getLabel(): string
    {
        return "Page Aliases";
    }


    public
    function getMutable(): bool
    {
        return true;
    }

    public function getUidClass(): ?string
    {
        return AliasPath::class;
    }

    public function getChildrenClass(): ?array
    {
        return [AliasPath::class, AliasType::class];
    }


}
