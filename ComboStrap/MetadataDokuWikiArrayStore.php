<?php


namespace ComboStrap;


use ComboStrap\Meta\Api\MetadataStore;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;

/**
 * Class MetadataArrayStore
 * @package ComboStrap
 * Represents the current dokuwiki array that can be read with {@link p_read_metadata()}
 */
class MetadataDokuWikiArrayStore extends MetadataSingleArrayStore
{


    public static function getOrCreateFromResource(ResourceCombo $resourceCombo, array $dokuWikiData = []): MetadataStore
    {
        if (isset($dokuWikiData[MetadataDokuWikiStore::CURRENT_METADATA])) {
            $dokuWikiData = $dokuWikiData[MetadataDokuWikiStore::CURRENT_METADATA];
        }
        return new MetadataDokuWikiArrayStore($resourceCombo, $dokuWikiData);
    }

}
