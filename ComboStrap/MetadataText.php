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
    private $value;
    private $wasBuild;

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
            // form don't return null
            $value = null;
        }
        $this->value = $value;
        $this->persistToFileSystem();
        return $this;
    }



    public function buildFromFileSystem(): MetadataText
    {
        try {
            $this->setValue($this->getFileSystemValue());
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

    private function buildCheck()
    {
        if (!$this->wasBuild && $this->value === null) {
            $this->wasBuild = true;
            $this->value = $this->getFileSystemValue();
        }
    }


}
