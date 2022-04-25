<?php

namespace ComboStrap;

/**
 * Represents a length
 */
class Length
{


    private $length;
    /**
     * @var string
     */
    private $unit;
    /**
     * @var float
     */
    private $value;

    /**
     * @throws ExceptionBadSyntax
     */
    public function __construct($length)
    {
        $this->length = $length;

        try {

            $this->value = DataType::toFloat($length);

        } catch (ExceptionBadSyntax $e) {

            /**
             * Not a numeric alone
             * Does the length value has an unit ?
             */
            try {
                preg_match("/[a-z]/i", $this->length, $matches, PREG_OFFSET_CAPTURE);
                if (sizeof($matches) > 0) {
                    $firstPosition = $matches[0][1];
                    $this->unit = strtolower(substr($this->length, $firstPosition));
                    $stringValue = substr($this->length, 0, $firstPosition);
                    $this->value = DataType::toFloat($stringValue);
                }
            } catch (ExceptionBadSyntax $e) {
                throw new ExceptionBadSyntax("The value ($length) is not a valid length value.");
            }
        }


    }

    public static function createFromString(string $widthLength): Length
    {
        return new Length($widthLength);
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    /**
     * @throws ExceptionBadArgument
     */
    public function toPixelValue(): int
    {

        switch ($this->unit) {
            case "rem":
                $remValue = Site::getRem();
                $targetValue = $this->value * $remValue;
                break;
            case "px":
            default:
                $targetValue = $this->value;
        }
        return DataType::toInteger($targetValue);

    }
}
