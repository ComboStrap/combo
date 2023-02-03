<?php

namespace ComboStrap;

class BrandColors
{

    public static function getCssFormControlFocusColor(ColorRgb $primaryColor): string
    {

        try {
            $colorRgb = self::toBackgroundColor($primaryColor);
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Error on background color calculation");
            return "";
        }

        return <<<EOF
.form-control:focus {
    border-color: $colorRgb;
}
EOF;


    }

    /**
     * @throws ExceptionCombo
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
