<?php


namespace ComboStrap;


abstract class MetadataJson extends MetadataScalar
{

    /**
     * @var array|null
     */
    private $json;


    /**
     * Helper function for date metadata
     * @return array|null
     */
    public function toStoreValue(): ?array
    {

        $this->buildCheck();
        return $this->json;

    }

    /**
     * @param array|string|null $value
     * @return $this
     * @throws ExceptionCombo
     */
    public function setValue($value): MetadataJson
    {
        $this->json = $this->toInternalValue($value);
        $this->sendToStore();
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public function setFromStoreValue($value): MetadataJson
    {
        $this->setValue($value);
        return $this;
    }

    public function toStoreDefaultValue(): ?string
    {

        return null;

    }


    public function getValue(): ?array
    {
        $this->buildCheck();
        return $this->json;
    }


    public function getDataType(): string
    {
        return DataType::JSON_TYPE_VALUE;
    }

    public function toFormField(): FormMetaField
    {
        $this->buildCheck();
        $formField = parent::toFormField();
        if ($this->json !== null && $this->json !== "") {
            $value = json_encode($this->json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $formField->setValue($value);
        }
        return $formField;

    }

    /**
     * @throws ExceptionCombo
     */
    public function setFromFormData($formData)
    {
        // From the form data, we receive a string
        $value = $formData[$this->getName()];
        $this->setFromStoreValue($value);
        return $this;

    }

    public function valueIsNotNull(): bool
    {
        return $this->json !== null;
    }

    public function buildFromStoreValue($value)
    {
        try {
            $this->json = $this->toInternalValue($value);
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Value in the store is not a valid json. Message:".$e->getMessage(),LogUtility::LVL_MSG_ERROR,$e->getCanonical());
        }
    }

    /**
     * @throws ExceptionCombo
     */
    private function toInternalValue($value)
    {
        if ($value === null || $value === "") {
            // html form return empty string
            return null;
        }
        if (is_string($value)) {
            $json = json_decode($value, true);
            if ($json === null) {
                throw new ExceptionCombo("The string given is not a valid json $value");
            }
            return $json;
        }
        if (!is_array($value)) {
            throw new ExceptionCombo("The json persistent value is not an array, nor a string");
        }
        return $value;
    }


}
