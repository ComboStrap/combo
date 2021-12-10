<?php


namespace ComboStrap;

/**
 * Class MetadataBoolean
 * @package ComboStrap
 */
abstract class MetadataBoolean extends MetadataScalar
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
     * @throws ExceptionCombo
     */
    public function setValue(?bool $value): MetadataBoolean
    {
        $this->value = $value;
        $this->sendToStore();
        return $this;
    }

    /**
     * @return bool|string|null
     */
    public function toStoreValue()
    {
        $store = $this->getStore();
        $value = $this->getValue();
        if ($store->isHierarchicalTextBased()) {
            $value = Boolean::toString($value);
        }
        return $value;

    }

    /**
     * @throws ExceptionCombo
     */
    public function setFromStoreValue($value)
    {
        $value = $this->toBoolean($value);
        return $this->setValue($value);
    }


    public function toFormField(): FormMetaField
    {
        $this->buildCheck();
        $formField = parent::toFormField();
        /**
         * In a boolean form field, the data is returned only when the field is checked.
         *
         * By default, this is not checked, therefore, the default value is when this is not the default.
         * It means that this is the inverse of the default value
         */
        $defaultValue = Boolean::toString(!$this->getDefaultValue());
        $formField->setValue($this->toStoreValue(), $defaultValue);

        return $formField;
    }


    public function valueIsNotNull(): bool
    {
        return $this->value !== null;
    }

    public function buildFromStoreValue($value): Metadata
    {
        $this->value = $this->toBoolean($value);
        return $this;
    }

    private function toBoolean($value): ?bool
    {
        /**
         * TODO: There is no validation
         * If the value is not a boolean, the return value is false ...
         */
        return Boolean::toBoolean($value);

    }


}
