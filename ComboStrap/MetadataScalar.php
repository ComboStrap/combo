<?php


namespace ComboStrap;


/**
 * Class MetadataScalar
 * @package ComboStrap
 *
 * A metadata that holds only one value
 * ie:
 *   Yes: text, boolean, numeric, ...
 *   No: array, collection
 *
 * The children needs to implements the {@link MetadataScalar::getValue()}
 * and {@link MetadataScalar::getDefaultValue()} function
 */
abstract class MetadataScalar extends Metadata
{


    public function getValueFromStore()
    {
        $this->buildFromStoreValue($this->getStore()->get($this));
        return $this->getValue();
    }

    public function getValueFromStoreOrDefault()
    {
        $this->buildFromStoreValue($this->getStore()->get($this));
        return $this->getValueOrDefault();
    }

    public function getValueOrDefault()
    {

        $value = $this->getValue();
        if ($value === null || $value === "") {
            return $this->getDefaultValue();
        }
        return $value;

    }


    public
    function toStoreDefaultValue()
    {
        return $this->getDefaultValue();
    }

    public
    function toStoreValue()
    {
        return $this->getValue();
    }


    public function setFromStoreValue($value): Metadata
    {
        return $this->setValue($value);
    }


}
