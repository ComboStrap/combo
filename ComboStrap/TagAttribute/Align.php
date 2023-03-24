<?php


namespace ComboStrap\TagAttribute;


use ComboStrap\Bootstrap;
use ComboStrap\ConditionalLength;
use ComboStrap\Dimension;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\GridTag;
use ComboStrap\LogUtility;
use ComboStrap\TagAttributes;

class Align
{
    /**
     * Class to center an element
     */
    public const CENTER_CLASS = "mx-auto";
    const ALIGN_ATTRIBUTE = "align";

    /**
     * Children are also known as items in HTML/CSS
     * ie list items, align-center-items, ...
     */
    const Y_CENTER_CHILDREN = "y-center-children";
    const Y_TOP_CHILDREN = "y-top-children";
    const X_CENTER_CHILDREN = "x-center-children";
    const DEFAULT_AXIS = self::X_AXIS;
    public const X_AXIS = "x";
    public const Y_AXIS = "y";
    const CANONICAL = self::ALIGN_ATTRIBUTE;

    /**
     * @param TagAttributes $attributes
     */
    public static function processAlignAttributes(TagAttributes &$attributes)
    {
        // The class shortcut
        $align = self::ALIGN_ATTRIBUTE;
        $alignAttributeValues = $attributes->getValueAndRemove($align);
        if ($alignAttributeValues === null || $alignAttributeValues === "") {
            return;
        }

        $flexAxis = null;
        $blockAlign = false;
        $alignValues = explode(" ", $alignAttributeValues);
        foreach ($alignValues as $alignStringValue) {

            try {
                $conditionalAlignValue = ConditionalLength::createFromString($alignStringValue);
            } catch (ExceptionBadArgument $e) {
                LogUtility::error("The align value ($alignStringValue) is not a valid conditional value and was skipped", self::CANONICAL);
                continue;
            }
            switch ($conditionalAlignValue->getLength()) {
                case "center":
                case "x-center":
                    $blockAlign = true;
                    $attributes->addClassName(self::CENTER_CLASS);
                    /**
                     * Don't set: `width:fit-content`
                     * Setting  is cool for a little block
                     * but it will take the max width of all children
                     * making the design not responsive if a table
                     * with `width:max-content` is a children
                     */
                    break;
                case "y-center":
                    $flexAxis[self::Y_AXIS] = true;
                    $breakpoint = $conditionalAlignValue->getBreakpointForBootstrapClass();
                    $attributes->addClassName("align-self{$breakpoint}-center");
                    break;
                case "right":
                case "end":
                    $blockAlign = true;
                    if (Bootstrap::getBootStrapMajorVersion() == Bootstrap::BootStrapFourMajorVersion) {
                        $attributes->addStyleDeclarationIfNotSet("margin-left", "auto");
                    } else {
                        $attributes->addClassName("ms-auto");
                    }
                    $attributes->addStyleDeclarationIfNotSet(Dimension::WIDTH_KEY, "fit-content");
                    break;
                case "x-left-children":
                case "left-children":
                case "start-children":
                case "x-start-children":
                    $flexAxis[self::X_AXIS] = true;
                    $breakpoint = $conditionalAlignValue->getBreakpointForBootstrapClass();
                    switch (Bootstrap::getBootStrapMajorVersion()) {
                        case Bootstrap::BootStrapFourMajorVersion:
                            $attributes->addClassName("justify-content{$breakpoint}-left");
                            break;
                        default:
                            $attributes->addClassName("justify-content{$breakpoint}-start");
                    }
                    break;
                case "x-right-children":
                case "right-children":
                case "end-children":
                case "x-end-children":
                    $flexAxis[self::X_AXIS] = true;
                    $breakpoint = $conditionalAlignValue->getBreakpointForBootstrapClass();
                    switch (Bootstrap::getBootStrapMajorVersion()) {
                        case Bootstrap::BootStrapFourMajorVersion:
                            $attributes->addClassName("justify-content{$breakpoint}-right");
                            break;
                        default:
                            $attributes->addClassName("justify-content{$breakpoint}-end");
                    }
                    break;
                case "x-center-children":
                case "center-children":
                    $flexAxis[self::X_AXIS] = true;
                    $breakpoint = $conditionalAlignValue->getBreakpointForBootstrapClass();
                    $attributes->addClassName("justify-content{$breakpoint}-center");
                    break;
                case "x-between-children":
                case "between-children":
                    $flexAxis[self::X_AXIS] = true;
                    $breakpoint = $conditionalAlignValue->getBreakpointForBootstrapClass();
                    $attributes->addClassName("justify-content{$breakpoint}-between");
                    break;
                case self::Y_CENTER_CHILDREN:
                    $flexAxis[self::Y_AXIS] = true;
                    $breakpoint = $conditionalAlignValue->getBreakpointForBootstrapClass();
                    $attributes->addClassName("align-items{$breakpoint}-center");
                    break;
                case self::Y_TOP_CHILDREN:
                    $flexAxis[self::Y_AXIS] = true;
                    $breakpoint = $conditionalAlignValue->getBreakpointForBootstrapClass();
                    $attributes->addClassName("align-items{$breakpoint}-start");
                    break;
                case "text-center":
                    $breakpoint = $conditionalAlignValue->getBreakpointForBootstrapClass();
                    $attributes->addClassName("text{$breakpoint}-center");
                    break;
                case "text-left":
                case "text-start":
                    $breakpoint = $conditionalAlignValue->getBreakpointForBootstrapClass();
                    switch (Bootstrap::getBootStrapMajorVersion()) {
                        case Bootstrap::BootStrapFourMajorVersion:
                            $attributes->addClassName("text{$breakpoint}-left");
                            break;
                        default:
                            $attributes->addClassName("text{$breakpoint}-start");
                    }
                    break;
                case "text-right":
                case "text-end":
                    $breakpoint = $conditionalAlignValue->getBreakpointForBootstrapClass();
                    switch (Bootstrap::getBootStrapMajorVersion()) {
                        case Bootstrap::BootStrapFourMajorVersion:
                            $attributes->addClassName("text{$breakpoint}-right");
                            break;
                        default:
                            $attributes->addClassName("text{$breakpoint}-end");
                    }
                    break;
            }


        }

        /**
         * For flex element
         */
        if ($flexAxis !== null) {
            /**
             * A bootstrap row is already a flex, no need to add it
             */
            if ($attributes->getLogicalTag() !== GridTag::TAG) {
                $attributes->addClassName("d-flex");
                if (!isset($flexAxis[self::Y_AXIS])) {
                    /**
                     * flex box change the line center of where the text is written
                     * if a flex align attribute is used in a row, a itext or any other
                     * component that is not a grid, we set it to center
                     *
                     * You can see this effect for instance on a badge, where the text will jump
                     * to the top (the flex default), without centering the flex on y
                     */
                    $attributes->addClassName("align-items-center");
                }
            }
        }

        /**
         * For inline element,
         * center should be a block
         * (svg is not a block by default for instance)
         * !
         * this should not be the case for flex block such as a row
         * therefore the condition
         * !
         */
        if ($blockAlign === true && in_array($attributes->getLogicalTag(), TagAttributes::INLINE_LOGICAL_ELEMENTS)) {
            $attributes->addClassName("d-block");
        }


    }
}
