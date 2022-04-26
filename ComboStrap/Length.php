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

    /**
     * @var string - the length value (may be breakpoint conditional)
     */
    private $lengthWithBreakpoint;

    /**
     * @var string - the length value without breakpoint
     */
    private $lengthWithoutBreakpoint;
    /**
     * @var string
     */
    private $unit;
    /**
     * @var float
     */
    private $number;
    /**
     * @var string
     */
    private $breakpoint;


    /**
     * @throws ExceptionBadArgument
     */
    public function __construct($value)
    {
        $this->lengthWithBreakpoint = $value;


        $this->lengthWithoutBreakpoint = $value;
        try {
            $conditionalValue = ConditionalValue::createFrom($value);
            $this->lengthWithoutBreakpoint = $conditionalValue->getValue();
            $this->breakpoint = $conditionalValue->getBreakpoint();
        } catch (ExceptionBadSyntax $e) {
            // not conditional
        }

        try {

            $this->number = DataType::toFloat($this->lengthWithoutBreakpoint);

        } catch (ExceptionBadSyntax $e) {

            /**
             * Not a numeric alone
             * Does the length value has an unit ?
             */
            preg_match("/([0-9.]*)(.*)/i", $this->lengthWithoutBreakpoint, $matches, PREG_OFFSET_CAPTURE);
            if (sizeof($matches) === 0) {
                throw new ExceptionBadArgument("The value ($value) is not a valid length value.");
            }
            $localNumber = $matches[1][0];
            try {
                $this->number = DataType::toFloat($localNumber);
            } catch (ExceptionBadSyntax $e) {
                // should not happen due to the match but yeah
                throw new ExceptionBadArgument("The number value ($localNumber) o the length value ($value) is not a valid float format.");
            }
            $this->unit = $matches[2][0];

        }


    }

    /**
     * @throws ExceptionBadArgument
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

    /**
     * @throws ExceptionBadArgument
     */
    public function toColClass(): string
    {

        if ($this->unit !== self::PERCENTAGE) {
            throw new ExceptionBadArgument("A col class can be calculated only from a percentage not from a ({$this->unit})");
        }
        $colsNumber = floor(\syntax_plugin_combo_row::GRID_TOTAL_COLUMNS * $this->number / 100);
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
        if ($this->unit !== null) {
            throw new ExceptionBadArgument("A row col class can be calculated only from a number without unit ({$this->unit})");
        }
        $colsNumber = intval($this->number);
        if ($this->breakpoint === "xs" || $this->breakpoint === null) {
            return "row-cols-$colsNumber";
        }
        return "row-cols-{$this->breakpoint}-$colsNumber";
    }
}
