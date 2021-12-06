<?php


namespace ComboStrap;


use action_plugin_combo_metadescription;
use action_plugin_combo_metagoogle;
use action_plugin_combo_qualitymessage;
use ReplicationDate;
use Slug;

abstract class Metadata
{
    const CANONICAL_PROPERTY = "page:metadata";
    const MUTABLE = "mutable";


    /**
     * The metadata is for this resource
     * @var ResourceCombo $resource
     */
    private $resource;

    /**
     * @var MetadataStore
     */
    private $store;

    /**
     * The metadata may be just not stored
     * CacheExpirationFrequencyMeta constructor.
     * The page is just the scope
     */
    public function __construct()
    {

    }

    public function setStore(MetadataStore $store): Metadata
    {
        $this->store = $store;
        return $this;
    }


    /**
     * Return the store for this metadata
     * By default, this is the {@link ResourceCombo::getDefaultMetadataStore() default resource metadata store}
     *
     * (ie a memory variable or a database)
     * @return MetadataStore|null
     */
    public function getStore(): ?MetadataStore
    {
        if ($this->store === null) {
            return $this->getResource()->getDefaultMetadataStore();
        }
        return $this->store;
    }

    public abstract function getTab();

    /**
     * This function sends the object value to the {@link Metadata::getStore() store}
     *
     * This function should be used at the end of each setter function
     *
     * @throws ExceptionCombo
     *
     * To persist or commit on disk, you use the {@link MetadataStore::persist()}
     * Because the metaadta is stored by resource, the persist function is
     * also made available on the resource level
     *
     */
    public function sendToStore(): Metadata
    {
        if ($this->store === null) {
            throw new ExceptionComboRuntime("The metadata store is not set, you can't persist the metadata ($this)");
        }
        $this->store->set($this);
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
    public function buildFromStore()
    {
        if ($this->store === null) {
            throw new ExceptionComboRuntime("The metadata store is not set, you can't get a value");
        }
        $this->setFromStoreValue($this->store->get($this));
        return $this;
    }

    /**
     * The function used by {@link Metadata::buildFromStore()}
     * to build the value from the {@link MetadataStore::get()}
     * function.
     *
     * The inverse function is {@link Metadata::toStoreValue()}
     * @param $value
     * @return mixed
     */
    public abstract function setFromStoreValue($value);

    /**
     * @return string - the data type
     * used to select the type of input in a HTML form
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
        /**
         * The canonical to page metadata
         */
        return self::CANONICAL_PROPERTY;
    }

    /**
     * @return ResourceCombo - The resource
     */
    public function getResource(): ResourceCombo
    {
        return $this->resource;
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
     * @return string the name of the metadata (property)
     */
    public abstract function getName(): string;

    /**
     * @return string|array|null the value to be persisted by the store
     * the reverse action is {@link Metadata::setFromStoreValue()}
     */
    public abstract function toStoreValue();

    /**
     * @return FormMetaField the field for this metadata
     * TODO: see a HTML form as a datastore where you send a retrieve data ?
     */
    public function toFormField(): FormMetaField
    {
        $field = FormMetaField::create($this->getName())
            ->setType($this->getDataType())
            ->setTab($this->getTab())
            ->setCanonical($this->getCanonical())
            ->setLabel($this->getLabel())
            ->setDescription($this->getDescription())
            ->setMutable($this->getMutable());
        $possibleValues = $this->getPossibleValues();
        if ($possibleValues !== null) {
            $field->setDomainValues($possibleValues);
        }
        return $field;

    }

    /**
     * @param $formData - the data received from the form
     * @return mixed
     * TODO: migrate the HTML form to a store
     */
    public abstract function setFromFormData($formData);


    /**
     * @return mixed
     * The store default value is used to
     * see if the value is the same than the default
     * It this is the case, the data is not stored
     *
     */
    public abstract function toStoreDefaultValue();

    /**
     * Data that should persist (this data should be in a backup)
     */
    public const PERSISTENT_METADATA = "persistent";
    /**
     * Data that are derived from other
     */
    public const DERIVED_METADATA = "derived";
    /**
     * A runtime metadata is created for the purpose of a process
     * Example {@link ReplicationDate}
     */
    const RUNTIME_METADATA = "runtime";

    /**
     *
     * Return the type of metadata.
     *   * {@link Metadata::PERSISTENT_METADATA}
     *   * {@link Metadata::DERIVED_METADATA}
     *   * {@link Metadata::RUNTIME_METADATA}
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
        Path::PATH_ATTRIBUTE,
        PageCreationDate::DATE_CREATED,
        AnalyticsDocument::DATE_MODIFIED,
        PageId::PAGE_ID_ATTRIBUTE,
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
        ReplicationDate::DATE_REPLICATION,
        PageH1::H1_PARSED,
        Page::LOW_QUALITY_INDICATOR_CALCULATED
    ];


    /**
     * The meta that are modifiable in the form.
     *
     * This meta could be replicated
     *   * in the {@link \syntax_plugin_combo_frontmatter}
     *   * or in the database
     */
    const MUTABLE_METADATA = [
        Canonical::CANONICAL_PROPERTY,
        PageType::TYPE_META_PROPERTY,
        PageH1::H1_PROPERTY,
        Aliases::ALIAS_ATTRIBUTE,
        PageImages::IMAGE_META_PROPERTY,
        Region::REGION_META_PROPERTY,
        Lang::LANG_ATTRIBUTES,
        PageTitle::TITLE,
        PagePublicationDate::OLD_META_KEY,
        PagePublicationDate::DATE_PUBLISHED,
        PageName::NAME_PROPERTY,
        LdJson::JSON_LD_META_PROPERTY,
        Page::LAYOUT_PROPERTY,
        action_plugin_combo_metagoogle::OLD_ORGANIZATION_PROPERTY,
        StartDate::DATE_START,
        EndDate::DATE_END,
        action_plugin_combo_metadescription::DESCRIPTION_META_KEY,
        Slug::SLUG_ATTRIBUTE,
        PageKeywords::KEYWORDS_ATTRIBUTE,
        CacheExpirationFrequency::META_CACHE_EXPIRATION_FREQUENCY_NAME,
        action_plugin_combo_qualitymessage::EXECUTE_DYNAMIC_QUALITY_MONITORING_INDICATOR,
        LowQualityPageOverwrite::CAN_BE_LOW_QUALITY_PAGE_INDICATOR,
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
}
