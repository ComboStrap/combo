<?php


namespace ComboStrap\Meta\Api;


use ComboStrap\DataType;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\LogUtility;

/**
 * @package ComboStrap
 * A list of value metadata
 *   * Multiple select if the possible values are set
 *   * Text with a {@link  MetadataMultiple::getStringSeparator() separator}
 * Ie:
 *   * keyword1, keyword2
 *   * usage1, usage2, ...
 *
 * By default, the data type is text, if number, the implementation should overwrite the {@link MetadataMultiple::getDataType()}
 */
abstract class MetadataMultiple extends Metadata
{

    /**
     * @var array|null
     */
    protected ?array $array = null;


    /**
     * @param null|array $value
     * @return Metadata
     * @throws ExceptionCompile
     */
    public function setValue($value): Metadata
    {
        if ($value === null) {
            $this->array = $value;
            return $this;
        }
        if (!is_array($value)) {
            throw new ExceptionCompile("The value is not an array. Value: " . var_export($value, true));
        }
        $this->array = $value;
        return $this;
    }

    static     public function getDataType(): string
    {
        return DataType::TEXT_TYPE_VALUE;
    }

    public function getValue(): array
    {
        $this->buildCheck();
        if ($this->array === null) {
            throw new ExceptionNotFound("No multiple values was found");
        }
        return $this->array;
    }

    public function getDefaultValue()
    {
        throw new ExceptionNotFound("No default multiples value");
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getValueOrDefaults(): array
    {
        try {
            return $this->getValue();
        } catch (ExceptionNotFound $e) {
            return $this->getDefaultValue();
        }
    }


    public function valueIsNotNull(): bool
    {
        return $this->array !== null;
    }

    public function toStoreValue()
    {
        $this->buildCheck();
        if ($this->array === null) {
            return null;
        }
        return implode($this->getStringSeparator(), $this->array);
    }

    public function toStoreDefaultValue()
    {
        try {
            $defaultValue = $this->getDefaultValue();
        } catch (ExceptionNotFound $e) {
            return null;
        }
        return implode($this->getStringSeparator(), $defaultValue);
    }


    /**
     * @return string - the separator used when we receive or store/send an element
     */
    function getStringSeparator(): string
    {
        return ",";
    }

    /**
     * @throws ExceptionBadArgument
     */
    public function setFromStoreValue($value): Metadata
    {
        $values = $this->toArrayOrNull($value);
        if ($values === null) {
            return $this;
        }
        $possibleValues = $this->getPossibleValues();
        if ($possibleValues !== null) {
            foreach ($values as $value) {
                if (!in_array($value, $possibleValues)) {
                    throw new ExceptionBadArgument("The value ($value) for ($this) is not a possible value (" . implode(",", $possibleValues) . ")", $this->getCanonical());
                }
            }
        }
        $this->array = $values;
        return $this;
    }

    /**
     * @throws ExceptionBadArgument
     */
    protected function toArrayOrNull($value): ?array
    {
        /**
         * Empty String is the default for HTML form
         */
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
            throw new ExceptionBadArgument("The value for $this is not an array, nor a string (value: $value)", get_class($this));
        }
        $stringSeparator = $this->getStringSeparator();
        return explode($stringSeparator, $value);

    }


    public function setFromStoreValueWithoutException($value): Metadata
    {
        try {
            $this->array = $this->toArrayOrNull($value);
        } catch (ExceptionCompile $e) {
            LogUtility::msg($e->getMessage(), $e->getCanonical());
        }
        return $this;

    }
}
