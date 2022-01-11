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

        $type = $metadata->getDataType();
        switch ($type) {
            case DataType::TABULAR_TYPE_VALUE:
                /**
                 * In a tabular, the children name are
                 */
                $value = null;
                foreach ($metadata->getChildrenObject() as $childrenObject) {
                    $childrenValue = $this->data[$childrenObject::getName()];
                    if ($childrenValue !== null) {
                        $value[$childrenObject::getPersistentName()] = $childrenValue;
                    }
                }
                if ($value !== null) {
                    return $value;
                }
                break;
            default:
                /**
                 * In a form, the name is send, not the {@link Metadata::getPersistentName()}
                 * but with the name
                 */
                $value = $this->data[$metadata::getName()];
                if ($value !== null) {
                    return $value;
                }
        }

        return $default;
    }


}
