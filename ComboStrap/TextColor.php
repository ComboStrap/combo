<?php


namespace ComboStrap;


class TextColor
{

    const TEXT_COLOR_ATTRIBUTE = "text-color";
    const CSS_ATTRIBUTE = "color";
    const CANONICAL = self::TEXT_COLOR_ATTRIBUTE;

    const TEXT_COLORS = array(
        'primary',
        'secondary',
        'success',
        'danger',
        'warning',
        'info',
        'light',
        'dark',
        'body',
        'muted',
        'white',
        'black-50',
        'white-50'
    );

    /**
     * @param TagAttributes $attributes
     */
    public static function processTextColorAttribute(TagAttributes &$attributes)
    {

        $colorAttributes = [TextColor::CSS_ATTRIBUTE, TextColor::TEXT_COLOR_ATTRIBUTE];
        foreach ($colorAttributes as $colorAttribute) {
            if ($attributes->hasComponentAttribute($colorAttribute)) {
                $colorValue = $attributes->getValueAndRemove($colorAttribute);
                $lowerCaseColorValue = strtolower($colorValue);

                /**
                 * text is based in the text-colorname class
                 * Not yet on variable or color object
                 * We overwrite it here
                 */
                switch ($lowerCaseColorValue) {
                    case ColorRgb::PRIMARY_VALUE:
                        $primaryColor = Site::getPrimaryColor();
                        if ($primaryColor !== null) {
                            // important because we set the text-class below and they already have an important value
                            $attributes->addStyleDeclarationIfNotSet(TextColor::CSS_ATTRIBUTE, "{$primaryColor->toRgbHex()}!important");
                        }
                        break;
                    case ColorRgb::SECONDARY_VALUE:
                        $secondaryColor = Site::getSecondaryColor();
                        if ($secondaryColor !== null) {
                            // important because we set the text-class below and they already have an important value
                            $attributes->addStyleDeclarationIfNotSet(TextColor::CSS_ATTRIBUTE, "{$secondaryColor->toRgbHex()}!important");
                        }
                        break;
                }

                if (in_array($lowerCaseColorValue, self::TEXT_COLORS)) {
                    /**
                     * The bootstrap text class
                     * https://getbootstrap.com/docs/5.0/utilities/colors/#colors
                     */
                    $attributes->addClassName("text-$lowerCaseColorValue");
                } else {
                    /**
                     * Other Text Colors
                     */
                    try {
                        $colorValue = ColorRgb::createFromString($colorValue)->toCssValue();
                    } catch (ExceptionCombo $e) {
                        LogUtility::msg("The text color value ($colorValue) is not a valid color. Error: {$e->getMessage()}");
                        return;
                    }
                    if (!empty($colorValue)) {
                        $attributes->addStyleDeclarationIfNotSet(TextColor::CSS_ATTRIBUTE, $colorValue);
                    }
                }
                break;
            }
        }


    }



}
