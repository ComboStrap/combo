<?php

namespace ComboStrap;

class BrandColors
{

    public static function getCssFormControlFocusColor(ColorRgb $primaryColor): string
    {

        try {
            $colorRgb = self::toBackgroundColor($primaryColor);
        } catch (ExceptionCompile $e) {
            LogUtility::msg("Error on background color calculation");
            return "";
        }

        return <<<EOF
.form-control:focus {
    border-color: $colorRgb;
    box-shadow: 0 0 0 0.25rem rgb({$primaryColor->getRed()} {$primaryColor->getGreen()} {$primaryColor->getBlue()} / 25%);
}
.form-check-input:focus {
    border-color: $colorRgb;
    box-shadow: 0 0 0 0.25rem rgb({$primaryColor->getRed()} {$primaryColor->getGreen()} {$primaryColor->getBlue()} / 25%);
}
EOF;


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
