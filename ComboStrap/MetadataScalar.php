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

    /**
     * @var bool
     */
    protected $wasBuild = false;

    public function toFormField(): FormMetaField
    {
        $this->buildCheck();
        $formField = parent::toFormField();
        $formField->setValue($this->toStoreValue(), $this->toStoreDefaultValue());

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


    public function setFromStoreValue($value)
    {
        return $this->setValue($value);
    }




}
