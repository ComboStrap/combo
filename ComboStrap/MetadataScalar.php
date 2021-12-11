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


      

    public abstract function getValue();

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


    public abstract function getDefaultValue();


    public
    function toStoreDefaultValue()
    {
        return $this->getDefaultValue();
    }

    public
    function toStoreValue()
    {

        $store = $this->getStore();
        if ($store instanceof MetadataFormDataStore) {

            $field = FormMetaField::create($this->getName())
                ->setType($this->getDataType())
                ->setTab($this->getTab())
                ->setCanonical($this->getCanonical())
                ->setLabel($this->getLabel())
                ->setDescription($this->getDescription())
                ->setMutable($this->getMutable())
                ->addValue($this->getValue(),$this->getDefaultValue());
            $possibleValues = $this->getPossibleValues();
            if ($possibleValues !== null) {
                $field->setDomainValues($possibleValues);
            }
            return $field
                ->toAssociativeArray();

        }

        return $this->getValue();
    }


    public function setFromStoreValue($value)
    {
        return $this->setValue($value);
    }


}
