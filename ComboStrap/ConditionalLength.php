<?php

namespace ComboStrap;

/**
 * Represents a length
 */
class ConditionalLength
{


    const RATIONAL_UNITS = [self::FRACTION, self::PERCENTAGE];

    const FRACTION = "fr";
    const PERCENTAGE = "%";

    /**
     * @var string - the length value (may be breakpoint conditional)
     */
    private $conditionalLength;

    /**
     * @var string - the length value without breakpoint
     */
    private $length;
    /**
     * @var string
     */
    private $unitInLength;
    /**
     * The number in a length string
     * @var float
     */
    private $numberInLength;
    /**
     * @var string
     */
    private $breakpoint;


    /**
     * @throws ExceptionBadArgument
     */
    public function __construct($value)
    {
        $this->conditionalLength = $value;


        $this->length = $value;
        try {
            $conditionalValue = ConditionalValue::createFrom($value);
            $this->length = $conditionalValue->getValue();
            $this->breakpoint = $conditionalValue->getBreakpoint();
        } catch (ExceptionBadSyntax $e) {
            // not conditional
        }

        try {

            $this->numberInLength = DataType::toFloat($this->length);

        } catch (ExceptionBadSyntax $e) {

            /**
             * Not a numeric alone
             * Does the length value has an unit ?
             */
            preg_match("/([0-9.]+)(.*)/i", $this->length, $matches, PREG_OFFSET_CAPTURE);
            if (sizeof($matches) === 0) {
                // just a string (ie auto, fit-content, ...)
                return;
            }
            $localNumber = $matches[1][0];
            try {
                $this->numberInLength = DataType::toFloat($localNumber);
            } catch (ExceptionBadSyntax $e) {
                // should not happen due to the match but yeah
                throw new ExceptionBadArgument("The number value ($localNumber) o the length value ($value) is not a valid float format.");
            }
            $this->unitInLength = $matches[2][0];

        }


    }

    /**
     * @throws ExceptionBadArgument
     */
    public static function createFromString(string $widthLength): ConditionalLength
    {
        return new ConditionalLength($widthLength);
    }

    public function getLengthUnit(): ?string
    {
        return $this->unitInLength;
    }

    /**
     * @throws ExceptionBadArgument
     */
    public function toPixelNumber(): int
    {

        switch ($this->unitInLength) {
            case "rem":
                $remValue = Site::getRem();
                $targetValue = $this->numberInLength * $remValue;
                break;
            case "px":
            default:
                $targetValue = $this->numberInLength;
        }
        return DataType::toInteger($targetValue);

    }

    public function getLengthNumber(): ?float
    {
        return $this->numberInLength;
    }

    /**
     * @throws ExceptionBadArgument
     */
    public function toColClass(): string
    {

        if ($this->unitInLength !== self::PERCENTAGE) {
            throw new ExceptionBadArgument("A col class can be calculated only from a percentage not from a ({$this->unitInLength})");
        }
        $colsNumber = floor(\syntax_plugin_combo_row::GRID_TOTAL_COLUMNS * $this->numberInLength / 100);
        if ($this->breakpoint === "xs" || $this->breakpoint === null) {
            return "col-$colsNumber";
        }
        return "col-{$this->breakpoint}-$colsNumber";


    }

    /**
     * @throws ExceptionBadArgument
     */
    public function toRowColsClass(): string
    {
        if ($this->unitInLength !== null) {
            throw new ExceptionBadArgument("A row col class can be calculated only from a number without unit ({$this->unitInLength})");
        }
        $colsNumber = intval($this->numberInLength);
        if ($this->breakpoint === "xs" || $this->breakpoint === null) {
            return "row-cols-$colsNumber";
        }
        return "row-cols-{$this->breakpoint}-$colsNumber";
    }

    public function getBreakpoint(): ?string
    {
        return $this->breakpoint;
    }

    /**
     * @throws ExceptionBadArgument
     */
    public function setBreakpoint(string $breakpoint)
    {
        ConditionalValue::checkValidBreakpoint($breakpoint);
        $this->breakpoint = $breakpoint;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function __toString()
    {
        return $this->conditionalLength;
    }

    /**
     * For CSS a unit is mandatory (not for HTML or SVG attributes)
     * @throws ExceptionBadArgument
     */
    public function toCssLength()
    {
        /**
         * A length value may be also `fit-content`
         * we just check that if there is a number,
         * we add the pixel
         */
        if ($this->numberInLength !== null) {
            return $this->toPixelNumber() . "px";
        } else {
            if ($this->length === "fit") {
                return "fit-content";
            }
            return $this->length;
        }
    }


}
