<?php


namespace ComboStrap\Meta\Api;

use ComboStrap\DataType;
use ComboStrap\ExceptionCompile;
use ComboStrap\Json;
use ComboStrap\LogUtility;

/**
 * Class MetadataJson
 * @package ComboStrap
 * A text that can be saved as array
 * TODO: Should be based on an array
 */
abstract class MetadataJson extends MetadataText
{


    /**
     * Helper function for date metadata
     * @throws ExceptionCompile
     */
    public function toStoreValue()
    {
        $value = parent::toStoreValue();

        if ($this->getReadStore()->isHierarchicalTextBased()) {
            return Json::createFromString($value)->toArray();
        }

        return $value;

    }


    static public function getDataType(): string
    {
        return DataType::JSON_TYPE_VALUE;
    }


    public function setFromStoreValueWithoutException($value): Metadata
    {
        try {
            parent::setFromStoreValueWithoutException($this->toInternalValue($value));
        } catch (ExceptionCompile $e) {
            LogUtility::msg("Value in the store is not a valid json. Message:" . $e->getMessage(), LogUtility::LVL_MSG_ERROR, $e->getCanonical());
        }
        return $this;
    }

    /**
     * @throws ExceptionCompile
     */
    private function toInternalValue($value)
    {
        if ($value === null) {
            // html form return empty string
            return null;
        }
        if (is_array($value)) {
            return Json::createFromArray($value)->toPrettyJsonString();
        }
        if (!is_string($value)) {
            throw new ExceptionCompile("The json persistent value is not an array, nor a string");
        }
        // the json is normalized when setting to verify
        return $value;
    }


}
