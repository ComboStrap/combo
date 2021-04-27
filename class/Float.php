<?php


namespace ComboStrap;


class Float
{
    const CANONICAL = "float";
    const CONF_FLOAT_DEFAULT_BREAKPOINT = "floatDefaultBreakpoint";

    public static function processFloat(&$attributes)
    {
        // The class shortcut
        $float = TagAttributes::FLOAT_KEY;
        if ($attributes->hasComponentAttribute($float)) {
            $floatValue = $attributes->getValueAndRemove($float);
            $floatedValues = StringUtility::explodeAndTrim($floatValue, " ");
            foreach ($floatedValues as $floatedValue) {
                switch ($floatValue) {
                    case "left":
                    case "right":
                    case "none":
                        $defaultBreakpoint = PluginUtility::getConfValue(self::CONF_FLOAT_DEFAULT_BREAKPOINT, "sm");
                        $floatValue = "{$defaultBreakpoint}-$floatValue";
                        break;
                }
                $attributes->addClassName("float-{$floatValue}");
            }
            /**
             * By default, we don't float on extra small screen
             */
            if (!StringUtility::contain("xs", $floatValue)) {
                $attributes->addClassName("float-none");
            }

            // position relative and z-index are needed to put the float above
            $attributes->addStyleDeclaration("position", "relative!important");
            $attributes->addStyleDeclaration("z-index", 1);
        }
    }
}
