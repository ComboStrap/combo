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


    public static function getOrCreateFromResource(ResourceCombo $resourceCombo, array $formData = []): MetadataStore
    {
        return new MetadataFormDataStore($resourceCombo, $formData);
    }


    public function get(Metadata $metadata, $default = null)
    {
        $this->checkResource($metadata->getResource());
        /**
         * In a form, the name is send, not the {@link Metadata::getPersistentName()}
         */
        $value = $this->data[$metadata::getName()];
        if ($value !== null) {
            return $value;
        }
        return $default;
    }


}
