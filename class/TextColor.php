<?php


namespace ComboStrap;


class TextColor
{

    const TEXT_COLOR_ATTRIBUTE = "text-color";
    const CSS_ATTRIBUTE = "color";
    const CANONICAL = self::TEXT_COLOR_ATTRIBUTE;

    /**
     * @param TagAttributes $attributes
     */
    public static function processTextColorAttribute(TagAttributes &$attributes)
    {
        $colorAttributes = [TextColor::CSS_ATTRIBUTE, TextColor::TEXT_COLOR_ATTRIBUTE];
        $colorValue = "";
        foreach ($colorAttributes as $colorAttribute) {
            if ($attributes->hasComponentAttribute($colorAttribute)) {
                $colorValue = $attributes->getValueAndRemove($colorAttribute);
                $colorValue = ColorUtility::getColorValue($colorValue);
                break;
            }
        }
        if (!empty($colorValue)) {
            $attributes->addStyleDeclaration(TextColor::CSS_ATTRIBUTE, $colorValue);
        }

    }


}
