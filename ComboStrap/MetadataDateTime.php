<?php


namespace ComboStrap;


use DateTime;

abstract class MetadataDateTime extends MetadataScalar
{
    /**
     * @var DateTime
     */
    private $dateTimeValue;
    /**
     * @var bool
     */
    private $wasBuild = false;


    /**
     * Helper function for date metadata
     * @return string|null
     */
    public function toPersistentValue(): ?string
    {
        $this->buildCheck();
        $value = $this->dateTimeValue;
        return $this->toPersistentDateTimeUtility($value);

    }

    public function setValue(DateTime $value): MetadataDateTime
    {
        $this->dateTimeValue = $value;
        $this->persistToFileSystem();
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public function setFromPersistentFormat($value): MetadataDateTime
    {
        $this->setValue($this->fromPersistentDateTimeUtility($value));
        return $this;
    }

    public function toPersistentDefaultValue(): ?string
    {

        return $this->toPersistentDateTimeUtility($this->getDefaultValue());

    }

    public function getDataType(): string
    {
        return DataType::DATETIME_TYPE_VALUE;
    }


    public function buildFromFileSystem()
    {
        $value = $this->getFileSystemValue();
        try {
            $this->dateTimeValue = $this->fromPersistentDateTimeUtility($value);
        } catch (ExceptionCombo $e) {
            LogUtility::msg($e->getMessage(), $this->getCanonical());
        }
    }

    /**
     * @throws ExceptionCombo
     */
    private function fromPersistentDateTimeUtility($value)
    {
        if ($value === null || $value === "") {
            return null;
        }
        if (!is_string($value)) {
            throw new ExceptionCombo("This is not a string value");
        }
        return Iso8601Date::createFromString($value)->getDateTime();
    }

    public function getValue(): ?DateTime
    {
        $this->buildCheck();
        return $this->dateTimeValue;
    }

    private function buildCheck()
    {
        if (!$this->wasBuild && $this->dateTimeValue === null) {
            $this->wasBuild = true;
            $value = $this->getFileSystemValue();
            try {
                $this->dateTimeValue = $this->fromPersistentDateTimeUtility($value);
            } catch (ExceptionCombo $e) {
                LogUtility::msg("An error has occurred. The value ($value) cannot be read as a date time. Message: " . $e->getMessage());
            }
        }
    }

    private function toPersistentDateTimeUtility($value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (!($value instanceof DateTime)) {
            throw new ExceptionComboRuntime("This is not a date time");
        }
        return Iso8601Date::createFromDateTime($value)->toString();
    }




}
