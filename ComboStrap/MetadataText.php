<?php


namespace ComboStrap;

/**
 * Implement all default method for a text metadata (ie small string without any paragraph such as name, title, ...)
 * Class MetadataText
 * @package ComboStrap
 */
abstract class MetadataText extends MetadataScalar
{

    /**
     * @var string|null
     */
    protected $value;


    public function getDataType(): string
    {
        return DataType::TEXT_TYPE_VALUE;
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
    public function setValue(?string $value): MetadataText
    {
        if ($value !== null && !is_string($value)) {
            throw new ExceptionCombo("The value is not a string");
        }
        $value = trim($value);
        if ($value === "") {
            // form don't return null only empty string
            $value = null;
        }
        $possibleValues = $this->getPossibleValues();
        if ($possibleValues !== null && $value !== null && $value !== "") {
            if (!in_array($value, $possibleValues)) {
                throw new ExceptionCombo("The value ($value) for the metadata ({$this->getName()}) is not one of the possible following values: " . implode(", ", $possibleValues) . ".");
            }
        }
        $this->value = $value;
        return $this;

    }

    /**
     * @throws ExceptionCombo
     */
    public function setFromStoreValue($value)
    {
        return $this->setValue($value);
    }

    public function buildFromStoreValue($value): Metadata
    {
        $this->value = $value;
        return $this;
    }


}
