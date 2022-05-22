<?php

namespace ComboStrap;

/**
 * Represents a conditional length / value
 */
class ConditionalLength
{


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
    private $defaultBreakpoint = "sm";
    private $denominator;
    /**
     * @var string
     */
    private $axis;
    /**
     * @var bool
     */
    private $isRatio = false;


    /**
     * @throws ExceptionBadArgument
     */
    public function __construct($value, $defaultBreakpoint)
    {
        $this->conditionalLength = $value;
        if ($defaultBreakpoint !== null) {
            $this->defaultBreakpoint = $defaultBreakpoint;
        }

        /**
         * Breakpoint Suffix
         */
        $this->length = $value;
        try {
            $conditionalValue = ConditionalValue::createFrom($value);
            $this->length = $conditionalValue->getValue();
            $this->breakpoint = $conditionalValue->getBreakpoint();
        } catch (ExceptionBadSyntax $e) {
            // not conditional
        }

        /**
         * Axis prefix
         */
        $axis = substr($value, 0, 2);
        switch ($axis) {
            case "x-":
                $this->axis = "x";
                break;
            case "y-";
                $this->axis = "y";
                break;
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

        $ratio = $this->getRatio();
        if ($ratio > 1) {
            throw new ExceptionBadArgument("The length ratio ($ratio) is greater than 1. It should be less than 1 to get a col class.");
        }
        $colsNumber = floor(\syntax_plugin_combo_grid::GRID_TOTAL_COLUMNS * $this->numerator / $this->denominator);
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

        if ($this->numerator === null) {
            if ($this->getLength() === "auto") {
                if (Bootstrap::getBootStrapMajorVersion() != Bootstrap::BootStrapFiveMajorVersion) {
                    // row-cols-auto is not in 4.0
                    PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot("row-cols-auto");
                }
                return "row-cols-auto";
            }
            throw new ExceptionBadArgument("A row col class can be calculated only from a number ({$this}) or from the `auto` value");
        }

        $colsNumber = intval($this->numerator);
        $totalColumns = \syntax_plugin_combo_grid::GRID_TOTAL_COLUMNS;
        if ($colsNumber > $totalColumns) {
            throw new ExceptionBadArgument("A row col class can be calculated only from a number below $totalColumns ({$this}");
        }
        $breakpoint = $this->getBreakpointOrDefault();
        if ($breakpoint === "xs") {
            return "row-cols-$colsNumber";
        }
        return "row-cols-{$breakpoint}-$colsNumber";
    }

    public
    function getBreakpoint(): ?string
    {
        return $this->breakpoint;
    }


    public
    function getLength()
    {
        return $this->length;
    }

    public
    function __toString()
    {
        return $this->conditionalLength;
    }

    /**
     * For CSS a unit is mandatory (not for HTML or SVG attributes)
     * @throws ExceptionBadArgument
     */
    public
    function toCssLength()
    {
        switch ($this->unitInLength){
            case "vh":
            case "wh":
                return $this->length;
        }
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

    public
    function getBreakpointOrDefault(): string
    {
        if ($this->breakpoint !== null) {
            return $this->breakpoint;
        }
        return $this->defaultBreakpoint;
    }


    public
    function getDenominator(): ?float
    {
        return $this->denominator;
    }

    /**
     * @throws ExceptionBadSyntax
     */
    private
    function parseAsNumberWithOptionalUnit()
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
        } catch (ExceptionBadArgument $e) {
            // should not happen due to the match but yeah
            throw new ExceptionBadSyntax("The number value ($localNumber) of the length value ($this->length) is not a valid float format.");
        }
        $this->denominator = 1;

        $secondMatch = $matches[2][0];
        if ($secondMatch == "") {
            return;
        }
        $this->unitInLength = $secondMatch;
        if ($this->unitInLength === self::PERCENTAGE) {
            $this->denominator = 100;
        }

    }

    /**
     * @throws ExceptionBadSyntax
     */
    private
    function parseAsRatio()
    {
        preg_match("/^([0-9]+):([0-9]+)$/i", $this->length, $matches, PREG_OFFSET_CAPTURE);
        if (sizeof($matches) === 0) {
            throw new ExceptionBadSyntax("Length is not a ratio");
        }
        $numerator = $matches[1][0];
        try {
            $this->numerator = DataType::toFloat($numerator);
        } catch (ExceptionBadArgument $e) {
            // should not happen due to the match but yeah
            throw new ExceptionBadSyntax("The number value ($numerator) of the length value ($this->length) is not a valid float format.");
        }
        $denominator = $matches[2][0];
        try {
            $this->denominator = DataType::toFloat($denominator);
        } catch (ExceptionBadArgument $e) {
            // should not happen due to the match but yeah
            throw new ExceptionBadSyntax("The number value ($denominator) of the length value ($this->length) is not a valid float format.");
        }
        $this->isRatio = true;
    }

    /**
     * @throws ExceptionBadArgument
     */
    public
    function getRatio()
    {
        if (!$this->isRatio()) {
            return null;
        }
        if ($this->numerator == null) {
            return null;
        }
        if ($this->denominator == null) {
            return null;
        }
        if ($this->denominator == 0) {
            throw new ExceptionBadArgument("The denominator of the conditional length ($this) is 0. You can't ask a ratio.");
        }
        return $this->numerator / $this->denominator;
    }

    public function getAxis(): string
    {
        return $this->axis;
    }

    public function getAxisOrDefault(): string
    {
        if ($this->axis !== null) {
            return $this->axis;
        }
        return Align::DEFAULT_AXIS;
    }

    public function isRatio(): bool
    {
        if ($this->getLengthUnit() === self::PERCENTAGE) {
            return true;
        }
        return $this->isRatio;
    }


}
