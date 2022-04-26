<?php

namespace ComboStrap;

/**
 * Represents a length
 */
class Length
{


    const RATIONAL_UNITS = [self::FRACTION, self::PERCENTAGE];

    const FRACTION = "fr";
    const PERCENTAGE = "%";

    private $length;
    /**
     * @var string
     */
    private $unit;
    /**
     * @var float
     */
    private $number;

    /**
     * @throws ExceptionBadSyntax
     */
    public function __construct($length)
    {
        $this->length = $length;

        try {

            $this->number = DataType::toFloat($length);

        } catch (ExceptionBadSyntax $e) {

            /**
             * Not a numeric alone
             * Does the length value has an unit ?
             */
            preg_match("/([0-9.]*)(.*)/i", $this->length, $matches, PREG_OFFSET_CAPTURE);
            if (sizeof($matches) === 0) {
                throw new ExceptionBadSyntax("The value ($length) is not a valid length value.");
            }
            $localNumber = $matches[1][0];
            try {
                $this->number = DataType::toFloat($localNumber);
            } catch (ExceptionBadSyntax $e) {
                // should not happen due to the match but yeah
                throw new ExceptionBadSyntax("The number value ($localNumber) o the length value ($length) is not a valid float format.");
            }
            $this->unit = $matches[2][0];

        }


    }

    /**
     * @throws ExceptionBadSyntax
     */
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
    public function toPixelNumber(): int
    {

        switch ($this->unit) {
            case "rem":
                $remValue = Site::getRem();
                $targetValue = $this->number * $remValue;
                break;
            case "px":
            default:
                $targetValue = $this->number;
        }
        return DataType::toInteger($targetValue);

    }

    public function getNumber(): float
    {
        return $this->number;
    }
}
