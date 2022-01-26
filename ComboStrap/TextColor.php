<?php


namespace ComboStrap;


class TextColor
{

    const TEXT_COLOR_ATTRIBUTE = "text-color";
    const CSS_ATTRIBUTE = "color";
    const CANONICAL = self::TEXT_COLOR_ATTRIBUTE;
    const TEXT_TAGS = [
        \syntax_plugin_combo_text::TAG,
        \syntax_plugin_combo_itext::TAG
    ];
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
                 * Branding colors overwrite
                 */
                switch($lowerCaseColorValue){
                    case ColorUtility::PRIMARY_VALUE:
                        $primaryColor = Site::getPrimaryColor();
                        if($primaryColor!==null){
                            // important because we set the text-class below and they already have an important value
                            $attributes->addStyleDeclarationIfNotSet(TextColor::CSS_ATTRIBUTE, "$primaryColor!important");
                        }
                        break;
                    case ColorUtility::SECONDARY_VALUE:
                        $secondaryColor = Site::getSecondaryColor();
                        if($secondaryColor!==null){
                            // important because we set the text-class below and they already have an important value
                            $attributes->addStyleDeclarationIfNotSet(TextColor::CSS_ATTRIBUTE, "$secondaryColor!important");
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
                    $colorValue = ColorUtility::getColorValue($colorValue);
                    if (!empty($colorValue)) {
                        $attributes->addStyleDeclarationIfNotSet(TextColor::CSS_ATTRIBUTE, $colorValue);
                    }
                }
                break;
            }
        }


    }



}
