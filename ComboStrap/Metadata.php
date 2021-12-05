<?php


namespace ComboStrap;


use action_plugin_combo_metadescription;
use action_plugin_combo_metagoogle;
use action_plugin_combo_qualitymessage;

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


    public function getStore(): ?MetadataStore
    {
        if ($this->store === null) {
            return $this->getResource()->getDefaultMetadataStore();
        }
        return $this->store;
    }

    public abstract function getTab();

    /**
     * This function is used to send the
     * data to the store
     * (ie a memory variable or a database)
     *
     * This function should be used at the end of each setter function
     *
     * @throws ExceptionCombo
     *
     * To persist on disk, you use the {@link MetadataStore::persist()}
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

    public function __toString()
    {
        return $this->getName();
    }


    public abstract function buildFromStore();

    public abstract function setFromPersistentFormat($value);

    public abstract function getDataType(): string;

    public abstract function getDescription(): string;

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
     * @return string|array|null
     */
    protected function getStoreValue()
    {
        if ($this->store === null) {
            throw new ExceptionComboRuntime("The metadata store is not set, you can't get a value");
        }
        return $this->store->get($this);
    }

    /**
     * @return string the name of the metadata (property)
     */
    public abstract function getName(): string;

    /**
     * @return string|array|null the value to be persisted in the store
     */
    public abstract function toPersistentValue();

    /**
     * @return FormMetaField the field for this metadata
     */
    public function toFormField(): FormMetaField
    {
        return FormMetaField::create($this->getName())
            ->setType($this->getDataType())
            ->setTab($this->getTab())
            ->setCanonical($this->getCanonical())
            ->setLabel($this->getLabel())
            ->setDescription($this->getDescription())
            ->setMutable($this->getMutable());
    }

    /**
     * @param $formData - the data received from the form
     * @return mixed
     */
    public abstract function setFromFormData($formData);


    /**
     * @return mixed
     */
    public abstract function toPersistentDefaultValue();

    /**
     *
     * Return if the metadata value should be backup up (derived value or not)
     *
     * If the value is {@link MetadataDokuWikiStore::PERSISTENT_METADATA}, it's yes
     * If the value is {@link MetadataDokuWikiStore::CURRENT_METADATA}, it's no
     *
     * @return string
     *
     * We are making the difference between a metadata that is derived
     * called {@link MetadataDokuWikiStore::CURRENT_METADATA} for Dokuwiki
     * and that is not called {@link MetadataDokuWikiStore::PERSISTENT_METADATA} for Dokuwiki
     *
     * Unfortunately, Dokuwiki makes this distinction only in rendering
     * https://forum.dokuwiki.org/d/19764-how-to-test-a-current-metadata-setting
     * Therefore all metadata are persistent
     *
     */
    public abstract function getPersistenceType(): string;


    /**
     * The user can't delete this metadata
     * in the persistent metadata
     */
    const NOT_MODIFIABLE_PERSISTENT_METADATA = [
        Path::PATH_ATTRIBUTE,
        AnalyticsDocument::DATE_CREATED,
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
        DatabasePage::DATE_REPLICATION,
        AnalyticsDocument::H1_PARSED,
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
        Page::TYPE_META_PROPERTY,
        AnalyticsDocument::H1,
        Aliases::ALIAS_ATTRIBUTE,
        PageImages::IMAGE_META_PROPERTY,
        Page::REGION_META_PROPERTY,
        Page::LANG_META_PROPERTY,
        PageTitle::TITLE,
        Publication::OLD_META_KEY,
        Publication::DATE_PUBLISHED,
        PageName::NAME_PROPERTY,
        LdJson::JSON_LD_META_PROPERTY,
        Page::LAYOUT_PROPERTY,
        action_plugin_combo_metagoogle::OLD_ORGANIZATION_PROPERTY,
        AnalyticsDocument::DATE_START,
        AnalyticsDocument::DATE_END,
        action_plugin_combo_metadescription::DESCRIPTION_META_KEY,
        Page::SLUG_ATTRIBUTE,
        Page::KEYWORDS_ATTRIBUTE,
        CacheExpirationFrequency::META_CACHE_EXPIRATION_FREQUENCY_NAME,
        action_plugin_combo_qualitymessage::EXECUTE_DYNAMIC_QUALITY_MONITORING_INDICATOR,
        Page::CAN_BE_LOW_QUALITY_PAGE_INDICATOR,
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
     */
    public abstract function getMutable(): bool;


}
