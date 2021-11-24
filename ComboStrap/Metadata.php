<?php


namespace ComboStrap;


use action_plugin_combo_metadescription;
use action_plugin_combo_metagoogle;
use action_plugin_combo_qualitymessage;
use syntax_plugin_combo_disqus;

class Metadata
{

    /**
     * The user can't delete this metadata
     * in the persistent metadata
     */
    const NOT_MODIFIABLE_PERSISTENT_METADATA = [
        Analytics::PATH,
        Analytics::DATE_CREATED,
        Analytics::DATE_MODIFIED,
        Page::PAGE_ID_ATTRIBUTE,
        "contributor",
        "creator",
        "date",
        action_plugin_combo_metadescription::DESCRIPTION_META_KEY, // Dokuwiki implements it as an array (you can't modify it directly)
        "last_change" // not sure why it's in the persistent data
    ];

    /**
     * Metadata that we can lose
     * because they are generated
     */
    const RUNTIME_META = [
        "format",
        "internal", // toc, cache, ...
        "relation",
        DatabasePage::DATE_REPLICATION,
        Analytics::H1_PARSED,
        Page::LOW_QUALITY_INDICATOR_CALCULATED
    ];


    /**
     * The meta that are modifiable in the form.
     *
     * This meta could be replicated
     *   * in the {@link \syntax_plugin_combo_frontmatter}
     *   * or in the database
     */
    const FORM_MANAGED_METADATA = [
        Page::CANONICAL_PROPERTY,
        Page::TYPE_META_PROPERTY,
        Analytics::H1,
        Page::ALIAS_ATTRIBUTE,
        Page::IMAGE_META_PROPERTY,
        Page::REGION_META_PROPERTY,
        Page::LANG_META_PROPERTY,
        Analytics::TITLE,
        Publication::OLD_META_KEY,
        Publication::DATE_PUBLISHED,
        Analytics::NAME,
        action_plugin_combo_metagoogle::JSON_LD_META_PROPERTY,
        Page::LAYOUT_PROPERTY,
        action_plugin_combo_metagoogle::OLD_ORGANIZATION_PROPERTY,
        Analytics::DATE_START,
        Analytics::DATE_END,
        action_plugin_combo_metadescription::DESCRIPTION_META_KEY,
        Page::CAN_BE_LOW_QUALITY_PAGE_INDICATOR,
        Page::SLUG_ATTRIBUTE,
        action_plugin_combo_qualitymessage::EXECUTE_DYNAMIC_QUALITY_MONITORING_INDICATOR,
        Page::KEYWORDS_ATTRIBUTE,
        CacheManager::META_CACHE_EXPIRATION_FREQUENCY_NAME
    ];

    /**
     * Current metadata / runtime metadata / calculated metadata
     * The data may be deleted
     * https://www.dokuwiki.org/devel:metadata#metadata_persistence
     */
    public const CURRENT_METADATA = "current";
    /**
     * Persistent metadata (data that should be in a backup)
     *
     * They are used as the default of the current metadata
     * and is never cleaned
     *
     * https://www.dokuwiki.org/devel:metadata#metadata_persistence
     */
    public const PERSISTENT_METADATA = "persistent";
    const TYPES = [self::CURRENT_METADATA, self::PERSISTENT_METADATA];
    /**
     * The canonical to page metadata
     */
    public const CANONICAL = "page:metadata";

    /**
     * Delete the managed metadata
     * @param $metadataArray - a metadata array
     * @return array - the metadata array without the managed metadata
     */
    public static function deleteManagedMetadata($metadataArray): array
    {

        if (sizeof($metadataArray) === 0) {
            return $metadataArray;
        }
        $cleanedMetadata = [];
        foreach ($metadataArray as $key => $value) {
            if (!in_array($key, Metadata::FORM_MANAGED_METADATA)) {
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

}
