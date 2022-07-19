<?php

namespace ComboStrap;

class Breakpoint
{


    /**
     * When the container query are a thing, we may change the breakpoint
     * https://twitter.com/addyosmani/status/1524039090655481857
     * https://groups.google.com/a/chromium.org/g/blink-dev/c/gwzxnTJDLJ8
     *
     */
    private const BREAKPOINTS_TO_PIXELS = array(
        "xs" => 375,
        "sm" => 576,
        "md" => 768,
        "lg" => 992,
        "xl" => 1200,
        "xxl" => 1400
    );

    public const BREAKPOINTS_LONG_TO_SHORT_NAMES = array(
        self::BREAKPOINT_EXTRA_SMALL_NAME => "xs",
        self::BREAKPOINT_SMALL_NAME => "sm",
        self::BREAKPOINT_MEDIUM_NAME => "md",
        self::BREAKPOINT_LARGE_NAME => "lg",
        self::BREAKPOINT_EXTRA_LARGE_NAME => "xl",
        self::BREAKPOINT_EXTRA_EXTRA_LARGE_NAME => "xxl",
        self::BREAKPOINT_NEVER_NAME => "never"
    );
    public const BREAKPOINT_EXTRA_LARGE_NAME = "extra-large";
    public const BREAKPOINT_LARGE_NAME = "large";
    public const BREAKPOINT_SMALL_NAME = "small";
    public const BREAKPOINT_MEDIUM_NAME = "medium";
    /**
     * Breakpoint naming
     */
    public const BREAKPOINT_EXTRA_SMALL_NAME = "extra-small";
    public const BREAKPOINT_NEVER_NAME = "never";
    public const BREAKPOINT_EXTRA_EXTRA_LARGE_NAME = "extra-extra-large";
    private string $shortBreakpointName;

    public function __construct(string $shortBreakpointName)
    {
        $this->shortBreakpointName = $shortBreakpointName;
    }


    public static function createFromShortName($name): Breakpoint
    {
        return new Breakpoint($name);
    }

    public static function createFromLongName($longName): Breakpoint
    {
        $breakpointShortName = self::BREAKPOINTS_LONG_TO_SHORT_NAMES[$longName];
        if ($breakpointShortName === null) {
            LogUtility::internalError("The breakpoint name ($longName) is unknown, defaulting to md");
            $breakpointShortName = "md";
        }
        return new Breakpoint($breakpointShortName);
    }

    /**
     * @return Breakpoint[];
     */
    public static function getBreakpoints(): array
    {
        $breakpoints = [];
        foreach (array_keys(self::BREAKPOINTS_TO_PIXELS) as $shortName) {
            if ($shortName !== self::BREAKPOINT_NEVER_NAME) {
                $breakpoints[] = Breakpoint::createFromShortName($shortName);
            }
        }
        return $breakpoints;
    }

    public function getWidth(): int
    {
        if ($this->shortBreakpointName === Breakpoint::BREAKPOINT_NEVER_NAME) {
            return 9999;
        }
        $value = self::BREAKPOINTS_TO_PIXELS[$this->shortBreakpointName];
        if ($value !== null) {
            return $value;
        }
        LogUtility::internalError("The breakpoint short name ($this->shortBreakpointName) is unknown, defaulting to md");
        return 768;
    }

    public function getShortName(): string
    {
        return $this->shortBreakpointName;
    }

    public function __toString()
    {
        return $this->shortBreakpointName;
    }


}
