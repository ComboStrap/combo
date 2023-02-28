<?php


namespace ComboStrap;


/**
 * Class MetadataArrayStore
 * @package ComboStrap
 * A store based on a single array for a single resource
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
