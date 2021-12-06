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
     * Helper function for date metadata
     * @return array|string|null
     */
    public function toStoreValue()
    {
        $this->buildCheck();
        $value = $this->dateTimeValue;
        return $this->toPersistentDateTimeUtility($value);

    }

    /**
     * @throws ExceptionCombo
     */
    public function setValue(DateTime $value): MetadataDateTime
    {
        $this->dateTimeValue = $value;
        $this->sendToStore();
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


    public function buildFromStore()
    {
        $value = $this->getStore()->get($this);
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

    public function toFormField(): FormMetaField
    {
        $this->buildCheck();
        return parent::toFormField()
            ->setValue($this->toStoreValue(), $this->toStoreDefaultValue());
    }

    public function getCanonical(): string
    {
        return ":date";
    }

    public function valueIsNotNull(): bool
    {
        return $this->dateTimeValue !== null;
    }


}
