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

    const CANONICAL = "dimension";
    public const WIDTH_KEY_SHORT = "w";
    public const HEIGHT_KEY_SHORT = "h";


    /**
     * @param TagAttributes $attributes
     */
    public static function processWidthAndHeight(TagAttributes &$attributes)
    {
        self::processWidth($attributes);

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
                                PluginUtility::getSnippetManager()->attachCssInternalStyleSheet("height-toggle");
                                /**
                                 * Set the color dynamically to the color of the parent
                                 */
                                PluginUtility::getSnippetManager()->attachJavascriptFromComponentId("height-toggle");
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
                                PluginUtility::getSnippetManager()->attachCssInternalStyleSheet("height-toggle-show", $css);
                                $bootstrapDataNameSpace = Bootstrap::getDataNamespace();
                                /** @noinspection HtmlUnknownAttribute */
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
                PluginUtility::getSnippetManager()->attachJavascriptFromComponentId($toggleOnClickId);
                $openingCall->addClassName("{$toggleOnClickId}-combo");
                $openingCall->addCssStyle("cursor", "pointer");
            }

        }
    }

    /**
     * @param $value - a css value to a pixel
     * @throws ExceptionCompile
     * @deprecated for {@link ConditionalLength::toPixelNumber()}
     */
    public static function toPixelValue($value): int
    {

        return ConditionalLength::createFromString($value)->toPixelNumber();

    }

    /**
     * Convert 16:9, ... to a float
     * @param string $stringRatio
     * @return float
     * @throws ExceptionBadSyntax
     */
    public static function convertTextualRatioToNumber(string $stringRatio): float
    {
        list($width, $height) = explode(":", $stringRatio, 2);
        try {
            $width = DataType::toInteger($width);
        } catch (ExceptionCompile $e) {
            throw new ExceptionBadSyntax("The width value ($width) of the ratio `$stringRatio` is not numeric", syntax_plugin_combo_pageimage::CANONICAL);
        }
        try {
            $height = DataType::toInteger($height);
        } catch (ExceptionCompile $e) {
            throw new ExceptionBadSyntax("The width value ($height) of the ratio `$stringRatio` is not numeric", syntax_plugin_combo_pageimage::CANONICAL);
        }
        if ($height === 0) {
            throw new ExceptionBadSyntax("The height value of the ratio `$stringRatio` should not be zero", syntax_plugin_combo_pageimage::CANONICAL);
        }
        return floatval($width / $height);

    }

    private static function processWidth(TagAttributes $attributes)
    {
        $widthValueAsString = $attributes->getComponentAttributeValueAndRemoveIfPresent(self::WIDTH_KEY);
        if ($widthValueAsString === null) {
            return;
        }

        $widthValueAsString = trim($widthValueAsString);
        $logicalTag = $attributes->getLogicalTag();
        if ($widthValueAsString === "") {
            LogUtility::error("The width value is empty for the tag ({$logicalTag})");
            return;
        }
        $widthValues = explode(" ", $widthValueAsString);
        foreach ($widthValues as $widthValue) {

            try {
                $conditionalWidthLength = ConditionalLength::createFromString($widthValue);
            } catch (ExceptionBadArgument $e) {
                LogUtility::error("The width value ($widthValue) is not a valid length. Error: {$e->getMessage()}");
                continue;
            }


            /**
             * For an image (png, svg)
             * They have width and height **element** attribute
             */
            if (in_array($logicalTag, self::NATURAL_SIZING_ELEMENT)) {

                /**
                 * If the image is not asked as static resource (ie HTTP request)
                 * but added in HTML
                 * (ie {@link \action_plugin_combo_svg})
                 */
                $requestedMime = $attributes->getMime();
                if ($requestedMime == TagAttributes::TEXT_HTML_MIME) {

                    $length = $conditionalWidthLength->getLength();
                    if ($length === "0") {

                        /**
                         * For an image, the dimension are restricted by height
                         */
                        if ($attributes->hasComponentAttribute(self::HEIGHT_KEY)) {
                            $attributes->addStyleDeclarationIfNotSet("width", "auto");
                        }
                        return;

                    }

                    /**
                     * For an image, the dimension are restricted by width
                     * (max-width or 100% of the container )
                     */
                    try {
                        $attributes->addStyleDeclarationIfNotSet('max-width', $conditionalWidthLength->toCssLength());
                    } catch (ExceptionBadArgument $e) {
                        LogUtility::error("The conditional length ($conditionalWidthLength) could not be transformed as CSS value. Error", self::CANONICAL);
                        $attributes->addStyleDeclarationIfNotSet('max-width', $conditionalWidthLength->getLength());
                    }
                    $attributes->addStyleDeclarationIfNotSet('width', "100%");
                }
                return;
            }

            /**
             * For a element without natural sizing
             */
            $unit = $conditionalWidthLength->getLengthUnit();
            switch ($unit) {
                case ConditionalLength::PERCENTAGE:
                    try {
                        $attributes->addClassName($conditionalWidthLength->toColClass());
                    } catch (ExceptionBadArgument $e) {
                        LogUtility::error("The conditional length ($conditionalWidthLength) could not be converted to a col class. Error: {$e->getMessage()}");
                    }
                    break;
                default:
                    try {
                        $attributes->addStyleDeclarationIfNotSet('max-width', $conditionalWidthLength->toCssLength());
                    } catch (ExceptionBadArgument $e) {
                        LogUtility::error("The conditional length ($conditionalWidthLength) could not be transformed as CSS value. Error", self::CANONICAL);
                        $attributes->addStyleDeclarationIfNotSet('max-width', $conditionalWidthLength->getLength());
                    }
                    break;
            }


        }

    }


}
