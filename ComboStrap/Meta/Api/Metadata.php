<?php


namespace ComboStrap\Meta\Api;


use action_plugin_combo_metadescription;
use ComboStrap\CacheExpirationDate;
use ComboStrap\CacheExpirationFrequency;
use ComboStrap\Canonical;
use ComboStrap\DataType;
use ComboStrap\ExceptionRuntimeInternal;
use ComboStrap\ExecutionContext;
use ComboStrap\Label;
use ComboStrap\DisqusIdentifier;
use ComboStrap\DokuwikiId;
use ComboStrap\EndDate;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionRuntime;
use ComboStrap\FirstImage;
use ComboStrap\FirstRasterImage;
use ComboStrap\FirstSvgIllustration;
use ComboStrap\FeaturedIcon;
use ComboStrap\Lang;
use ComboStrap\LdJson;
use ComboStrap\Lead;
use ComboStrap\Locale;
use ComboStrap\LogUtility;
use ComboStrap\LowQualityCalculatedIndicator;
use ComboStrap\LowQualityPageOverwrite;
use ComboStrap\Meta\Field\Aliases;
use ComboStrap\Meta\Field\AliasPath;
use ComboStrap\Meta\Field\AliasType;
use ComboStrap\Meta\Field\AncestorImage;
use ComboStrap\Meta\Field\FacebookImage;
use ComboStrap\Meta\Field\FeaturedImage;
use ComboStrap\Meta\Field\SocialCardImage;
use ComboStrap\Meta\Field\FeaturedRasterImage;
use ComboStrap\Meta\Field\FeaturedSvgImage;
use ComboStrap\Meta\Field\PageH1;
use ComboStrap\Meta\Field\PageTemplateName;
use ComboStrap\Meta\Field\TwitterImage;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;
use ComboStrap\MetadataMutation;
use ComboStrap\ModificationDate;
use ComboStrap\CreationDate;
use ComboStrap\PageDescription;
use ComboStrap\PageId;
use ComboStrap\Meta\Field\PageImagePath;
use ComboStrap\Meta\Field\PageImages;
use ComboStrap\PageImageUsage;
use ComboStrap\PageKeywords;
use ComboStrap\PageLevel;
use ComboStrap\PagePath;
use ComboStrap\PagePublicationDate;
use ComboStrap\PageTitle;
use ComboStrap\PageType;
use ComboStrap\PageUrlPath;
use ComboStrap\PluginUtility;
use ComboStrap\QualityDynamicMonitoringOverwrite;
use ComboStrap\References;
use ComboStrap\Meta\Field\Region;
use ComboStrap\ReplicationDate;
use ComboStrap\ResourceCombo;
use ComboStrap\ResourceName;
use ComboStrap\Slug;
use ComboStrap\StartDate;
use ComboStrap\Web\UrlEndpoint;

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
    protected bool $wasBuild = false;

    /**
     * The metadata is for this resource
     * @var ResourceCombo $resource
     */
    private $resource;

    /**
     * @var MetadataStore|string - string is the class
     */
    private $readStore;

    /**
     * @var MetadataStore|string
     */
    private $writeStore;
    /**
     * @var Metadata
     */
    private $uidObject;

    /**
     * @var Metadata[] - the runtime children
     */
    private array $childrenObject;

    /**
     * @var Metadata|null - the parent with runtime metadata
     */
    private ?Metadata $parent;


    /**
     * The metadata may be just not stored
     * The page is just the scope
     */
    public function __construct(Metadata $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getParent(): Metadata
    {
        if ($this->parent === null) {
            throw new ExceptionNotFound("No parent");
        }
        return $this->parent;
    }

    /**
     * The class string of the child/columns metadata
     * @return string[];
     */
    public static function getChildrenClass(): array
    {
        return [];
    }


    /**
     * @return array|mixed|string|null
     * Store value may returns null as they may be stored
     * Be careful
     */
    public
    function toStoreValueOrDefault()
    {

        $value = $this->toStoreValue();
        if ($value !== null) {
            return $value;
        }
        return $this->toStoreDefaultValue();

    }

    /**
     * @return Metadata[]
     */
    public function getChildrenObject(): array
    {
        if (static::getChildrenClass() === []) {
            return [];
        }
        if (isset($this->childrenObject)) {
            return $this->childrenObject;
        }
        foreach (static::getChildrenClass() as $childrenClass) {
            try {
                $this->childrenObject[] = MetadataSystem::toMetadataObject($childrenClass)
                    ->setResource($this->getResource());
            } catch (ExceptionCompile $e) {
                LogUtility::msg("Unable to build the metadata children object: " . $e->getMessage());
            }
        }
        return $this->childrenObject;

    }

    /**
     * @return bool - true if single value, false if an array
     */
    public
    function isScalar(): bool
    {

        try {
            $parent = $this->getParent();
            if ($parent::getDataType() === DataType::TABULAR_TYPE_VALUE) {
                return false;
            }
        } catch (ExceptionNotFound $e) {
            // no parent
        }
        return true;

    }




    /**
     * @param $store
     * @return $this
     */
    public
    function setReadStore($store): Metadata
    {
        if (isset($this->readStore)) {
            LogUtility::msg("The read store was already set.");
        }
        if (is_string($store) && !is_subclass_of($store, MetadataStore::class)) {
            throw new ExceptionRuntime("The store class ($store) is not a metadata store class");
        }
        $this->readStore = $store;
        return $this;
    }

    /**
     * @param MetadataStore|string $store
     * @return $this
     */
    public
    function setWriteStore($store): Metadata
    {
        $this->writeStore = $store;
        return $this;
    }

    /**
     * @param mixed $value
     * @return Metadata
     */
    public

    abstract function setValue($value): Metadata;

    /**
     * @return bool
     * Used in the {@link Metadata::buildCheck()} function
     * If the value is null, the {@link Metadata::buildFromReadStore()} will be performed
     * otherwise, it will not.
     * Why ? because the value may have been set before building.
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
     * @return MetadataStore
     */
    public function getReadStore(): ?MetadataStore
    {
        if (!isset($this->readStore)) {
            return $this->getResource()->getReadStoreOrDefault();
        }
        if (!$this->readStore instanceof MetadataStore) {
            $this->readStore = MetadataStoreAbs::toMetadataStore($this->readStore, $this->getResource());
        }
        return $this->readStore;
    }

    public static function getTab(): ?string
    {
        return static::getName();
    }

    /**
     * This function sends the object value to the memory {@link Metadata::getWriteStore() store}
     *
     * This function should be used at the end of each setter/adder function
     *
     * To persist or commit on disk, you use the {@link MetadataStore::persist()}
     * Because the metadata is stored by resource, the persist function is
     * also made available on the resource level
     *
     * @throws ExceptionBadArgument - if the value cannot be persisted
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
        $this->setFromStoreValueWithoutException($value);
        return $this;
    }


    /**
     * @return string - the data type
     * used:
     *   * to store the data in the database
     *   * to select the type of input in a HTML form
     */
    public static abstract function getDataType(): string;

    /**
     * @return string - the description (used in tooltip)
     */
    public static abstract function getDescription(): string;

    /**
     * @return string - the label used in a form or log
     */
    public static abstract function getLabel(): string;

    /**
     * @return string - the short link name
     */
    public static function getCanonical(): string
    {
        return static::getName();
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
     * @return string - the storage name use in the store
     *
     * For instance, a {@link PageImagePath} has a unique name of `page-image-path`
     * but when we store it hierarchically, the prefix `page-image` is not needed
     * and becomes simple `path`
     * The metadata is stored in a table `page-image` with the column `path`.
     */
    public static function getPersistentName(): string
    {
        return static::getName();
    }

    /**
     * @return string the unique name of the metadata (property)
     *
     *
     * It's the hierachical representation of the {@link self::getPersistentName()}
     *
     */
    public static abstract function getName(): string;


    /**
     * @return null|string|array the value to be persisted to the {@link self::setWriteStore()}
     * the reverse action is {@link Metadata::setFromStoreValue()}
     *
     * Null may be returned (no exception is thrown)
     * as this is a possible storage value
     */
    public function toStoreValue()
    {
        try {
            return $this->getValue();
        } catch (ExceptionNotFound $e) {
            /**
             * The only case when we return null
             * and not throw an exception
             * because it may be stored
             */
            return null;
        }
    }


    /**
     * @return mixed
     * The store default value is used to
     * see if the value set is the same than the default one
     * It this is the case, the data is not stored
     */
    public function toStoreDefaultValue()
    {
        try {
            return $this->getDefaultValue();
        } catch (ExceptionNotFound $e) {
            /**
             * We don't throw an null exception here because
             * null may be stored
             */
            return null;
        }
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
     * in a rendering context. A {@link MetadataDokuWikiStore::PERSISTENT_DOKUWIKI_KEY} is always stored.
     *
     *
     *
     */
    public abstract static function getPersistenceType(): string;


    /**
     * The user can't delete this metadata
     * in the persistent metadata
     */
    const NOT_MODIFIABLE_PERSISTENT_METADATA = [
        PagePath::PROPERTY_NAME,
        CreationDate::PROPERTY_NAME,
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
        LowQualityCalculatedIndicator::PROPERTY_NAME
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
            try {
                if (!MetadataSystem::getForName($key)->isMutable()) {
                    $cleanedMetadata[$key] = $value;
                }
            } catch (ExceptionNotFound $e) {
                continue;
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
     *
     *
     * The meta that are modifiable by the user (in the form, ...)
     *
     * Usage:
     * * In a form, the field will be disabled
     * * Replication: this meta could be replicated
     *   * in the {@link \syntax_plugin_combo_frontmatter}
     *   * or in the database
     *
     * If you deprecate a metadata, you should set this value to false
     * and set the replacement to true (where the replacement takes the value of the deprecated metadata)
     */
    public abstract static function isMutable(): bool;

    /**
     * @return bool if true the metadata will be shown on the meta manager form
     * Note that a non-mutable meta may also be shown for information purpose
     */
    public static function isOnForm(): bool
    {
        return false;
    }


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
     *
     * @throws ExceptionBadArgument - if the value can not be persisted
     */
    public function persist(): Metadata
    {

        $oldValue = $this->getWriteStore()->get($this);
        $this->sendToWriteStore();
        $this->getWriteStore()->persist();
        $actualValue = $this->toStoreValue();
        $attribute = $this->getName();

        MetadataMutation::notifyMetadataMutation($attribute, $oldValue, $actualValue, $this->getResource()->getPathObject());

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
     * or throw any compile exception.
     *
     * It's a commodity function that should be use by the developer
     * when it sets a known value and therefore does not expect a quality error
     *
     * @param $value
     * @return mixed
     */
    public abstract function setFromStoreValueWithoutException($value): Metadata;

    /**
     * Set a value from the {@link self::getReadStore()}
     *
     * If you have quality problem to throw, you can use this function
     * instead of {@link Metadata::setFromStoreValueWithoutException()}
     *
     * @param $value
     * @return Metadata
     */
    public function setFromStoreValue($value): Metadata
    {
        return $this->setFromStoreValueWithoutException($value);
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
        if (count(static::getChildrenClass()) >= 1) {
            LogUtility::msg("An entity metadata should define a metadata that store the unique value");
        }
        return null;
    }

    /**
     * The width on a scale of 12 for the form field
     * @return null
     */
    public static function getFormControlWidth()
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
     * @throws ExceptionNotFound - if the value is null
     */
    public abstract function getValue();

    /**
     * @return mixed
     * @throws ExceptionNotFound
     */
    public abstract function getDefaultValue();

    /**
     * @return mixed - set the memory value from the store and return ut
     * @deprecated use the {@link self::toStoreValue()} instead
     */
    public function getValueFromStore()
    {
        return $this->toStoreValue();
    }


    /**
     * @throws ExceptionNotFound
     */
    public function getValueFromStoreOrDefault()
    {
        $this->setFromStoreValueWithoutException($this->getReadStore()->get($this));
        return $this->getValueOrDefault();
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getValueOrDefault()
    {

        try {
            $value = $this->getValue();
            if ($value === "") {
                return $this->getDefaultValue();
            }
            return $value;
        } catch (ExceptionNotFound $e) {
            return $this->getDefaultValue();
        }

    }


    /**
     * @return MetadataStore - the store where the metadata are persist (by default, the {@link Metadata::getReadStore()}
     */
    public function getWriteStore(): MetadataStore
    {
        if ($this->writeStore === null) {
            return $this->getReadStore();
        }
        /**
         * WriteStore may be just the string class name
         */
        if (!($this->writeStore instanceof MetadataStore)) {
            $this->writeStore = MetadataStoreAbs::toMetadataStore($this->writeStore, $this->getResource());
        }
        return $this->writeStore;
    }

    /**
     * @throws ExceptionBadArgument - if the class string of the children are not good
     */
    public function getUidObject(): Metadata
    {
        if ($this->uidObject === null) {

            $this->uidObject = MetadataSystem::toMetadataObject($this->getUidClass())
                ->setResource($this->getResource());

        }
        return $this->uidObject;
    }

}
