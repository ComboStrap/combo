<?php


namespace ComboStrap;

use http\Exception\RuntimeException;

/**
 * Class MetadataBoolean
 * @package ComboStrap
 */
abstract class MetadataBoolean extends Metadata
{

    /**
     * @var bool|null
     */
    protected $value;

    public function getDataType(): string
    {
        return DataType::BOOLEAN_TYPE_VALUE;
    }

    public function getValue(): ?bool
    {
        $this->buildCheck();
        return $this->value;
    }


    /**
     * @param null|boolean $value
     * @return Metadata
     */
    public function setValue($value): Metadata
    {
        if ($value === null) {
            $this->value = null;
            return $this;
        }
        if (!is_bool($value)) {
            throw new ExceptionComboRuntime("The value is not a boolean: " . var_export($value, true));
        }
        $this->value = $value;
        return $this;
    }

    public function toStoreDefaultValue()
    {
        $store = $this->getWriteStore();

        if ($store instanceof MetadataFormDataStore) {
            /**
             * In a boolean form field, the data is returned only when the field is checked.
             *
             * By default, this is not checked, therefore, the default value is when this is not the default.
             * It means that this is the inverse of the default value
             */
            return !$this->getDefaultValue();

        }
        return parent::toStoreDefaultValue();
    }


    /**
     * @return bool|string|null
     */
    public function toStoreValue()
    {

        $store = $this->getWriteStore();
        $value = $this->getValue();

        if ($store instanceof MetadataFormDataStore) {
            return $value;
        }

        if ($store instanceof MetadataDokuWikiStore) {
            // The store modify it
            return $value;
        }

        if ($store->isHierarchicalTextBased()) {
            $value = Boolean::toString($value);
        }

        return $value;

    }

    /**
     * @throws ExceptionCombo
     */
    public
    function setFromStoreValue($value): Metadata
    {
        $value = $this->toBoolean($value);
        return $this->setValue($value);
    }


    public
    function valueIsNotNull(): bool
    {
        return $this->value !== null;
    }

    public
    function buildFromStoreValue($value): Metadata
    {
        $this->value = $this->toBoolean($value);
        return $this;
    }

    private
    function toBoolean($value): ?bool
    {
        /**
         * TODO: There is no validation
         * If the value is not a boolean, the return value is false ...
         */
        return Boolean::toBoolean($value);

    }


}
