<?php


namespace ComboStrap;


class Horizontal
{


    public const HORIZONTAL_ATTRIBUTE = "horizontal";
    const CANONICAL = self::HORIZONTAL_ATTRIBUTE;

    const VALUES = [
        "start-items",
        "end",
        "center-children",
        "between",
        "around",
        "evenly"
    ];

    public static function processHorizontal(TagAttributes &$tagAttributes)
    {

        self::processFlexAttribute(self::HORIZONTAL_ATTRIBUTE, $tagAttributes);

    }

    static function processFlexAttribute(string $attributeName, $tagAttributes)
    {

        $value = $tagAttributes->getValueAndRemove($attributeName);
        if ($value === null) {
            return;
        }

        $logicalTag = $tagAttributes->getLogicalTag();

        if (!in_array($logicalTag, Vertical::COMPONENTS)) {
            LogUtility::warning("The $attributeName attribute is only meant to be used on the following component " . implode(", ", Vertical::COMPONENTS), self::CANONICAL);
        }
        try {
            $conditionalValue = ConditionalValue::createFrom($value);
        } catch (ExceptionBadSyntax $e) {
            LogUtility::error("The $attributeName attribute value is not valid. Error: {$e->getMessage()}", self::CANONICAL);
            return;
        }
        $valueWithoutBreakpoint = $conditionalValue->getValue();
        if ($attributeName === self::HORIZONTAL_ATTRIBUTE) {
            $possibleValues = self::VALUES;
        } else {
            $possibleValues = Horizontal::VALUES;
        }
        if (!in_array($valueWithoutBreakpoint, $possibleValues)) {
            LogUtility::error("The $attributeName attribute value ($valueWithoutBreakpoint) is not good. It should be one of: " . implode(", ", $possibleValues), self::CANONICAL);
            return;
        }
        $breakpoint = $conditionalValue->getBreakpoint();
        if ($attributeName === self::HORIZONTAL_ATTRIBUTE) {
            $classPrefix = "justify-content";
        } else {
            $classPrefix = "align-items";
        }
        if ($breakpoint !== null) {
            $class = "$classPrefix-$breakpoint-$valueWithoutBreakpoint";
        } else {
            $class = "$classPrefix-$valueWithoutBreakpoint";
        }
        $tagAttributes->addClassName($class);

        // works only on flex items
        // row is a flex item
        if ($logicalTag !== \syntax_plugin_combo_grid::TAG) {
            $tagAttributes->addClassName(\syntax_plugin_combo_cell::FLEX_CLASS);
        }

    }

}
