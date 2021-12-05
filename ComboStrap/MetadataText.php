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
    protected $wasBuild = false;

    public function getDataType(): string
    {
        return DataType::TEXT_TYPE_VALUE;
    }

    public function getValue(): ?string
    {
        $this->buildCheck();
        return $this->value;
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
        if ($possibleValues !== null) {
            if (!in_array($value, $possibleValues)) {
                throw new ExceptionCombo("The value ($value) for the metadata ({$this->getName()}) is not one of the possible following values: " . implode(", ", $possibleValues) . ".");
            }
        }
        $this->value = $value;
        $this->sendToStore();
        return $this;

    }


    public function buildFromStore(): MetadataText
    {
        try {
            $this->setValue($this->getStoreValue());
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Error while building the value:", $e->getCanonical());
        }
        return $this;
    }

    public function toFormField(): FormMetaField
    {
        $this->buildCheck();
        return parent::toFormField()
            ->setValue($this->getValue(), $this->getDefaultValue());
    }

    protected function buildCheck()
    {
        if (!$this->wasBuild && $this->value === null) {
            $this->wasBuild = true;
            $this->buildFromStore();
        }
    }


}
