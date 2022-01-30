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
            Color::COLOR => "#0c5460",
            Background::BACKGROUND_COLOR => "#d1ecf1",
            Color::BORDER_COLOR => "#bee5eb"
        ),
        "tip" => array(
            Color::COLOR => "#6c6400",
            Background::BACKGROUND_COLOR => "#fff79f",
            Color::BORDER_COLOR => "#FFF78c"
        ),
        "warning" => array(
            Color::COLOR => "#856404",
            Background::BACKGROUND_COLOR => "#fff3cd",
            Color::BORDER_COLOR => "#ffeeba"
        ),
        "success" => array(
            Color::COLOR => "#fff",
            Background::BACKGROUND_COLOR => "#28a745",
            Color::BORDER_COLOR => "#28a745"
        ),
        "danger" => array(
            Color::COLOR => "#fff",
            Background::BACKGROUND_COLOR => "#dc3545",
            Color::BORDER_COLOR => "#dc3545"
        ),
        "dark" => array(
            Color::COLOR => "#fff",
            Background::BACKGROUND_COLOR => "#343a40",
            Color::BORDER_COLOR => "#343a40"
        ),
        "light" => array(
            Color::COLOR => "#fff",
            Background::BACKGROUND_COLOR => "#f8f9fa",
            Color::BORDER_COLOR => "#f8f9fa"
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
        $brandingColors = array(Color::PRIMARY_VALUE => array(
            Color::COLOR => "#fff",
            Background::BACKGROUND_COLOR => $primaryColor,
            Color::BORDER_COLOR => $primaryColor
        ),
            Color::SECONDARY_VALUE => array(
                Color::COLOR => "#fff",
                Background::BACKGROUND_COLOR => $secondaryColor,
                Color::BORDER_COLOR => $secondaryColor
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
                        $attributes->addStyleDeclarationIfNotSet(Color::COLOR, $color[Color::COLOR]);
                        $attributes->addStyleDeclarationIfNotSet(Background::BACKGROUND_COLOR, $color[Background::BACKGROUND_COLOR]);
                        $attributes->addStyleDeclarationIfNotSet(Color::BORDER_COLOR, $color[Color::BORDER_COLOR]);
                        Shadow::addMediumElevation($attributes);
                        break;
                    case self::FILLED_VALUE:
                    case "solid":
                        $attributes->addStyleDeclarationIfNotSet(Color::COLOR, $color[Color::COLOR]);
                        $attributes->addStyleDeclarationIfNotSet(Background::BACKGROUND_COLOR, $color[Background::BACKGROUND_COLOR]);
                        $attributes->addStyleDeclarationIfNotSet(Color::BORDER_COLOR, $color[Color::BORDER_COLOR]);
                        break;
                    case "outline":
                        $primaryColor = $color[Color::COLOR];
                        if ($primaryColor === "#fff") {
                            $primaryColor = $color[Background::BACKGROUND_COLOR];
                        }
                        $attributes->addStyleDeclarationIfNotSet(Color::COLOR, $primaryColor);
                        $attributes->addStyleDeclarationIfNotSet(Background::BACKGROUND_COLOR, "transparent");
                        $borderColor = $color[Background::BACKGROUND_COLOR];
                        if ($attributes->hasStyleDeclaration(Color::BORDER_COLOR)) {
                            // Color in the `border` attribute
                            // takes precedence in the `border-color` if located afterwards
                            // We don't take the risk
                            $borderColor = $attributes->getAndRemoveStyleDeclaration(Color::BORDER_COLOR);
                        }
                        $attributes->addStyleDeclarationIfNotSet("border", "1px solid " . $borderColor);

                        break;
                    case "text":
                        $primaryColor = $color[Color::COLOR];
                        if ($primaryColor === "#fff") {
                            $primaryColor = $color[Background::BACKGROUND_COLOR];
                        }
                        $attributes->addStyleDeclarationIfNotSet(Color::COLOR, "$primaryColor!important");
                        $attributes->addStyleDeclarationIfNotSet(Background::BACKGROUND_COLOR, "transparent");
                        $attributes->addStyleDeclarationIfNotSet(Color::BORDER_COLOR, "transparent");
                        break;
                }
            }
        }
    }

}
