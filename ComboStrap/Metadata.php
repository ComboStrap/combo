<?php


namespace ComboStrap;


use action_plugin_combo_metagoogle;
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
        "description",
        "format",
        "last_change",
        "user",
        "internal", // toc, cache, ...
        "relation"
    ];

    /**
     * The managed meta with the exception of
     * the {@link action_plugin_combo_metadescription::DESCRIPTION_META_KEY description}
     * because it's already managed by dokuwiki in description['abstract']
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
        action_plugin_combo_metagoogle::OLD_ORGANIZATION_PROPERTY
    ];
    public const CURRENT_METADATA = "current";
    public const PERSISTENT_METADATA = "persistent";
    const TYPES = [self::CURRENT_METADATA, self::PERSISTENT_METADATA];
    /**
     * The canonical to page metadata
     */
    public const CANONICAL = "page:metadata";

}
