<?php

namespace ComboStrap;

/**
 * Static method of color calculation
 */
class ColorSystem
{

    const CANONICAL = "color-system";

    /**
     * Return a color suitable for reading (that has a good ratio)
     * @param ColorRgb $colorRgb
     * @return ColorRgb
     */
    public static function toTextColor(ColorRgb $colorRgb): ColorRgb
    {
        try {
            return $colorRgb
                ->toHsl()
                ->setSaturation(30)
                ->setLightness(40)
                ->toRgb()
                ->toMinimumContrastRatioAgainstWhite();
        } catch (ExceptionCompile $e) {
            LogUtility::error("Error while calculating the primary text color. {$e->getMessage()}", self::CANONICAL, $e);
            return $colorRgb;
        }

    }

    /**
     * Calculate a color for a text hover that has:
     * * more lightness than the text
     * * and a good contrast ratio
     *
     * @param ColorRgb $colorRgb
     * @return ColorRgb
     */
    public static function toTextHoverColor(ColorRgb $colorRgb): ColorRgb
    {
        try {
            return $colorRgb
                ->toHsl()
                ->setSaturation(88)
                ->setLightness(53)
                ->toRgb()
                ->toMinimumContrastRatioAgainstWhite();
        } catch (ExceptionCompile $e) {
            LogUtility::error("Error while calculating the color text hover color. {$e->getMessage()}", self::CANONICAL, $e);
            return $colorRgb;
        }
    }

    /**
     *
     * @throws ExceptionCompile when the color could not be calculated
     */
    public static function toBackgroundColor(ColorRgb $primaryColor): ColorRgb
    {
        return $primaryColor
            ->toHsl()
            ->setLightness(98)
            ->toRgb()
            ->toMinimumContrastRatioAgainstWhite(1.1, 1);
    }

}
