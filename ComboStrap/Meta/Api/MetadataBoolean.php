<?php


namespace ComboStrap\Meta\Api;


use ComboStrap\DataType;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionRuntime;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;
use ComboStrap\MetadataFormDataStore;

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

    static public function getDataType(): string
    {
        return DataType::BOOLEAN_TYPE_VALUE;
    }

    /**
     * @return bool
     * @throws ExceptionNotFound
     */
    public function getValue(): bool
    {
        $this->buildCheck();
        if ($this->value === null) {
            throw new ExceptionNotFound("No ($this) found");
        }
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
            throw new ExceptionRuntime("The value is not a boolean: " . var_export($value, true));
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
            try {
                return !$this->getDefaultValue();
            } catch (ExceptionNotFound $e) {
                return null;
            }

        }
        return parent::toStoreDefaultValue();
    }


    /**
     * @return bool|string|null
     */
    public function toStoreValue()
    {

        $store = $this->getWriteStore();
        try {
            $value = $this->getValue();
        } catch (ExceptionNotFound $e) {
            return null;
        }

        if ($store instanceof MetadataFormDataStore) {
            return $value;
        }

        if ($store instanceof MetadataDokuWikiStore) {
            // The store modify it
            return $value;
        }

        if ($store->isHierarchicalTextBased()) {
            $value = DataType::toBooleanString($value);
        }

        return $value;

    }

    /**
     * @throws ExceptionCompile
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
    function setFromStoreValueWithoutException($value): Metadata
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
        return DataType::toBoolean($value);

    }


}
