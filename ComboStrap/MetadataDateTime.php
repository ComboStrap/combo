<?php


namespace ComboStrap;


use DateTime;

abstract class MetadataDateTime extends Metadata
{
    /**
     * @var DateTime
     */
    protected $dateTimeValue;


    /**
     * Helper function for date metadata
     * @return array|string
     * @throws ExceptionNotFound
     */
    public function toStoreValue()
    {

        $this->buildCheck();
        $value = $this->getValue();
        return $this->toPersistentDateTimeUtility($value);

    }

    /**
     * @param DateTime|null $value
     * @return $this
     */
    public function setValue($value): Metadata
    {
        if ($value === null) {
            $this->dateTimeValue = null;
            return $this;
        }
        if (!($value instanceof DateTime)) {
            throw new ExceptionRuntime("The value is not a date time. Value: " . var_export($value, true));
        }
        $this->dateTimeValue = $value;
        return $this;
    }

    /**
     * @throws ExceptionCompile
     */
    public function setFromStoreValue($value): Metadata
    {
        return $this->setValue($this->fromPersistentDateTimeUtility($value));
    }

    /**
     * @throws ExceptionNotFound
     */
    public function toStoreDefaultValue(): string
    {

        $defaultValue = $this->getDefaultValue();
        try {
            return $this->toPersistentDateTimeUtility($defaultValue);
        } catch (ExceptionBadArgument $e) {
            $message = "The date time ($this) has a default value ($defaultValue) that is not valid. Error: {$e->getMessage()}";
            LogUtility::internalError($message);
            throw new ExceptionNotFound($message);
        }

    }

    public function getDataType(): string
    {
        return DataType::DATETIME_TYPE_VALUE;
    }


    public function buildFromReadStore(): MetadataDateTime
    {
        $value = $this->getReadStore()->get($this);
        try {
            $this->dateTimeValue = $this->fromPersistentDateTimeUtility($value);
        } catch (ExceptionCompile $e) {
            LogUtility::msg($e->getMessage(), $this->getCanonical());
        }
        return $this;
    }

    /**
     * @throws ExceptionBadSyntax - if the string is not a date string
     * @throws ExceptionBadArgument - if this is not a string
     */
    protected function fromPersistentDateTimeUtility($value)
    {
        if ($value === null || $value === "") {
            return null;
        }
        if (!is_string($value)) {
            throw new ExceptionBadArgument("This is not a string value");
        }
        return Iso8601Date::createFromString($value)->getDateTime();
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getValue(): DateTime
    {
        $this->buildCheck();
        if ($this->dateTimeValue === null) {
            throw new ExceptionNotFound("The resource does not have this date time ($this)");
        }
        return $this->dateTimeValue;
    }


    /**
     * @throws ExceptionBadArgument
     */
    private function toPersistentDateTimeUtility($value): string
    {
        if ($value === null) {
            throw new ExceptionBadArgument("The passed value is null");
        }
        if (!($value instanceof DateTime)) {
            throw new ExceptionBadArgument("This is not a date time");
        }
        return Iso8601Date::createFromDateTime($value)->toString();
    }

    public function getCanonical(): string
    {
        return "date";
    }

    public function valueIsNotNull(): bool
    {
        return $this->dateTimeValue !== null;
    }

    public function buildFromStoreValue($value): Metadata
    {
        try {
            $this->dateTimeValue = $this->fromPersistentDateTimeUtility($value);
        } catch (ExceptionCompile $e) {
            LogUtility::msg($e->getMessage(), LogUtility::LVL_MSG_ERROR, $e->getCanonical());
        }
        return $this;
    }


}
