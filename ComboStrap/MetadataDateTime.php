<?php


namespace ComboStrap;


use DateTime;
use RuntimeException;

abstract class MetadataDateTime extends MetadataScalar
{
    /**
     * @var DateTime
     */
    private $dateTimeValue;


    /**
     * Helper function for date metadata
     * @return array|string|null
     */
    public function toStoreValue()
    {

        $this->buildCheck();
        $value = $this->getValue();
        return $this->toPersistentDateTimeUtility($value);

    }


    public function setValue(?DateTime $value): MetadataDateTime
    {
        $this->dateTimeValue = $value;
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public function setFromStoreValue($value): MetadataDateTime
    {
        $this->setValue($this->fromPersistentDateTimeUtility($value));
        return $this;
    }

    public function toStoreDefaultValue(): ?string
    {

        return $this->toPersistentDateTimeUtility($this->getDefaultValue());

    }

    public function getDataType(): string
    {
        return DataType::DATETIME_TYPE_VALUE;
    }


    public function buildFromStore(): MetadataDateTime
    {
        $value = $this->getStore()->get($this);
        try {
            $this->dateTimeValue = $this->fromPersistentDateTimeUtility($value);
        } catch (ExceptionCombo $e) {
            LogUtility::msg($e->getMessage(), $this->getCanonical());
        }
        return $this;
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

    public function getCanonical(): string
    {
        return ":date";
    }

    public function valueIsNotNull(): bool
    {
        return $this->dateTimeValue !== null;
    }

    public function buildFromStoreValue($value): Metadata
    {
        try {
            $this->dateTimeValue = $this->fromPersistentDateTimeUtility($value);
        } catch (ExceptionCombo $e) {
            LogUtility::msg($e->getMessage(), LogUtility::LVL_MSG_ERROR, $e->getCanonical());
        }
        return $this;
    }


}
