<?php


namespace ComboStrap;


class Dimension
{
    /**
     * The element that have an width and height
     */
    const NATURAL_SIZING_ELEMENT = [SvgImageLink::CANONICAL, RasterImageLink::CANONICAL];


    /**
     * @param TagAttributes $attributes
     */
    public static function processWidthAndHeight(&$attributes)
    {
        $widthName = TagAttributes::WIDTH_KEY;
        if ($attributes->hasComponentAttribute($widthName)) {

            $widthValue = trim($attributes->getValueAndRemove($widthName));

            if ($widthValue == "0") {

                // The dimension are restricted by height
                if($attributes->hasComponentAttribute(TagAttributes::HEIGHT_KEY)) {
                    $attributes->addStyleDeclaration("width", "auto");
                }

            } else {


                if ($widthValue == "fit") {
                    $widthValue = "fit-content";
                } else {
                    /** Numeric value */
                    $widthValue = TagAttributes::toQualifiedCssValue($widthValue);
                }


                /**
                 * For an image
                 */
                if (in_array($attributes->getLogicalTag(), self::NATURAL_SIZING_ELEMENT)) {

                    /**
                     * If the image is not ask as static resource (ie HTTP request)
                     * but added in HTML
                     * (ie {@link \action_plugin_combo_svg})
                     */
                    $requestedMime = $attributes->getMime();
                    if ($requestedMime == TagAttributes::TEXT_HTML_MIME) {
                        $attributes->addStyleDeclaration('max-width', $widthValue);
                        $attributes->addStyleDeclaration('width', "100%");
                    }

                } else {

                    /**
                     * For a block
                     */
                    $attributes->addStyleDeclaration('max-width', $widthValue);

                }
            }

        }

        $heightName = TagAttributes::HEIGHT_KEY;
        if ($attributes->hasComponentAttribute($heightName)) {
            $heightValue = trim($attributes->getValueAndRemove($heightName));
            if($heightValue!=="") {
                $heightValue = TagAttributes::toQualifiedCssValue($heightValue);

                if (in_array($attributes->getLogicalTag(), self::NATURAL_SIZING_ELEMENT)) {

                    /**
                     * A element with a natural height is responsive, we set only the max-height
                     *
                     * By default, the image has a `height: auto` due to the img-fluid class
                     * Making it height responsive
                     */
                    $attributes->addStyleDeclaration("max-height", $heightValue);

                } else {

                    /**
                     * HTML Block
                     *
                     * Without the height value, a block display will collapse
                     * min-height and not height to not constraint the box
                     */
                    $attributes->addStyleDeclaration("min-height", $heightValue);
                }
            }

        }
    }
}
