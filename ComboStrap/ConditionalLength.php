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
    private $numerator;
    /**
     * @var string
     */
    private $breakpoint;
    private $defaultBreakpoint = "lg";
    private $denominator;


    /**
     * @throws ExceptionBadArgument
     */
    public function __construct($value, $defaultBreakpoint)
    {
        $this->conditionalLength = $value;
        if ($defaultBreakpoint !== null) {
            $this->defaultBreakpoint = $defaultBreakpoint;
        }


        $this->length = $value;
        try {
            $conditionalValue = ConditionalValue::createFrom($value);
            $this->length = $conditionalValue->getValue();
            $this->breakpoint = $conditionalValue->getBreakpoint();
        } catch (ExceptionBadSyntax $e) {
            // not conditional
        }

        try {
            $this->parseAsNumberWithOptionalUnit();
        } catch (ExceptionBadSyntax $e) {
            try {
                $this->parseAsRatio();
            } catch (ExceptionBadSyntax $e) {
                // string only
            }
        }


    }

    /**
     * @throws ExceptionBadArgument
     */
    public static function createFromString(string $widthLength, string $defaultBreakpoint = null): ConditionalLength
    {
        return new ConditionalLength($widthLength, $defaultBreakpoint);
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
                $targetValue = $this->numerator * $remValue;
                break;
            case "px":
            default:
                $targetValue = $this->numerator;
        }
        return DataType::toInteger($targetValue);

    }

    public function getNumerator(): ?float
    {
        return $this->numerator;
    }

    /**
     * @throws ExceptionBadArgument
     */
    public function toColClass(): string
    {

        if ($this->unitInLength !== self::PERCENTAGE) {
            throw new ExceptionBadArgument("A col class can be calculated only from a percentage not from a ({$this->unitInLength})");
        }
        $colsNumber = floor(\syntax_plugin_combo_row::GRID_TOTAL_COLUMNS * $this->numerator / 100);
        $breakpoint = $this->getBreakpointOrDefault();
        if ($breakpoint === "xs") {
            return "col-$colsNumber";
        }
        return "col-{$breakpoint}-$colsNumber";


    }

    /**
     * @throws ExceptionBadArgument
     */
    public function toRowColsClass(): string
    {
        if ($this->unitInLength !== null) {
            throw new ExceptionBadArgument("A row col class can be calculated only from a number without unit ({$this->unitInLength})");
        }
        $colsNumber = intval($this->numerator);
        $breakpoint = $this->getBreakpointOrDefault();
        if ($breakpoint === "xs") {
            return "row-cols-$colsNumber";
        }
        return "row-cols-{$breakpoint}-$colsNumber";
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
        if ($this->numerator !== null) {
            return $this->toPixelNumber() . "px";
        } else {
            if ($this->length === "fit") {
                return "fit-content";
            }
            return $this->length;
        }
    }

    public function getBreakpointOrDefault(): string
    {
        if ($this->breakpoint !== null) {
            return $this->breakpoint;
        }
        return $this->defaultBreakpoint;
    }


    public function getDenominator(): ?float
    {
        return $this->denominator;
    }

    /**
     * @throws ExceptionBadSyntax
     */
    private function parseAsNumberWithOptionalUnit()
    {
        /**
         * Not a numeric alone
         * Does the length value has an unit ?
         */
        preg_match("/^([0-9.]+)([^0-9]*)$/i", $this->length, $matches, PREG_OFFSET_CAPTURE);
        if (sizeof($matches) === 0) {
            throw new ExceptionBadSyntax("Length is not a number with optional unit");
        }
        $localNumber = $matches[1][0];
        try {
            $this->numerator = DataType::toFloat($localNumber);
        } catch (ExceptionBadSyntax $e) {
            // should not happen due to the match but yeah
            throw new ExceptionBadSyntax("The number value ($localNumber) of the length value ($this->length) is not a valid float format.");
        }
        $this->unitInLength = $matches[2][0];
        if ($this->unitInLength === self::PERCENTAGE) {
            $this->denominator = 100;
        } else {
            $this->denominator = 1;
        }

    }

    /**
     * @throws ExceptionBadSyntax
     */
    private function parseAsRatio()
    {
        preg_match("/^([0-9]+):([0-9]+)$/i", $this->length, $matches, PREG_OFFSET_CAPTURE);
        if (sizeof($matches) === 0) {
            throw new ExceptionBadSyntax("Length is not a ratio");
        }
        $numerator = $matches[1][0];
        try {
            $this->numerator = DataType::toFloat($numerator);
        } catch (ExceptionBadSyntax $e) {
            // should not happen due to the match but yeah
            throw new ExceptionBadSyntax("The number value ($numerator) of the length value ($this->length) is not a valid float format.");
        }
        $denominator = $matches[2][0];
        try {
            $this->denominator = DataType::toFloat($denominator);
        } catch (ExceptionBadSyntax $e) {
            // should not happen due to the match but yeah
            throw new ExceptionBadSyntax("The number value ($denominator) of the length value ($this->length) is not a valid float format.");
        }
    }


}
