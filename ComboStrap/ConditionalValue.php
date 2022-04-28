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
        $lastIndex = strrpos($value, "-");
        if ($lastIndex === false) {
            $this->breakpoint = null;
            $this->value = $value;
            return;
        }
        $breakpoint = substr($value, $lastIndex + 1);
        if (array_key_exists($breakpoint, self::$breakpoints)) {
            $this->breakpoint = $breakpoint;
            $this->value = substr($value, 0, $lastIndex);
            return;
        }
        // Old the breakpoints may be in the middle
        $parts = explode("-", $value);
        $valueFromParts = [];
        foreach ($parts as $key => $part) {
            if (array_key_exists($part, self::$breakpoints)) {
                $this->breakpoint = $part;
            } else {
                $valueFromParts[] = $part;
            }
        }
        if ($this->breakpoint === null) {
            $this->breakpoint = null;
            $this->value = $value;
            return;
        }
        $this->value = implode("-", $valueFromParts);
        LogUtility::warning("The breakpoint conditional value format ($value) will be deprecated in the next releases. It should be written ($this->value-$this->breakpoint)");

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
