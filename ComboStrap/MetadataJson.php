<?php


namespace ComboStrap;


abstract class MetadataJson extends MetadataScalar
{

    /**
     * @var array|null
     */
    private $json;
    /**
     * @var bool
     */
    private $wasBuild = false;


    /**
     * Helper function for date metadata
     * @return array|null
     */
    public function toPersistentValue(): ?array
    {

        $this->buildCheck();
        return $this->json;

    }

    public function setValue(?array $value): MetadataJson
    {
        $this->json = $value;
        $this->persistToFileSystem();
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public function setFromPersistentFormat($value): MetadataJson
    {
        if ($value === null || $value === "") {
            $this->setValue(null);
            return $this;
        }
        if (is_string($value)) {
            $json = json_decode($value, true);
            if ($json === null) {
                throw new ExceptionCombo("The string given is not a valid json $value");
            }
            $this->setValue($json);
            return $this;
        }
        if (!is_array($value)) {
            throw new ExceptionCombo("The json persistent value is not an array, nor a string");
        }
        $this->setValue($value);
        return $this;
    }

    public function toPersistentDefaultValue(): ?string
    {

        return null;

    }


    public function buildFromFileSystem()
    {
        $this->json = $this->getFileSystemValue();
    }


    public function getValue(): ?array
    {
        $this->buildCheck();
        return $this->json;
    }

    private function buildCheck()
    {
        if (!$this->wasBuild && $this->json === null) {
            $this->wasBuild = true;
            $this->json = $this->getFileSystemValue();
        }
    }

    public function getDataType(): string
    {
        return DataType::JSON_TYPE_VALUE;
    }

    public function toFormField(): FormMetaField
    {

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
        $this->setFromPersistentFormat($value);
        return $this;

    }


}
