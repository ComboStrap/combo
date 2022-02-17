<?php


namespace ComboStrap;

use splitbrain\phpcli\Colors;

/**
 * Class Skin
 * @package ComboStrap
 * Processing the skin attribute
 */
class Skin
{

    const CANONICAL = self::SKIN_ATTRIBUTE;
    const SKIN_ATTRIBUTE = "skin";
    const FILLED_VALUE = "filled";


    static $colorsWithoutPrimaryAndSecondary = array(
        "info" => array(
            ColorRgb::COLOR => "#0c5460",
            Background::BACKGROUND_COLOR => "#d1ecf1",
            ColorRgb::BORDER_COLOR => "#bee5eb"
        ),
        "tip" => array(
            ColorRgb::COLOR => "#6c6400",
            Background::BACKGROUND_COLOR => "#fff79f",
            ColorRgb::BORDER_COLOR => "#FFF78c"
        ),
        "warning" => array(
            ColorRgb::COLOR => "#856404",
            Background::BACKGROUND_COLOR => "#fff3cd",
            ColorRgb::BORDER_COLOR => "#ffeeba"
        ),
        "success" => array(
            ColorRgb::COLOR => "#fff",
            Background::BACKGROUND_COLOR => "#28a745",
            ColorRgb::BORDER_COLOR => "#28a745"
        ),
        "danger" => array(
            ColorRgb::COLOR => "#fff",
            Background::BACKGROUND_COLOR => "#dc3545",
            ColorRgb::BORDER_COLOR => "#dc3545"
        ),
        "dark" => array(
            ColorRgb::COLOR => "#fff",
            Background::BACKGROUND_COLOR => "#343a40",
            ColorRgb::BORDER_COLOR => "#343a40"
        ),
        "light" => array(
            ColorRgb::COLOR => "#fff",
            Background::BACKGROUND_COLOR => "#f8f9fa",
            ColorRgb::BORDER_COLOR => "#f8f9fa"
        )
    );

    public static function getSkinColors(): array
    {
        $primaryColorRgbHex = Site::getPrimaryColor("#007bff")->toRgbHex();
        $secondaryColorRgbHex = Site::getSecondaryColor("#6c757d")->toRgbHex();
        $brandingColors = array(ColorRgb::PRIMARY_VALUE => array(
            ColorRgb::COLOR => "#fff",
            Background::BACKGROUND_COLOR => $primaryColorRgbHex,
            ColorRgb::BORDER_COLOR => $primaryColorRgbHex
        ),
            ColorRgb::SECONDARY_VALUE => array(
                ColorRgb::COLOR => "#fff",
                Background::BACKGROUND_COLOR => $secondaryColorRgbHex,
                ColorRgb::BORDER_COLOR => $secondaryColorRgbHex
            ));
        return array_merge($brandingColors, self::$colorsWithoutPrimaryAndSecondary);
    }

    /**
     * Used with button
     * @param TagAttributes $attributes
     */
    public static function processSkinAttribute(TagAttributes &$attributes)
    {
        // Skin
        if (!$attributes->hasComponentAttribute(self::SKIN_ATTRIBUTE)) {
            return;
        }
        $skinValue = $attributes->getValue(self::SKIN_ATTRIBUTE);
        if (!$attributes->hasComponentAttribute(TagAttributes::TYPE_KEY)) {

            LogUtility::msg("A component type is mandatory when using the skin attribute", LogUtility::LVL_MSG_WARNING, self::CANONICAL);

        } else {
            $type = $attributes->getValue(TagAttributes::TYPE_KEY);
            if (
                $skinValue === self::FILLED_VALUE
                && ($attributes->hasClass("btn-$type")||$attributes->hasClass("alert-$type"))
            ) {
                $isBrandingColor = in_array($type, [ColorRgb::PRIMARY_VALUE, ColorRgb::SECONDARY_VALUE]);
                if (!$isBrandingColor) {
                    // example: light
                    return;
                }
                if (!Site::isBrandingColorInheritanceFunctional()) {
                    // example: primary, secondary
                    return;
                }
            }

            $skinColors = self::getSkinColors();
            if (!isset($skinColors[$type])) {
                $types = implode(", ", array_keys($skinColors));
                LogUtility::msg("The type value ($type) is not supported. Only the following types value may be used: $types", LogUtility::LVL_MSG_WARNING, self::CANONICAL);
            } else {
                $color = $skinColors[$type];
                switch ($skinValue) {
                    case "contained":
                        $attributes->addStyleDeclarationIfNotSet(ColorRgb::COLOR, $color[ColorRgb::COLOR]);
                        $attributes->addStyleDeclarationIfNotSet(Background::BACKGROUND_COLOR, $color[Background::BACKGROUND_COLOR]);
                        $attributes->addStyleDeclarationIfNotSet(ColorRgb::BORDER_COLOR, $color[ColorRgb::BORDER_COLOR]);
                        Shadow::addMediumElevation($attributes);
                        break;
                    case self::FILLED_VALUE:
                    case "solid":
                        $attributes->addStyleDeclarationIfNotSet(ColorRgb::COLOR, $color[ColorRgb::COLOR]);
                        $attributes->addStyleDeclarationIfNotSet(Background::BACKGROUND_COLOR, $color[Background::BACKGROUND_COLOR]);
                        $attributes->addStyleDeclarationIfNotSet(ColorRgb::BORDER_COLOR, $color[ColorRgb::BORDER_COLOR]);
                        break;
                    case "outline":
                        $primaryColor = $color[ColorRgb::COLOR];
                        if ($primaryColor === "#fff") {
                            $primaryColor = $color[Background::BACKGROUND_COLOR];
                        }
                        $attributes->addStyleDeclarationIfNotSet(ColorRgb::COLOR, $primaryColor);
                        $attributes->addStyleDeclarationIfNotSet(Background::BACKGROUND_COLOR, "transparent");
                        $borderColor = $color[Background::BACKGROUND_COLOR];
                        if ($attributes->hasStyleDeclaration(ColorRgb::BORDER_COLOR)) {
                            // Color in the `border` attribute
                            // takes precedence in the `border-color` if located afterwards
                            // We don't take the risk
                            $borderColor = $attributes->getAndRemoveStyleDeclaration(ColorRgb::BORDER_COLOR);
                        }
                        $attributes->addStyleDeclarationIfNotSet("border", "1px solid " . $borderColor);

                        break;
                    case "text":
                        $primaryColor = $color[ColorRgb::COLOR];
                        if ($primaryColor === "#fff") {
                            $primaryColor = $color[Background::BACKGROUND_COLOR];
                        }
                        $attributes->addStyleDeclarationIfNotSet(ColorRgb::COLOR, "$primaryColor!important");
                        $attributes->addStyleDeclarationIfNotSet(Background::BACKGROUND_COLOR, "transparent");
                        $attributes->addStyleDeclarationIfNotSet(ColorRgb::BORDER_COLOR, "transparent");
                        break;
                }
            }
        }
    }

}
