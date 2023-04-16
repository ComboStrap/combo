<?php

namespace ComboStrap;

/**
 * Branding colors are the primary and secondary colors of the web site
 * (for now)
 *
 * Don't confuse with {@link Brand}
 */
class BrandingColors
{

    /**
     * Do we set also the branding color on
     * other elements ?
     */
    public const BRANDING_COLOR_INHERITANCE_ENABLE_CONF = "brandingColorInheritanceEnable";
    public const BRANDING_COLOR_INHERITANCE_ENABLE_CONF_DEFAULT = 1;
    public const PRIMARY_COLOR_CONF = "primaryColor";

    const CANONICAL = "branding-colors";

    /**
     * The attribute used in the model template data
     * (used for css variables and other colors transformation)
     */
    const PRIMARY_COLOR_TEMPLATE_ATTRIBUTE = 'primary-color';
    /**
     * A color that is derived from the primary color
     * where the contrast is good enought for reading
     */
    const PRIMARY_COLOR_TEXT_ATTRIBUTE = "primary-color-text";
    /**
     * The text color with a little bit more lightness
     */
    const PRIMARY_COLOR_TEXT_HOVER_ATTRIBUTE = "primary-color-text-hover";
    public const SECONDARY_COLOR_TEMPLATE_ATTRIBUTE = 'secondary-color';

    public static function getCssFormControlFocusColor(ColorRgb $primaryColor): string
    {

        try {
            $colorRgb = ColorSystem::toBackgroundColor($primaryColor);
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


}
