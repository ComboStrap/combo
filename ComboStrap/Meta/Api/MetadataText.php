<?php


namespace ComboStrap\Meta\Api;

use ComboStrap\DataType;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\LogUtility;

/**
 * Implement all default method for a text metadata (ie small string without any paragraph such as name, title, ...)
 * Class MetadataText
 * @package ComboStrap
 */
abstract class MetadataText extends Metadata
{

    /**
     * @var string|null
     */
    protected $value;


    public static function getDataType(): string
    {
        return DataType::TEXT_TYPE_VALUE;
    }

    public function getValue(): string
    {
        $this->buildCheck();
        if($this->value===null){
            throw new ExceptionNotFound("The value was not found for the metadata ($this)");
        }
        return $this->value;
    }

    public function valueIsNotNull(): bool
    {
        return $this->value !== null;
    }


    /**
     * @param null|string $value
     * @return $this
     * @throws ExceptionBadArgument
     */
    public function setValue($value): Metadata
    {
        if ($value !== null && !is_string($value)) {
            throw new ExceptionBadArgument("The value of the metadata ($this) is not a string", $this->getCanonical());
        }
        $value = trim($value);
        if ($value === "") {
            /**
             * TODO: move this into the function {@link MetadataText::buildFromStoreValue()} ??
             *   form don't return null only empty string
             *   equivalent to null
             */
            return $this;
        }
        $possibleValues = $this->getPossibleValues();
        if ($possibleValues !== null) {
            if (!in_array($value, $possibleValues)) {
                throw new ExceptionBadArgument("The value ($value) for the metadata ({$this->getName()}) is not one of the possible following values: " . implode(", ", $possibleValues) . ".");
            }
        }
        $this->value = $value;
        return $this;

    }

    /**
     * @throws ExceptionCompile
     */
    public function setFromStoreValue($value): Metadata
    {
        return $this->setValue($value);
    }

    public function buildFromStoreValue($value): Metadata
    {
        if ($value === null || $value === "") {
            $this->value = null;
            return $this;
        }
        if (!is_string($value)) {
            LogUtility::msg("This value of a text metadata is not a string. " . var_export($value, true));
            return $this;
        }
        $this->value = $value;
        return $this;
    }

    public function getDefaultValue()
    {
        return null;
    }




}
