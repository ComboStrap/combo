<?php


namespace ComboStrap;


use dokuwiki\Extension\SyntaxPlugin;
use syntax_plugin_combo_button;
use syntax_plugin_combo_link;
use syntax_plugin_combo_pageimage;

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
    const SCROLL = "scroll";

    /**
     * Logical height and width
     * used by default to define the width and height of an image or a css box
     */
    const HEIGHT_KEY = 'height';
    const WIDTH_KEY = 'width';

    /**
     * The ratio (16:9, ...) permits to change:
     *   * the viewBox in svg
     *   * the intrinsic dimension in raster
     *
     * It's then part of the request
     * because in svg it is the definition of the viewBox
     *
     * The rendering function takes care of it
     * and it's also passed in the fetch url
     */
    public const RATIO_ATTRIBUTE = "ratio";
    const ZOOM_ATTRIBUTE = "zoom";


    /**
     * @param TagAttributes $attributes
     */
    public static function processWidthAndHeight(TagAttributes &$attributes)
    {
        $widthName = self::WIDTH_KEY;
        if ($attributes->hasComponentAttribute($widthName)) {

            $widthValue = trim($attributes->getValueAndRemove($widthName));

            if ($widthValue == "0") {

                /**
                 * For an image, the dimension are restricted by height
                 */
                if ($attributes->hasComponentAttribute(self::HEIGHT_KEY)) {
                    $attributes->addStyleDeclarationIfNotSet("width", "auto");
                }

            } else {


                if ($widthValue == "fit") {
                    $widthValue = "fit-content";
                } else {
                    /** Numeric value */
                    $widthValue = TagAttributes::toQualifiedCssValue($widthValue);
                }


                /**
                 * For an image (png, svg)
                 * They have width and height **element** attribute
                 */
                if (in_array($attributes->getLogicalTag(), self::NATURAL_SIZING_ELEMENT)) {

                    /**
                     * If the image is not ask as static resource (ie HTTP request)
                     * but added in HTML
                     * (ie {@link \action_plugin_combo_svg})
                     */
                    $requestedMime = $attributes->getMime();
                    if ($requestedMime == TagAttributes::TEXT_HTML_MIME) {
                        $attributes->addStyleDeclarationIfNotSet('max-width', $widthValue);
                        $attributes->addStyleDeclarationIfNotSet('width', "100%");
                    }

                } else {

                    /**
                     * For a block
                     */
                    $attributes->addStyleDeclarationIfNotSet('max-width', $widthValue);

                }
            }

        }

        $heightName = self::HEIGHT_KEY;
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
                    $attributes->addStyleDeclarationIfNotSet("max-height", $heightValue);

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
                        $attributes->addStyleDeclarationIfNotSet("height", $heightValue);

                        $scrollMechanism = $attributes->getValueAndRemoveIfPresent("scroll");
                        if ($scrollMechanism != null) {
                            $scrollMechanism = trim(strtolower($scrollMechanism));
                        }
                        switch ($scrollMechanism) {
                            case "toggle":
                                // https://jsfiddle.net/gerardnico/h0g6xw58/
                                $attributes->addStyleDeclarationIfNotSet("overflow-y", "hidden");
                                $attributes->addStyleDeclarationIfNotSet("position", "relative");
                                $attributes->addStyleDeclarationIfNotSet("display", "block");
                                // The block should collapse to this height
                                $attributes->addStyleDeclarationIfNotSet("min-height", $heightValue);
                                if ($attributes->hasComponentAttribute("id")) {
                                    $id = $attributes->getValue("id");
                                } else {
                                    $id = $attributes->generateAndSetId();
                                }
                                /**
                                 * Css of the button and other standard attribute
                                 */
                                PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot("height-toggle");
                                /**
                                 * Set the color dynamically to the color of the parent
                                 */
                                PluginUtility::getSnippetManager()->attachInternalJavascriptForSlot("height-toggle");
                                /**
                                 * The height when there is not the show class
                                 * is the original height
                                 */
                                $css = <<<EOF
#$id:not(.show){
  height: $heightValue;
  transition: height .35s ease;
}
EOF;
                                PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot("height-toggle-show", $css);
                                $bootstrapDataNameSpace = Bootstrap::getDataNamespace();
                                $button = <<<EOF
<button class="height-toggle-combo" data$bootstrapDataNameSpace-toggle="collapse" data$bootstrapDataNameSpace-target="#$id" aria-expanded="false"></button>
EOF;

                                $attributes->addHtmlAfterEnterTag($button);

                                break;
                            case "lift";
                            default:
                                $attributes->addStyleDeclarationIfNotSet("overflow", "auto");
                                break;

                        }


                    } else {

                        /**
                         * if fluid
                         * min-height and not height to not constraint the box
                         */
                        $attributes->addStyleDeclarationIfNotSet("min-height", $heightValue);

                    }
                }
            }

        }
    }

    /**
     *
     * Toggle with a click on the collapsed element
     * if there is no control element such as button or link inside
     *
     * This function is used at the {@link DOKU_LEXER_EXIT} state of a {@link SyntaxPlugin::handle()}
     *
     * @param CallStack $callStack
     */
    public static function addScrollToggleOnClickIfNoControl(CallStack $callStack)
    {
        $callStack->moveToEnd();
        $openingCall = $callStack->moveToPreviousCorrespondingOpeningCall();
        $scrollAttribute = $openingCall->getAttribute(Dimension::SCROLL);
        if ($scrollAttribute != null && $scrollAttribute == "toggle") {

            $controlFound = false;
            while ($actualCall = $callStack->next()) {
                if (in_array($actualCall->getTagName(),
                    [syntax_plugin_combo_button::TAG, syntax_plugin_combo_link::TAG, "internallink", "externallink"])) {
                    $controlFound = true;
                    break;
                }
            }
            if (!$controlFound) {
                $toggleOnClickId = "height-toggle-onclick";
                PluginUtility::getSnippetManager()->attachInternalJavascriptForSlot($toggleOnClickId);
                $openingCall->addClassName("{$toggleOnClickId}-combo");
                $openingCall->addCssStyle("cursor", "pointer");
            }

        }
    }

    /**
     * @param $value - a css value to a pixel
     * @throws ExceptionCombo
     */
    public static function toPixelValue($value): int
    {

        preg_match("/[a-z]/i", $value, $matches, PREG_OFFSET_CAPTURE);
        $unit = null;
        if (sizeof($matches) > 0) {
            $firstPosition = $matches[0][1];
            $unit = strtolower(substr($value, $firstPosition));
            $value = DataType::toFloat((substr($value, 0, $firstPosition)));
        }
        switch ($unit) {
            case "rem":
                $remValue = Site::getRem();
                $targetValue = $value * $remValue;
                break;
            case "px":
            default:
                $targetValue = $value;
        }
        return DataType::toInteger($targetValue);
    }

    /**
     * Convert 16:9, ... to a float
     * @param string $stringRatio
     * @return float
     * @throws ExceptionCombo
     */
    public static function convertTextualRatioToNumber(string $stringRatio): float
    {
        list($width, $height) = explode(":", $stringRatio, 2);
        try {
            $width = DataType::toInteger($width);
        } catch (ExceptionCombo $e) {
            throw new ExceptionCombo("The width value ($width) of the ratio `$stringRatio` is not numeric", syntax_plugin_combo_pageimage::CANONICAL);
        }
        try {
            $height = DataType::toInteger($height);
        } catch (ExceptionCombo $e) {
            throw new ExceptionCombo("The width value ($height) of the ratio `$stringRatio` is not numeric", syntax_plugin_combo_pageimage::CANONICAL);
        }
        if ($height == 0) {
            throw new ExceptionCombo("The height value of the ratio `$stringRatio` should not be zero", syntax_plugin_combo_pageimage::CANONICAL);
        }
        return floatval($width / $height);

    }


}
