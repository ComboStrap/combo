<?php


namespace ComboStrap;


class Display
{

    public const DISPLAY = "display";
    public const DISPLAY_NONE_VALUE = "none";

    public static function processDisplay(TagAttributes &$tagAttributes)
    {

        $display = $tagAttributes->getValueAndRemove(self::DISPLAY);
        if ($display !== null) {
            if (strtolower($display) === self::DISPLAY_NONE_VALUE) {
                $tagAttributes->addStyleDeclarationIfNotSet("display", "none");
            }
        }

    }
}
