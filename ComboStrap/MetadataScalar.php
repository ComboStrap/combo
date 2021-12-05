<?php


namespace ComboStrap;


/**
 * Class MetadataScalar
 * @package ComboStrap
 * A metadata that holds only one value
 */
abstract class MetadataScalar extends Metadata
{

    public function toFormField(): FormMetaField
    {

        $formField = parent::toFormField();
        $persistentValue = $this->toStoreValue();
        if ($persistentValue !== null) {
            $formField->setValue($persistentValue, $this->toStoreDefaultValue());
        }
        return $formField;

    }

    public function setFromFormData($formData)
    {
        $value = $formData[$this->getName()];
        $this->setFromStoreValue($value);
        return $this;
    }

    public abstract function getValue();

    public abstract function getDefaultValue();

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


    public
    function setFromStoreValue($value)
    {

        return $this->setValue($value);

    }


}
