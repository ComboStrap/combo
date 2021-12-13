<?php


namespace ComboStrap;

/**
 * Class MetadataFormStore
 * @package ComboStrap
 * Represents a data array of a post from an HTML form
 * ie formData
 */
class MetadataFormDataStore extends MetadataSingleArrayStore
{


    public static function createForPage(ResourceCombo $page, array $formData = []): MetadataFormDataStore
    {
        return new MetadataFormDataStore($page,$formData);
    }

}
