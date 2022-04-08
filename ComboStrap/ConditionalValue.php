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

    private $breakpoints = [
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
                $this->breakpoint = "";
                $this->value = "";
                break;
            case 1:
                $this->breakpoint = "";
                $this->value = $array[0];
                break;
            case 2:
                $this->breakpoint = strtolower($array[0]);
                if (!key_exists($this->breakpoint, $this->breakpoints)) {
                    throw new ExceptionBadSyntax("The breakpoint ($this->breakpoint) is not a valid breakpoint prefix", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                }
                $this->value = $array[1];
                break;
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

    public function getBreakpoint(): string
    {
        return $this->breakpoint;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getBreakpointSize(): int
    {
        return $this->breakpoints[$this->breakpoint];
    }


}
