<?php


namespace ComboStrap\Meta\Api;


use ComboStrap\DataType;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionRuntime;
use ComboStrap\ExceptionRuntimeInternal;
use ComboStrap\Iso8601Date;
use ComboStrap\LogUtility;
use DateTime;

abstract class MetadataDateTime extends Metadata
{

    /**
     * @var DateTime|null
     * may be null as this is a stored value
     * but throw a not found exception when the value is null
     */
    protected ?DateTime $dateTimeValue = null;


    /**
     * Helper function for date metadata
     * @return string|array (may be an array for dokuwiki ie {@link CreationDate::toStoreValue()} for instance
     */
    public function toStoreValue()
    {

        $this->buildCheck();
        try {
            $value = $this->getValue();
        } catch (ExceptionNotFound $e) {
            return null;
        }
        try {
            return $this->toPersistentDateTimeUtility($value);
        } catch (ExceptionBadArgument $e) {
            throw ExceptionRuntimeInternal::withMessageAndError("The date time should have been checked on set. This error should not happen", $e);
        }

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


    public function toStoreDefaultValue(): ?string
    {

        try {
            $defaultValue = $this->getDefaultValue();
        } catch (ExceptionNotFound $e) {
            return null;
        }
        try {
            return $this->toPersistentDateTimeUtility($defaultValue);
        } catch (ExceptionBadArgument $e) {
            $message = "The date time ($this) has a default value ($defaultValue) that is not valid. Error: {$e->getMessage()}";
            LogUtility::internalError($message);
            return null;
        }

    }

    public static function getDataType(): string
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
     * @throws ExceptionBadArgument - if the value is not valid
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

    public static function getCanonical(): string
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
