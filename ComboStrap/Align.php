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

        $alignValues = explode(" ", $alignAttributeValues);
        foreach ($alignValues as $alignValue) {

            switch ($alignValue) {
                case "center":
                    $attributes->addClassName(self::CENTER_CLASS);
                    break;
                case "right":
                case "end":
                    if (Bootstrap::getBootStrapMajorVersion() == Bootstrap::BootStrapFourMajorVersion) {
                        $attributes->addStyleDeclarationIfNotSet("margin-left", "auto");
                    } else {
                        $attributes->addClassName("ms-auto");
                    }
                    $attributes->addStyleDeclarationIfNotSet("width", "fit-content");
                    break;
                case "x-center-children":
                case "center-children":
                    $attributes->addClassName("justify-content-center");
                    if ($attributes->getLogicalTag() !== \syntax_plugin_combo_row::TAG) {
                        $attributes->addClassName("d-flex");
                    }
                    break;
                case "x-between-children":
                case "between-children":
                    $attributes->addClassName("justify-content-between");
                    if ($attributes->getLogicalTag() !== \syntax_plugin_combo_row::TAG) {
                        $attributes->addClassName("d-flex");
                    }
                    break;
                case "y-center-children":
                    $attributes->addClassName("align-items-center");
                    if ($attributes->getLogicalTag() !== \syntax_plugin_combo_row::TAG) {
                        $attributes->addClassName("d-flex");
                    }
                    break;
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
            if (in_array($attributes->getLogicalTag(), TagAttributes::INLINE_LOGICAL_ELEMENTS)) {
                $attributes->addClassName("d-block");
            }

        }


    }
}
