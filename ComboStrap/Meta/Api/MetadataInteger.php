<?php


namespace ComboStrap\Meta\Api;


use ComboStrap\DataType;
use ComboStrap\ExceptionCompile;
use ComboStrap\LogUtility;
use ComboStrap\Meta\Api\Metadata;

abstract class MetadataInteger extends Metadata
{

    /**
     * @var int
     */
    protected $value;


    static public function getDataType(): string
    {
        return DataType::INTEGER_TYPE_VALUE;
    }

    public function getValue(): ?int
    {
        $this->buildCheck();
        return $this->value;
    }

    public function valueIsNotNull(): bool
    {
        return $this->value !== null;
    }


    /**
     * @throws ExceptionCompile
     */
    public function setValue($value): Metadata
    {
        $this->value = DataType::toInteger($value);
        return $this;
    }

    /**
     * @throws ExceptionCompile
     */
    public function setFromStoreValue($value): Metadata
    {
        return $this->setValue($value);
    }

    public function setFromStoreValueWithoutException($value): Metadata
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
