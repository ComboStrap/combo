<?php


namespace ComboStrap;


use action_plugin_combo_metamanager;

class Aliases extends Metadata
{

    public const ALIAS_ATTRIBUTE = "alias";
    public const ALIAS_PATH = "alias-path";
    public const ALIAS_TYPE = "alias-type";

    /**
     * @var Alias[]
     */
    private $aliases;
    /**
     * @var bool
     */
    private $wasBuild = false;

    public static function createForPage(Page $page): Aliases
    {

        return (new Aliases())->setResource($page);

    }

    public static function create(): Aliases
    {
        return new Aliases();
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
                $path = $value[Alias::ALIAS_PATH_PROPERTY];
                if (empty($path)) {
                    if (is_string($key)) {
                        // Old way (deprecated)
                        $path = $key;
                    } else {
                        LogUtility::msg("The path of the alias should not be empty to create a path", Alias::CANONICAL);
                    }
                }
                $type = $value[Alias::ALIAS_TYPE_PROPERTY];

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
                Alias::ALIAS_PATH_PROPERTY => $alias->getPath(),
                Alias::ALIAS_TYPE_PROPERTY => $alias->getType()
            ];
        }
        return array_values($array);
    }

    public function getName(): string
    {
        return self::ALIAS_ATTRIBUTE;
    }

    public function toPersistentValue()
    {
        $this->buildCheck();
        return self::toMetadataArray($this->aliases);
    }

    public function toPersistentDefaultValue()
    {
        return null;
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
        $sqlite = Sqlite::getSqlite();
        if ($sqlite === null) return [];

        $canonicalOrDefault = $this->getResource()->getCanonicalOrDefault();
        $res = $sqlite->query("select ALIAS from DEPRECATED_PAGES_ALIAS where CANONICAL = ?", $canonicalOrDefault);
        if (!$res) {
            LogUtility::msg("An exception has occurred with the deprecated alias selection query", LogUtility::LVL_MSG_ERROR);
            return [];
        }
        $deprecatedAliasInDb = $sqlite->res2arr($res);
        $sqlite->res_close($res);
        $deprecatedAliases = [];
        array_map(
            function ($row) use ($deprecatedAliases) {
                $alias = $row['ALIAS'];
                $deprecatedAliases[$alias] = Alias::create($this->getResource(), $alias)
                    ->setType(Alias::REDIRECT);
            },
            $deprecatedAliasInDb
        );

        /**
         * Delete them
         */
        try {
            if (sizeof($deprecatedAliasInDb) > 0) {
                $res = $sqlite->query("delete from DEPRECATED_PAGE_ALIASES where CANONICAL = ?", $canonicalOrDefault);
                if (!$res) {
                    LogUtility::msg("An exception has occurred with the delete deprecated alias statement", LogUtility::LVL_MSG_ERROR);
                }
                $sqlite->res_close($res);
            }
        } catch (\Exception $e) {
            LogUtility::msg("An exception has occurred with the deletion of deprecated aliases. Message: {$e->getMessage()}", LogUtility::LVL_MSG_ERROR);
        }

        /**
         * Return
         */
        return $deprecatedAliases;

    }

    /**
     * @return Alias[]
     */
    public function getAll(): array
    {
        $this->buildCheck();

        /**
         * We don't do that on build because
         * we are using a set a metadata method that creates
         * a cycle via the {@link Page::PAGE_METADATA_MUTATION_EVENT}
         */
        if (
            $this->aliases === null
            &&
            $this->getStore() !== null
            &&
            $this->getStore() instanceof MetadataDokuWikiStore
        ) {
            $this->aliases = $this->getAndDeleteDeprecatedAlias();
            /**
             * To validate the migration we set a value
             * (the array may be empty)
             */
            $this->sendToStore();
        }

        if ($this->aliases === null) {
            return [];
        }
        return array_values($this->aliases);
    }

    public
    function addAlias(string $aliasPath, $aliasType = Alias::REDIRECT): Aliases
    {
        $this->addAndGetAlias($aliasPath, $aliasType);
        return $this;
    }

    public
    function addAndGetAlias($aliasPath, $aliasType = Alias::REDIRECT): Alias
    {
        $this->buildCheck();
        $alias = Alias::create($this->getResource(), $aliasPath);

        if (!blank($aliasType)) {
            $alias->setType($aliasType);
        }

        $this->aliases[$aliasPath] = $alias;
        return $alias;
    }


    /**
     *
     */
    public
    function buildFromStore(): Aliases
    {
        $this->wasBuild = true;
        $aliases = $this->getStoreValue();
        $this->aliases = self::toNativeAliasArray($aliases);
        return $this;
    }

    public
    function getSize(): int
    {
        $aliases = $this->aliases;
        if ($this->aliases === null) {
            return 0;
        }
        return sizeof($aliases);
    }

    public
    function setFromPersistentFormat($value): Aliases
    {
        $this->aliases = $this->toNativeAliasArray($value);
        return $this;
    }


    public
    function getCanonical(): string
    {
        return Alias::CANONICAL;
    }

    private
    function buildCheck()
    {
        if (
            !$this->wasBuild
            && $this->aliases === null
            && $this->getStore() !== null
        ) {
            $this->buildFromStore();
            $this->wasBuild = true;
        }
    }


    public
    function getTab(): string
    {
        return action_plugin_combo_metamanager::TAB_REDIRECTION_VALUE;
    }

    public
    function getDataType(): string
    {
        return DataType::TABULAR_TYPE_VALUE;
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
    function toFormField(): FormMetaField
    {

        $this->buildCheck();

        $aliasPath = FormMetaField::create(Aliases::ALIAS_PATH)
            ->setCanonical(Alias::CANONICAL)
            ->setLabel("Alias Path")
            ->setDescription("The path of the alias");
        $aliasType = FormMetaField::create(Aliases::ALIAS_TYPE)
            ->setCanonical(Alias::CANONICAL)
            ->setLabel("Alias Type")
            ->setDescription("The type of the alias")
            ->setDomainValues(Alias::getPossibleTypesValues());


        $aliasesValues = $this->aliases;
        if ($aliasesValues !== null) {
            foreach ($aliasesValues as $alias) {
                $aliasPath->addValue($alias->getPath());
                $aliasType->addValue($alias->getType(), Alias::getDefaultType());
            }
        }
        /**
         * To be able to add one
         */
        $aliasPath->addValue(null);
        $aliasType->addValue(null, Alias::getDefaultType());

        $formField = parent::toFormField();
        return $formField
            ->addColumn($aliasPath)
            ->addColumn($aliasType);

    }

    public
    function setFromFormData($formData): Aliases
    {
        $pathData = $formData[self::ALIAS_PATH];
        if ($pathData !== null && $pathData !== "") {
            $this->aliases = [];
            $typeData = $formData[self::ALIAS_TYPE];
            $counter = 0;
            foreach ($pathData as $path) {
                if ($path !== "" && $path !== null) {
                    $type = $typeData[$counter];
                    $this->aliases[] = Alias::create($this->getResource(), $path)
                        ->setType($type);
                }
                $counter++;
            }
        }
        return $this;
    }

    public
    function getMutable(): bool
    {
        return true;
    }

    public
    function has(Alias $alias): bool
    {
        return isset($this->aliases[$alias->getPath()]);
    }

    public
    function remove(Alias $alias)
    {
        unset($this->aliases[$alias->getPath()]);
    }


}
