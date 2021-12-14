<?php


namespace ComboStrap;

/**
 * Class MetadataArray
 * @package ComboStrap
 * An array, ie list of value metadata
 * Multiple select if the possible values are set
 * Ie:
 *   * keyword1, keyword2
 *   * usage1, usage2, ...
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

    public function getValue(): ?array
    {
        $this->buildCheck();
        return $this->array;
    }

    public function getDefaultValue()
    {
        return null;
    }

    public function getValueOrDefaults(): array
    {
        $value = $this->getValue();
        if ($value !== null) {
            return $value;
        }
        return $this->getDefaultValue();
    }


    public function valueIsNotNull(): bool
    {
        return $this->array !== null;
    }

    public function toStoreValue()
    {
        $this->buildCheck();
        return implode($this->getStringSeparator(), $this->array);
    }


    /**
     * @return string - the separator used when we receive a string
     */
    function getStringSeparator(): string
    {
        return ",";
    }

    /**
     * @throws ExceptionCombo
     */
    public function setFromStoreValue($value): Metadata
    {
        $values = $this->toArrayOrNull($value);
        $possibleValues = $this->getPossibleValues();
        if ($possibleValues !== null) {
            foreach ($values as $value) {
                if (!in_array($value, $possibleValues)) {
                    throw new ExceptionCombo("The value ($value) for ($this) is not a possible value (" . implode(",", $possibleValues) . ")", $this->getCanonical());
                }
            }
        }
        $this->array = $values;
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    protected function toArrayOrNull($value): ?array
    {
        if ($value === null || $value === "") {
            return null;
        }

        /**
         * Array
         */
        if (is_array($value)) {
            return $value;
        }

        /**
         * String
         */
        if (!is_string($value)) {
            LogUtility::msg("The value for $this is not an array, nor a string (value: $value)");
        }
        $stringSeparator = $this->getStringSeparator();
        if ($stringSeparator === null) {
            LogUtility::msg("This array value is a string but has no separator defined. Value: $value");
        }
        return explode($stringSeparator, $value);

    }


    public function buildFromStoreValue($value): Metadata
    {
        try {
            $this->array = $this->toArrayOrNull($value);
        } catch (ExceptionCombo $e) {
            LogUtility::msg($e->getMessage(), $e->getCanonical());
        }
        return $this;

    }
}
