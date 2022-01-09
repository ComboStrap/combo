<?php


namespace ComboStrap;

/**
 * Class MetadataJson
 * @package ComboStrap
 * A text that can be saved as array
 */
abstract class MetadataJson extends MetadataText
{


    /**
     * Helper function for date metadata
     * @throws ExceptionCombo
     */
    public function toStoreValue()
    {
        $value = parent::toStoreValue();

        if ($this->getReadStore()->isHierarchicalTextBased()) {
            return Json::createFromString($value)->toArray();
        }

        return $value;

    }


    public function getDataType(): string
    {
        return DataType::JSON_TYPE_VALUE;
    }


    /**
     * @throws ExceptionCombo
     */
    public function setFromFormData($formData)
    {
        // From the form data, we receive a string
        $value = $formData[$this->getName()];
        // validate that this a json by normalizing it
        $value = Json::createFromString($value)->toPrettyJsonString();
        $this->setValue($value);
        return $this;

    }


    public function buildFromStoreValue($value): Metadata
    {
        try {
            parent::buildFromStoreValue($this->toInternalValue($value));
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Value in the store is not a valid json. Message:" . $e->getMessage(), LogUtility::LVL_MSG_ERROR, $e->getCanonical());
        }
        return $this;
    }

    /**
     * @throws ExceptionCombo
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
            throw new ExceptionCombo("The json persistent value is not an array, nor a string");
        }
        // the json is normalized when setting to verify
        return $value;
    }


}
