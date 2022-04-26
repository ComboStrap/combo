<?php


namespace ComboStrap;


class ConditionalValue
{

    const CANONICAL = "conditional";
    /**
     * @var string
     */
    private $value;
    /**
     * @var string
     */
    private $breakpoint;

    private static $breakpoints = [
        "xs" => 0,
        "sm" => 576,
        "md" => 768,
        "lg" => 992,
        "xl" => 1200,
        "xxl" => 1400
    ];

    /**
     * ConditionalValue constructor.
     * @throws ExceptionBadSyntax
     */
    public function __construct($value)
    {
        $array = explode("-", $value);
        $sizeof = sizeof($array);
        switch ($sizeof) {
            case 0:
                LogUtility::msg("There is no value in ($value)", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                $this->breakpoint = null;
                $this->value = "";
                break;
            case 1:
                $this->breakpoint = null;
                $this->value = $array[0];
                break;
            case 2:
                $this->breakpoint = strtolower($array[0]);
                if (array_key_exists($this->breakpoint, self::$breakpoints)) {
                    $this->value = $array[1];
                    break;
                }
                $this->breakpoint = strtolower($array[1]);
                if (array_key_exists($this->breakpoint, self::$breakpoints)) {
                    $this->value = $array[0];
                    break;
                }
                throw new ExceptionBadSyntax("The breakpoint ($this->breakpoint) is not a valid breakpoint prefix", self::CANONICAL);
            default:
                throw new ExceptionBadSyntax("The screen conditional value ($value) should have only one separator character `-`", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
        }
    }

    /**
     * @throws ExceptionBadSyntax
     */
    public static function createFrom($value): ConditionalValue
    {
        return new ConditionalValue($value);
    }

    /**
     * @throws ExceptionBadArgument
     */
    public static function checkValidBreakpoint(string $breakpoint)
    {
        if (!array_key_exists($breakpoint, self::$breakpoints)) {
            throw new ExceptionBadArgument("$breakpoint is not a valid breakpoint value");
        }
    }

    public function getBreakpoint(): ?string
    {
        return $this->breakpoint;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getBreakpointSize(): int
    {
        return self::$breakpoints[$this->breakpoint];
    }


}
