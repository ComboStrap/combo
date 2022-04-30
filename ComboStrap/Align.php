<?php


namespace ComboStrap;


class Align
{
    /**
     * Class to center an element
     */
    public const CENTER_CLASS = "mx-auto";
    const ALIGN_ATTRIBUTE = "align";
    const Y_CENTER_CHILDREN = "y-center-children";
    const Y_TOP_CHILDREN = "y-top-children";

    /**
     * @param TagAttributes $attributes
     */
    public static function processAlignAttributes(&$attributes)
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
                    $flexAxis[ConditionalLength::X_AXIS] = true;
                    $attributes->addClassName("justify-content-center");
                    break;
                case "x-between-children":
                case "between-children":
                    $flexAxis[ConditionalLength::X_AXIS] = true;
                    $attributes->addClassName("justify-content-between");
                    break;
                case self::Y_CENTER_CHILDREN:
                    $flexAxis[ConditionalLength::Y_AXIS] = true;
                    $attributes->addClassName("align-items-center");
                    break;
                case self::Y_TOP_CHILDREN:
                    $flexAxis[ConditionalLength::Y_AXIS] = true;
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
            }
            if (!isset($flexAxis[ConditionalLength::Y_AXIS])) {
                /**
                 * Why ? Because by default, a flex place text at the top and if a badge is added
                 * for instance, it will shift the text towards the top
                 * If a flex attribute on the x-axis is used, we still want the y-axis centered
                 */
                $attributes->addClassName("align-items-center");
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
