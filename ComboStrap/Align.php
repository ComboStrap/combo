<?php


namespace ComboStrap;


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
        foreach ($alignValues as $alignValue) {

            switch ($alignValue) {
                case "center":
                    $blockAlign = true;
                    $attributes->addClassName(self::CENTER_CLASS);
                    break;
                case "y-center":
                    $flexAxis[self::Y_AXIS] = true;
                    $attributes->addClassName("align-self-center");
                    break;
                case "right":
                case "end":
                    $blockAlign = true;
                    if (Bootstrap::getBootStrapMajorVersion() == Bootstrap::BootStrapFourMajorVersion) {
                        $attributes->addStyleDeclarationIfNotSet("margin-left", "auto");
                    } else {
                        $attributes->addClassName("ms-auto");
                    }
                    $attributes->addStyleDeclarationIfNotSet("width", "fit-content");
                    break;
                case "x-center-children":
                case "center-children":
                    $flexAxis[self::X_AXIS] = true;
                    $attributes->addClassName("justify-content-center");
                    break;
                case "x-between-children":
                case "between-children":
                    $flexAxis[self::X_AXIS] = true;
                    $attributes->addClassName("justify-content-between");
                    break;
                case self::Y_CENTER_CHILDREN:
                    $flexAxis[self::Y_AXIS] = true;
                    $attributes->addClassName("align-items-center");
                    break;
                case self::Y_TOP_CHILDREN:
                    $flexAxis[self::Y_AXIS] = true;
                    $attributes->addClassName("align-items-start");
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
            if ($attributes->getLogicalTag() !== \syntax_plugin_combo_grid::TAG) {
                $attributes->addClassName("d-flex");
                if(!isset($flexAxis[self::Y_AXIS])){
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
