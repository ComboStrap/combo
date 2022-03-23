<?php


namespace ComboStrap;


abstract class MetadataInteger extends Metadata
{

    /**
     * @var int
     */
    protected $value;


    public function getDataType(): string
    {
        return DataType::INTEGER_TYPE_VALUE;
    }

    public function getValue(): ?string
    {
        $this->buildCheck();
        return $this->value;
    }

    public function valueIsNotNull(): bool
    {
        return $this->value !== null;
    }


    /**
     * @throws ExceptionCombo
     */
    public function setValue($value): Metadata
    {
        $this->value = self::toInt($value);
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public static function toInt($value): int
    {
        if (!is_numeric($value)) {
            throw new ExceptionCombo("The value is not a numeric");
        }
        if (!is_int($value)) {
            throw new ExceptionCombo("The value is not an integer");
        }
        return intval($value);
    }

    /**
     * @throws ExceptionCombo
     */
    public function setFromStoreValue($value): Metadata
    {
        return $this->setValue($value);
    }

    public function buildFromStoreValue($value): Metadata
    {
        if ($value === null || $value === "") {
            $this->value = null;
            return $this;
        }
        if (!is_string($value)) {
            LogUtility::msg("This value of a text metadata is not a string. " . var_export($value, true));
            return $this;
        }
        $this->value = $value;
        return $this;
    }

    public function getDefaultValue(): int
    {
        return 0;
    }

}
