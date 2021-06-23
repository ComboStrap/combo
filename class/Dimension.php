<?php


namespace ComboStrap;


class Dimension
{
    /**
     * The element that have an width and height
     */
    const NATURAL_SIZING_ELEMENT = [SvgImageLink::CANONICAL, RasterImageLink::CANONICAL];

    const DESIGN_LAYOUT_CONSTRAINED = "constrained"; // fix value
    const DESIGN_LAYOUT_FLUID = "fluid"; // adapt

    /**
     * On the width, if set, the design is fluid and will adapt to all screen
     * with a min-width
     */
    const WIDTH_LAYOUT_DEFAULT = self::DESIGN_LAYOUT_FLUID;
    /**
     * On height, if set, the design is constrained and overflow
     */
    const HEIGHT_LAYOUT_DEFAULT = self::DESIGN_LAYOUT_CONSTRAINED;


    /**
     * @param TagAttributes $attributes
     */
    public static function processWidthAndHeight(&$attributes)
    {
        $widthName = TagAttributes::WIDTH_KEY;
        if ($attributes->hasComponentAttribute($widthName)) {

            $widthValue = trim($attributes->getValueAndRemove($widthName));

            if ($widthValue == "0") {

                /**
                 * For an image, the dimension are restricted by height
                 */
                if ($attributes->hasComponentAttribute(TagAttributes::HEIGHT_KEY)) {
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
            if ($heightValue !== "") {
                $heightValue = TagAttributes::toQualifiedCssValue($heightValue);

                if (in_array($attributes->getLogicalTag(), self::NATURAL_SIZING_ELEMENT)) {

                    /**
                     * A element with a natural height is responsive, we set only the max-height
                     *
                     * By default, the image has a `height: auto` due to the img-fluid class
                     * Making its height responsive
                     */
                    $attributes->addStyleDeclaration("max-height", $heightValue);

                } else {

                    /**
                     * HTML Block
                     *
                     * Without the height value, a block display will collapse
                     */
                    if (self::HEIGHT_LAYOUT_DEFAULT == self::DESIGN_LAYOUT_CONSTRAINED) {

                        /**
                         * The box is constrained in height
                         * By default, a box is not constrained
                         */
                        $attributes->addStyleDeclaration("height", $heightValue);

                        $scrollMechanism = $attributes->getValueAndRemoveIfPresent("scroll");
                        if ($scrollMechanism != null) {
                            $scrollMechanism = trim(strtolower($scrollMechanism));
                        }
                        switch ($scrollMechanism) {
                            case "toggle":
                                // https://jsfiddle.net/gerardnico/h0g6xw58/
                                $attributes->addStyleDeclaration("overflow", "hidden");
                                $attributes->addStyleDeclaration("position", "relative");
                                $attributes->addStyleDeclaration("display", "block");
                                // The block should collapse to this height
                                $attributes->addStyleDeclaration("min-height", $heightValue);
                                if($attributes->hasComponentAttribute("id")){
                                    $id = $attributes->getValue("id");
                                } else {
                                    $id = $attributes->generateAndSetId();
                                }
                                /**
                                 * Css of the button and other standard attribute
                                 */
                                PluginUtility::getSnippetManager()->attachCssSnippetForBar("height-toggle");
                                /**
                                 * Set the color dynamically to the color of the parent
                                 */
                                PluginUtility::getSnippetManager()->attachJavascriptSnippetForBar("height-toggle");
                                /**
                                 * The height when there is not the show class
                                 * is the original height
                                 */
                                $css =<<<EOF
#$id:not(.show){
  height: $heightValue;
}
EOF;
                                PluginUtility::getSnippetManager()->attachCssSnippetForBar("height-toggle-show",$css);
                                $bootstrapDataNameSpace = Bootstrap::getDataNamespace();
                                $button=<<<EOF
<button class="height-toggle-combo" data$bootstrapDataNameSpace-toggle="collapse" data$bootstrapDataNameSpace-target="#$id" aria-expanded="false"><span class="label"></span></button>
EOF;

                                $attributes->addHtmlAfterEnterTag($button);

                                break;
                            case "lift";
                            default:
                                $attributes->addStyleDeclaration("overflow", "auto");
                                break;

                        }


                    } else {

                        /**
                         * if fluid
                         * min-height and not height to not constraint the box
                         */
                        $attributes->addStyleDeclaration("min-height", $heightValue);

                    }
                }
            }

        }
    }
}
