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
    public const BREAKPOINTS = array(
        "xs" => 375,
        "sm" => 576,
        "md" => 768,
        "lg" => 992,
        "xl" => 1200,
        "xxl" => 1400
    );

    public const BREAKPOINTS_NAME = array(
        "extra-small" => "xs",
        "small" => "sm",
        "medium" => "md",
        "large" => "lg",
        "extra-large" => "xl",
        "extra-extra-large" => "xxl"
    );

    public static function getPixelFromName(string $name): int
    {

        $breakpointPrefix = self::BREAKPOINTS_NAME[$name];
        if ($breakpointPrefix === null) {
            LogUtility::internalError("The breakpoint name ($name) is unknown, defaulting to md");
            $breakpointPrefix = "md";
        }
        return self::getPixelFromShortName($breakpointPrefix);

    }

    public static function getPixelFromShortName(string $breakpointShortName): int
    {
        $value = self::BREAKPOINTS[$breakpointShortName];
        if ($value !== null) {
            return $value;
        }
        LogUtility::internalError("The breakpoint short name ($$breakpointShortName) is unknown, defaulting to md");
        return 768;
    }
}
