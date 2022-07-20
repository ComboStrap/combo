<?php

namespace ComboStrap;

use http\Exception\RuntimeException;

class Breakpoint
{


    /**
     *
     * When the container query are a thing, we may change the breakpoint
     * https://twitter.com/addyosmani/status/1524039090655481857
     * https://groups.google.com/a/chromium.org/g/blink-dev/c/gwzxnTJDLJ8
     *
     */
    private const BREAKPOINTS_TO_PIXELS = array(
        self::XS => 375,
        self::SM => 576,
        self::MD => 768,
        self::LG => 992,
        self::XL => 1200,
        self::XXL => 1400
    );

    public const BREAKPOINTS_LONG_TO_SHORT_NAMES = array(
        self::EXTRA_SMALL_NAME => self::XS,
        self::BREAKPOINT_SMALL_NAME => self::SM,
        self::BREAKPOINT_MEDIUM_NAME => self::MD,
        self::BREAKPOINT_LARGE_NAME => self::LG,
        self::BREAKPOINT_EXTRA_LARGE_NAME => self::XL,
        self::EXTRA_EXTRA_LARGE_NAME => self::XXL,
        self::NEVER_NAME => self::FLUID
    );
    public const BREAKPOINT_EXTRA_LARGE_NAME = "extra-large";
    public const BREAKPOINT_LARGE_NAME = "large";
    public const BREAKPOINT_SMALL_NAME = "small";
    public const BREAKPOINT_MEDIUM_NAME = "medium";
    /**
     * Breakpoint naming
     */
    public const EXTRA_SMALL_NAME = "extra-small";
    public const NEVER_NAME = "never";
    public const EXTRA_EXTRA_LARGE_NAME = "extra-extra-large";
    public const MD = "md";
    const LG = "lg";
    const XL = "xl";
    const XXL = "xxl";
    const FLUID = "fluid";
    const SM = "sm";
    const XS = "xs";

    private string $shortBreakpointName;

    public function __construct(string $shortBreakpointName)
    {
        $this->shortBreakpointName = $shortBreakpointName;
    }


    /**
     * @param $name - the short name, the name used by bootstrap
     * @return Breakpoint
     */
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
            $breakpoints[] = Breakpoint::createFromShortName($shortName);
        }
        $breakpoints[] = Breakpoint::createFromShortName(self::FLUID);
        return $breakpoints;
    }

    /**
     * @throws ExceptionInfinite
     */
    public function getWidth(): int
    {
        if (in_array($this->shortBreakpointName, [Breakpoint::NEVER_NAME, Breakpoint::FLUID])) {
            // 100% on all viewport
            // infinite with
            throw new ExceptionInfinite();
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
