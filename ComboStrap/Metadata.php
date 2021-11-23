<?php


namespace ComboStrap;


use action_plugin_combo_metadescription;
use action_plugin_combo_metagoogle;
use action_plugin_combo_qualitymessage;
use syntax_plugin_combo_disqus;

class Metadata
{

    const NOT_MODIFIABLE_METADATA = [
        Analytics::PATH,
        Analytics::DATE_CREATED,
        Analytics::DATE_MODIFIED,
        Page::PAGE_ID_ATTRIBUTE,
        "contributor",
        "creator",
        "date",
        action_plugin_combo_metadescription::DESCRIPTION_META_KEY, // Dokuwiki implements it as an array (you can't be modified directly)
        "format",
        "last_change",
        "user",
        "internal", // toc, cache, ...
        "relation"
    ];

    /**
     * The managed meta
     * This meta could be replicated
     *   * in the {@link \syntax_plugin_combo_frontmatter}
     *   * or in the database
     */
    const MANAGED_METADATA = [
        Page::CANONICAL_PROPERTY,
        Page::TYPE_META_PROPERTY,
        Analytics::H1,
        Page::ALIAS_ATTRIBUTE,
        Page::IMAGE_META_PROPERTY,
        Page::REGION_META_PROPERTY,
        Page::LANG_META_PROPERTY,
        Analytics::TITLE,
        syntax_plugin_combo_disqus::META_DISQUS_IDENTIFIER,
        Publication::OLD_META_KEY,
        Publication::DATE_PUBLISHED,
        Analytics::NAME,
        action_plugin_combo_metagoogle::JSON_LD_META_PROPERTY,
        Page::LAYOUT_PROPERTY,
        action_plugin_combo_metagoogle::OLD_ORGANIZATION_PROPERTY,
        Analytics::DATE_START,
        Analytics::DATE_END,
        Page::PAGE_ID_ATTRIBUTE,
        action_plugin_combo_metadescription::DESCRIPTION_META_KEY,
        Page::CAN_BE_LOW_QUALITY_PAGE_INDICATOR,
        Page::SLUG_ATTRIBUTE,
        action_plugin_combo_qualitymessage::EXECUTE_DYNAMIC_QUALITY_MONITORING_INDICATOR
    ];

    /**
     * Current metadata
     * will not persist through
     * the next metadata rendering.
     * https://www.dokuwiki.org/devel:metadata#metadata_persistence
     */
    public const CURRENT_METADATA = "current";
    /**
     * Persistent metadata
     * will persist through
     * the next metadata rendering.
     *
     * They are used as the default of the current metadata
     * and is never cleaned
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
     * @param $metadataArray -  a metadata array
     * @return array - the metadata array without the managed metadata
     */
    public static function deleteManagedMetadata($metadataArray): array
    {

        if (sizeof($metadataArray) === 0) {
            return $metadataArray;
        }
        $cleanedMetadata = [];
        foreach ($metadataArray as $key => $value) {
            if (!in_array($key, Metadata::MANAGED_METADATA)) {
                $cleanedMetadata[$key] = $value;
            }
        }
        return $cleanedMetadata;
    }

}
