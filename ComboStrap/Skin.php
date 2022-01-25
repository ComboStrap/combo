<?php


namespace ComboStrap;

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
            ColorUtility::COLOR => "#0c5460",
            Background::BACKGROUND_COLOR => "#d1ecf1",
            ColorUtility::BORDER_COLOR => "#bee5eb"
        ),
        "tip" => array(
            ColorUtility::COLOR => "#6c6400",
            Background::BACKGROUND_COLOR => "#fff79f",
            ColorUtility::BORDER_COLOR => "#FFF78c"
        ),
        "warning" => array(
            ColorUtility::COLOR => "#856404",
            Background::BACKGROUND_COLOR => "#fff3cd",
            ColorUtility::BORDER_COLOR => "#ffeeba"
        ),
        "success" => array(
            ColorUtility::COLOR => "#fff",
            Background::BACKGROUND_COLOR => "#28a745",
            ColorUtility::BORDER_COLOR => "#28a745"
        ),
        "danger" => array(
            ColorUtility::COLOR => "#fff",
            Background::BACKGROUND_COLOR => "#dc3545",
            ColorUtility::BORDER_COLOR => "#dc3545"
        ),
        "dark" => array(
            ColorUtility::COLOR => "#fff",
            Background::BACKGROUND_COLOR => "#343a40",
            ColorUtility::BORDER_COLOR => "#343a40"
        ),
        "light" => array(
            ColorUtility::COLOR => "#fff",
            Background::BACKGROUND_COLOR => "#f8f9fa",
            ColorUtility::BORDER_COLOR => "#f8f9fa"
        )
    );

    public static function getSkinColors(): array
    {
        $primaryColor = Site::getPrimaryColor();
        if ($primaryColor === null) {
            $primaryColor = "#007bff";
        }
        $secondaryColor = Site::getSecondaryColor();
        if ($secondaryColor === null) {
            $secondaryColor = "#6c757d";
        }
        $brandingColors = array(ColorUtility::PRIMARY_VALUE => array(
            ColorUtility::COLOR => "#fff",
            Background::BACKGROUND_COLOR => $primaryColor,
            ColorUtility::BORDER_COLOR => $primaryColor
        ),
            ColorUtility::SECONDARY_VALUE => array(
                ColorUtility::COLOR => "#fff",
                Background::BACKGROUND_COLOR => $secondaryColor,
                ColorUtility::BORDER_COLOR => $secondaryColor
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
            $skinColors = self::getSkinColors();
            if (!isset($skinColors[$type])) {
                $types = implode(", ", array_keys($skinColors));
                LogUtility::msg("The type value ($type) is not supported. Only the following types value may be used: $types", LogUtility::LVL_MSG_WARNING, self::CANONICAL);
            } else {
                $color = $skinColors[$type];
                switch ($skinValue) {
                    case "contained":
                        $attributes->addStyleDeclarationIfNotSet(ColorUtility::COLOR, $color[ColorUtility::COLOR]);
                        $attributes->addStyleDeclarationIfNotSet(Background::BACKGROUND_COLOR, $color[Background::BACKGROUND_COLOR]);
                        $attributes->addStyleDeclarationIfNotSet(ColorUtility::BORDER_COLOR, $color[ColorUtility::BORDER_COLOR]);
                        Shadow::addMediumElevation($attributes);
                        break;
                    case self::FILLED_VALUE:
                    case "solid":
                        $attributes->addStyleDeclarationIfNotSet(ColorUtility::COLOR, $color[ColorUtility::COLOR]);
                        $attributes->addStyleDeclarationIfNotSet(Background::BACKGROUND_COLOR, $color[Background::BACKGROUND_COLOR]);
                        $attributes->addStyleDeclarationIfNotSet(ColorUtility::BORDER_COLOR, $color[ColorUtility::BORDER_COLOR]);
                        break;
                    case "outline":
                        $primaryColor = $color[ColorUtility::COLOR];
                        if ($primaryColor === "#fff") {
                            $primaryColor = $color[Background::BACKGROUND_COLOR];
                        }
                        $attributes->addStyleDeclarationIfNotSet(ColorUtility::COLOR, $primaryColor);
                        $attributes->addStyleDeclarationIfNotSet(Background::BACKGROUND_COLOR, "transparent");
                        $borderColor = $color[Background::BACKGROUND_COLOR];
                        if ($attributes->hasStyleDeclaration(ColorUtility::BORDER_COLOR)) {
                            // Color in the `border` attribute
                            // takes precedence in the `border-color` if located afterwards
                            // We don't take the risk
                            $borderColor = $attributes->getAndRemoveStyleDeclaration(ColorUtility::BORDER_COLOR);
                        }
                        $attributes->addStyleDeclarationIfNotSet("border", "1px solid " . $borderColor);

                        break;
                    case "text":
                        $primaryColor = $color[ColorUtility::COLOR];
                        if ($primaryColor === "#fff") {
                            $primaryColor = $color[Background::BACKGROUND_COLOR];
                        }
                        $attributes->addStyleDeclarationIfNotSet(ColorUtility::COLOR, "$primaryColor!important");
                        $attributes->addStyleDeclarationIfNotSet(Background::BACKGROUND_COLOR, "transparent");
                        $attributes->addStyleDeclarationIfNotSet(ColorUtility::BORDER_COLOR, "transparent");
                        break;
                }
            }
        }
    }

}
