<?php


namespace ComboStrap;


use action_plugin_combo_metadescription;
use ModificationDate;
use Slug;

abstract class Metadata
{
    const CANONICAL = "metadata";

    public const NOT_MODIFIABLE_METAS = [
        "date",
        "user",
        "last_change",
        "creator",
        "contributor"
    ];



    /**
     * @var bool
     */
    protected $wasBuild = false;

    /**
     * The metadata is for this resource
     * @var ResourceCombo $resource
     */
    private $resource;

    /**
     * @var MetadataStore
     */
    private $readStore;
    /**
     * @var Metadata|null
     */
    private $parent;
    /**
     * @var MetadataStore
     */
    private $writeStore;
    /**
     * @var Metadata
     */
    private $uidObject;
    /**
     * @var Metadata[]
     */
    private $childrenObject;

    /**
     * The metadata may be just not stored
     * The page is just the scope
     */
    public function __construct(Metadata $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * @param $class
     * @param Metadata|null $parent
     * @return Metadata
     * @throws ExceptionCombo
     */
    public static function toMetadataObject($class, Metadata $parent = null): Metadata
    {
        if ($class === null) {
            throw new ExceptionCombo("The string class is empty");
        }
        if (!is_subclass_of($class, Metadata::class)) {
            throw new ExceptionCombo("The class ($class) is not a metadata class");
        }
        return new $class($parent);
    }


    public function getParent(): ?Metadata
    {
        return $this->parent;
    }

    /**
     * The class string of the child/columns metadata
     * @return null|string[];
     */
    public function getChildrenClass(): ?array
    {
        return null;
    }

    public static function getForName(string $name): ?Metadata
    {

        $name = strtolower(trim($name));
        /**
         * TODO: this array could be build automatically by creating an object for each metadata
         */
        switch ($name) {
            case Canonical::getName():
                return new Canonical();
            case PageType::getName():
                return new PageType();
            case PageH1::getName():
                return new PageH1();
            case Aliases::getName():
            case AliasPath::getName():
            case AliasType::getName():
                return new Aliases();
            case PageImages::getName():
            case PageImages::OLD_PROPERTY_NAME:
            case PageImages::getPersistentName():
            case PageImagePath::getName():
            case PageImageUsage::getName():
                return new PageImages();
            case Region::OLD_REGION_PROPERTY:
            case Region::getName():
                return new Region();
            case Lang::PROPERTY_NAME:
                return new Lang();
            case PageTitle::TITLE:
                return new PageTitle();
            case PagePublicationDate::OLD_META_KEY:
            case PagePublicationDate::PROPERTY_NAME:
                return new PagePublicationDate();
            case ResourceName::PROPERTY_NAME:
                return new ResourceName();
            case LdJson::OLD_ORGANIZATION_PROPERTY:
            case LdJson::PROPERTY_NAME:
                return new LdJson();
            case PageLayout::PROPERTY_NAME:
                return new PageLayout();
            case StartDate::PROPERTY_NAME:
                return new StartDate();
            case EndDate::PROPERTY_NAME:
                return new EndDate();
            case PageDescription::DESCRIPTION_PROPERTY:
                return new PageDescription();
            case Slug::PROPERTY_NAME:
                return new Slug();
            case PageKeywords::PROPERTY_NAME:
                return new PageKeywords();
            case CacheExpirationFrequency::PROPERTY_NAME:
                return new CacheExpirationFrequency();
            case QualityDynamicMonitoringOverwrite::PROPERTY_NAME:
                return new QualityDynamicMonitoringOverwrite();
            case LowQualityPageOverwrite::PROPERTY_NAME:
                return new LowQualityPageOverwrite();
            case PageId::PROPERTY_NAME:
                return new PageId();
            case PagePath::PROPERTY_NAME:
                return new PagePath();
            case PageCreationDate::PROPERTY_NAME:
                return new PageCreationDate();
            case ModificationDate::PROPERTY_NAME:
                return new ModificationDate();
            case DokuwikiId::DOKUWIKI_ID_ATTRIBUTE:
                return new DokuwikiId();
            case PageUrlPath::PROPERTY_NAME:
                return new PageUrlPath();
            case Locale::PROPERTY_NAME:
                return new Locale();
            case CacheExpirationDate::PROPERTY_NAME:
                return new CacheExpirationDate();
            case \ReplicationDate::getName():
                return new \ReplicationDate();
            case DisqusIdentifier::PROPERTY_NAME:
                return new DisqusIdentifier();
            default:
                $msg = "The metadata ($name) can't be retrieved in the list of metadata. It should be defined";
                LogUtility::msg($msg, LogUtility::LVL_MSG_INFO, self::CANONICAL);
        }
        return null;

    }

    public function toStoreValueOrDefault()
    {
        $value = $this->toStoreValue();
        if ($value !== null) {
            return $value;
        }
        return $this->toStoreDefaultValue();
    }

    public function getChildrenObject()
    {
        if ($this->getChildrenClass() === null) {
            return null;
        }
        if ($this->childrenObject !== null) {
            return $this->childrenObject;
        }
        foreach ($this->getChildrenClass() as $childrenClass) {
            try {
                $this->childrenObject[] = Metadata::toMetadataObject($childrenClass)
                    ->setResource($this->getResource());
            } catch (ExceptionCombo $e) {
                LogUtility::msg("Unable to build the metadata children object: " . $e->getMessage());
            }
        }
        return $this->childrenObject;

    }


    /**
     * @param $store
     * @return $this
     */
    public function setReadStore($store): Metadata
    {
        if ($this->readStore !== null) {
            LogUtility::msg("The read store was already set.");
        }
        if (is_string($store) && !is_subclass_of($store, MetadataStore::class)) {
            throw new ExceptionComboRuntime("The store class ($store) is not a metadata store class");
        }
        $this->readStore = $store;
        return $this;
    }

    /**
     * @param MetadataStore|string $store
     * @return $this
     */
    public function setWriteStore($store): Metadata
    {
        $this->writeStore = $store;
        return $this;
    }

    /**
     * @param mixed $value
     * @return Metadata
     */
    public abstract function setValue($value): Metadata;

    /**
     * @return bool
     * used in the {@link Metadata::buildCheck()} function
     * If the value is null, the {@link Metadata::buildFromReadStore()} will be performed
     * otherwise, it will not
     */
    public abstract function valueIsNotNull(): bool;

    /**
     * If the {@link Metadata::getValue()} is null and if the object was not already build
     * this function will call the function {@link Metadata::buildFromReadStore()}
     */
    protected function buildCheck()
    {
        if (!$this->wasBuild && !$this->valueIsNotNull()) {
            $this->wasBuild = true;
            $this->buildFromReadStore();
        }
    }

    /**
     * Return the store for this metadata
     * By default, this is the {@link ResourceCombo::getReadStoreOrDefault() default resource metadata store}
     *
     * (ie a memory variable or a database)
     * @return MetadataStore|null
     */
    public function getReadStore(): ?MetadataStore
    {
        if ($this->readStore === null) {
            return $this->getResource()->getReadStoreOrDefault();
        }
        if (!$this->readStore instanceof MetadataStore) {
            $this->readStore = MetadataStoreAbs::toMetadataStore($this->readStore, $this->getResource());
        }
        return $this->readStore;
    }

    public function getTab(): ?string
    {
        return $this->getName();
    }

    /**
     * This function sends the object value to the {@link Metadata::getReadStore() store}
     *
     * This function should be used at the end of each setter/adder function
     *
     * @throws ExceptionCombo
     *
     * To persist or commit on disk, you use the {@link MetadataStore::persist()}
     * Because the metadata is stored by resource, the persist function is
     * also made available on the resource level
     *
     */
    public function sendToWriteStore(): Metadata
    {
        $this->getWriteStore()->set($this);
        return $this;
    }

    /**
     * @return string - the name to lookup the value
     * This is the column name in a database or the property name in a key value store
     * It should be unique over all metadata
     */
    public function __toString()
    {
        return $this->getName();
    }

    /** @noinspection PhpMissingReturnTypeInspection */
    public function buildFromReadStore()
    {
        $this->wasBuild = true;
        $metadataStore = $this->getReadStore();
        if ($metadataStore === null) {
            LogUtility::msg("The metadata store is unknown. You need to define a resource or a store to build from it");
            return $this;
        }
        $value = $metadataStore->get($this);
        $this->buildFromStoreValue($value);
        return $this;
    }


    /**
     * @return string - the data type
     * used:
     *   * to store the data in the database
     *   * to select the type of input in a HTML form
     */
    public abstract function getDataType(): string;

    /**
     * @return string - the description (used in tooltip)
     */
    public abstract function getDescription(): string;

    /**
     * @return string - the label used in a form or log
     */
    public abstract function getLabel(): string;

    public function getCanonical(): string
    {
        if ($this->parent !== null) {
            $canonical = $this->parent->getCanonical();
            if ($canonical !== null) {
                return $canonical;
            }
        }
        /**
         * The canonical to page metadata
         */
        return self::CANONICAL;
    }

    /**
     * @return ResourceCombo - The resource
     */
    public function getResource(): ?ResourceCombo
    {
        if ($this->resource !== null) {
            return $this->resource;
        }
        if ($this->parent !== null) {
            return $this->parent->getResource();
        }
        return null;
    }

    /**
     * For which resources is the metadata for
     * @param ResourceCombo $resource
     * @return $this
     */
    public function setResource(ResourceCombo $resource): Metadata
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * @return string - the name use in the store
     * For instance, a {@link PageImagePath} has a unique name of `page-image-path`
     * but when we store it hierarchically, the prefix `page-image` is not needed
     * and becomes simple `path`
     */
    public static function getPersistentName(): string
    {
        return static::getName();
    }

    /**
     * @return string the name of the metadata (property)
     * Used in all store such as database (therefore no minus please)
     * Alphanumeric
     */
    public static abstract function getName(): string;


    /**
     * @return string|array|null the value to be persisted by the store
     * the reverse action is {@link Metadata::setFromStoreValue()}
     */
    public function toStoreValue()
    {
        return $this->getValue();
    }


    /**
     * @return mixed
     * The store default value is used to
     * see if the value set is the same than the default one
     * It this is the case, the data is not stored
     */
    public function toStoreDefaultValue()
    {
        return $this->getDefaultValue();
    }

    /**
     * Data that should persist (this data should be in a backup)
     */
    public const PERSISTENT_METADATA = "persistent";
    /**
     * Data that are derived from other and stored
     */
    public const DERIVED_METADATA = "derived";
    /**
     * A runtime metadata is created for the purpose of a process
     * Example {@link CacheRuntimeDependencies2}
     */
    const RUNTIME_METADATA = "runtime";


    /**
     *
     * Return the type of metadata.
     *   * {@link Metadata::PERSISTENT_METADATA}
     *   * {@link Metadata::DERIVED_METADATA}
     *   * {@link Metadata::RUNTIME_METADATA}
     *   *
     *
     * Backup: Only the {@link Metadata::PERSISTENT_METADATA} got a backup
     *
     * @return string
     *
     * Unfortunately, Dokuwiki makes this distinction only in rendering
     * https://forum.dokuwiki.org/d/19764-how-to-test-a-current-metadata-setting
     * Therefore all metadata are persistent
     *
     * Ie a {@link MetadataDokuWikiStore::CURRENT_METADATA} is only derived
     * in a rendering context. A {@link MetadataDokuWikiStore::PERSISTENT_METADATA} is always stored.
     *
     *
     *
     */
    public abstract function getPersistenceType(): string;


    /**
     * The user can't delete this metadata
     * in the persistent metadata
     */
    const NOT_MODIFIABLE_PERSISTENT_METADATA = [
        PagePath::PROPERTY_NAME,
        PageCreationDate::PROPERTY_NAME,
        ModificationDate::PROPERTY_NAME,
        PageId::PROPERTY_NAME,
        "user",
        "contributor",
        "creator",
        "date",
        action_plugin_combo_metadescription::DESCRIPTION_META_KEY, // Dokuwiki implements it as an array (you can't modify it directly)
        "last_change" // not sure why it's in the persistent data
    ];

    /**
     * Metadata that we can lose
     * because they are generated
     *
     * They still needs to be saved in as persistent metadata
     * otherwise they are just not persisted
     * https://forum.dokuwiki.org/d/19764-how-to-test-a-current-metadata-setting
     */
    const RUNTIME_META = [
        "format",
        "internal", // toc, cache, ...
        "relation",
        PageH1::H1_PARSED,
        LowQualityCalculatedIndicator::LOW_QUALITY_INDICATOR_CALCULATED
    ];


    /**
     * The meta that are modifiable in the form.
     *
     * This meta could be replicated
     *   * in the {@link \syntax_plugin_combo_frontmatter}
     *   * or in the database
     */
    const MUTABLE_METADATA = [
        Canonical::PROPERTY_NAME,
        PageType::PROPERTY_NAME,
        PageH1::PROPERTY_NAME,
        Aliases::PROPERTY_NAME,
        PageImages::PROPERTY_NAME,
        Region::PROPERTY_NAME,
        Lang::PROPERTY_NAME,
        PageTitle::PROPERTY_NAME,
        PagePublicationDate::PROPERTY_NAME,
        ResourceName::PROPERTY_NAME,
        LdJson::PROPERTY_NAME,
        PageLayout::PROPERTY_NAME,
        StartDate::PROPERTY_NAME,
        EndDate::PROPERTY_NAME,
        PageDescription::PROPERTY_NAME,
        DisqusIdentifier::PROPERTY_NAME,
        Slug::PROPERTY_NAME,
        PageKeywords::PROPERTY_NAME,
        CacheExpirationFrequency::PROPERTY_NAME,
        QualityDynamicMonitoringOverwrite::PROPERTY_NAME,
        LowQualityPageOverwrite::PROPERTY_NAME,
    ];


    /**
     * Delete the managed metadata
     * @param $metadataArray - a metadata array
     * @return array - the metadata array without the managed metadata
     */
    public static function deleteMutableMetadata($metadataArray): array
    {
        if (sizeof($metadataArray) === 0) {
            return $metadataArray;
        }
        $cleanedMetadata = [];
        foreach ($metadataArray as $key => $value) {
            if (!in_array($key, Metadata::MUTABLE_METADATA)) {
                $cleanedMetadata[$key] = $value;
            }
        }
        return $cleanedMetadata;
    }

    /**
     * This function will upsert the meta array
     * with a unique property
     * @param $metaArray
     * @param string $uniqueAttribute
     * @param array $attributes
     */
    public static function upsertMetaOnUniqueAttribute(&$metaArray, string $uniqueAttribute, array $attributes)
    {

        foreach ($metaArray as $key => $meta) {
            if (!is_numeric($key)) {
                LogUtility::msg("The passed array is not a meta array because the index are not numeric. Unable to update it.");
                return;
            }
            if (isset($meta[$uniqueAttribute])) {
                $value = $meta[$uniqueAttribute];
                if ($value === $attributes[$uniqueAttribute]) {
                    $metaArray[$key] = $attributes;
                    return;
                }
            }
        }
        $metaArray[] = $attributes;

    }

    /**
     * Delete the runtime if present
     * (They were saved in persistent)
     */
    public static function deleteIfPresent(array &$persistentPageMeta, array $attributeToDeletes): bool
    {
        $unsetWasPerformed = false;
        foreach ($attributeToDeletes as $runtimeMeta) {
            if (isset($persistentPageMeta[$runtimeMeta])) {
                unset($persistentPageMeta[$runtimeMeta]);
                $unsetWasPerformed = true;
            }
        }
        return $unsetWasPerformed;
    }

    /**
     * @return bool can the user change the value
     * In a form, the field will be disabled
     */
    public abstract function getMutable(): bool;


    /**
     * @return array|null The possible values that can take this metadata
     * If null, all, no constraints
     */
    public function getPossibleValues(): ?array
    {
        return null;
    }

    /**
     * An utility function to {@link Metadata::sendToWriteStore()}
     * and {@link MetadataStore::persist()} at the same time in the {@link Metadata::getWriteStore() write store}
     * @throws ExceptionCombo
     */
    public function persist(): Metadata
    {
        $this->sendToWriteStore();
        $this->getWriteStore()->persist();
        return $this;
    }

    /**
     * Build the object from the store value
     *
     * The inverse function is {@link Metadata::toStoreValue()}
     *
     * The function used by {@link Metadata::buildFromReadStore()}
     * to build the value from the {@link MetadataStore::get()}
     * function.
     *
     * The difference between the {@link Metadata::setFromStoreValue()}
     * is that this function should not make any validity check
     * or throw any exception
     *
     * @param $value
     * @return mixed
     */
    public abstract function buildFromStoreValue($value): Metadata;

    /**
     * If you have quality problem to throw, you can use this function
     * instead of {@link Metadata::buildFromStoreValue()}
     * @param $value
     * @return Metadata
     */
    public function setFromStoreValue($value): Metadata
    {
        return $this->buildFromStoreValue($value);
    }


    /**
     * The class of an entity metadata (ie if the metadata has children / is a {@link Metadata::$parent}
     *
     * One id value = one row = one entity
     *
     * @return string|null
     */
    public function getUidClass(): ?string
    {
        if ($this->getChildrenClass() !== null) {
            LogUtility::msg("An entity metadata should define a metadata that store the unique value");
        }
        return null;
    }

    /**
     * The width on a scale of 12 for the form field
     * @return null
     */
    public function getFormControlWidth()
    {
        return null;
    }

    /**
     * @return string[] - the old name if any
     */
    public static function getOldPersistentNames(): array
    {
        return [];
    }

    /**
     * @return mixed - the memory value
     */
    public abstract function getValue();

    public abstract function getDefaultValue();

    /**
     * @return mixed - set the memory value from the store and return ut
     */
    public function getValueFromStore()
    {
        $this->buildFromStoreValue($this->getReadStore()->get($this));
        return $this->getValue();
    }


    public function getValueFromStoreOrDefault()
    {
        $this->buildFromStoreValue($this->getReadStore()->get($this));
        return $this->getValueOrDefault();
    }

    public function getValueOrDefault()
    {

        $value = $this->getValue();
        if ($value === null || $value === "") {
            return $this->getDefaultValue();
        }
        return $value;

    }


    /**
     * @return MetadataStore - the store where the metadata are persist (by default, the {@link Metadata::getReadStore()}
     */
    public function getWriteStore(): MetadataStore
    {
        if ($this->writeStore === null) {
            return $this->getReadStore();
        }
        if (!$this->writeStore instanceof MetadataStore) {
            $this->writeStore = MetadataStoreAbs::toMetadataStore($this->writeStore, $this->getResource());
        }
        return $this->writeStore;
    }

    public function getUidObject(): Metadata
    {
        if ($this->uidObject === null) {
            $this->uidObject = Metadata::toMetadataObject($this->getUidClass())
                ->setResource($this->getResource());
        }
        return $this->uidObject;
    }

}
