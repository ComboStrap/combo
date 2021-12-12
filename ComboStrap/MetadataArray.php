<?php


namespace ComboStrap;

/**
 * Class MetadataArray
 * @package ComboStrap
 * An array, ie list of value metadata
 */
abstract class MetadataArray extends Metadata
{

    /**
     * @var array|null
     */
    protected $array;


    /**
     * @throws ExceptionCombo
     */
    public function setValue(?array $array): MetadataArray
    {
        $this->array = $array;
        $this->sendToStore();
        return $this;
    }

    public function getDataType(): string
    {
        return DataType::ARRAY_TYPE_VALUE;
    }

    public function getValues(): ?array
    {
        $this->buildCheck();
        return $this->array;
    }

    abstract function getDefaultValues();

    public function getValueOrDefaults(): array
    {
        $value = $this->getValues();
        if ($value !== null) {
            return $value;
        }
        return $this->getDefaultValues();
    }


    public function valueIsNotNull(): bool
    {
        return $this->array !== null;
    }

    public function toStoreValue()
    {
        $this->buildCheck();
        return $this->array;
    }

    public function toStoreDefaultValue()
    {
        return $this->getDefaultValues();
    }

    /**
     * @return string - the separator used when we receive a string
     */
    function getStringSeparator(): string
    {
        return ",";
    }

    public function buildFromStoreValue($value): Metadata
    {
        /**
         * Array
         */
        if (is_array($value)) {
            $this->array = $value;
            return $this;
        }

        /**
         * String
         */
        if (!is_string($value)) {
            LogUtility::msg("The value is not an array, nor a string");
        }
        $stringSeparator = $this->getStringSeparator();
        if ($stringSeparator === null) {
            LogUtility::msg("This array value is a string but has no separator defined. Value: $value");
        }
        $this->array = explode($stringSeparator, $value);

        return $this;

    }
}
