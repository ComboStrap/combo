<?php

namespace ComboStrap;


class TextAlign
{

    const ATTRIBUTE_NAME = "text-align";
    const CANONICAL = "text-align";


    /**
     * @param TagAttributes $attributes
     * https://getbootstrap.com/docs/5.0/utilities/text/#text-alignment
     */
    public static function processTextAlign(&$attributes)
    {

        if ($attributes->hasComponentAttribute(self::ATTRIBUTE_NAME)) {

            LogUtility::warning("The text-align attribute has been deprecated for the align attribute.", self::CANONICAL);

            try {
                $textAlignValues = $attributes->getValuesAndRemove(self::ATTRIBUTE_NAME);
            } catch (ExceptionBadArgument $e) {
                LogUtility::error("Unable to retrieve the tex-align attribute. Error: {$e->getMessage()}", self::CANONICAL);
                return;
            }
            foreach ($textAlignValues as $textAlignValue) {
                try {
                    $conditionalTextAlignValue = ConditionalLength::createFromString($textAlignValue);
                } catch (ExceptionBadArgument $e) {
                    LogUtility::error("The text-align value($textAlignValue) is not valid. Error: {$e->getMessage()}", self::CANONICAL);
                    return;
                }

                $bootstrapMajorVersion = Bootstrap::getBootStrapMajorVersion();
                if ($bootstrapMajorVersion == Bootstrap::BootStrapFourMajorVersion) {
                    $breakpoint = $conditionalTextAlignValue->getBreakpoint();
                    if (!empty($breakpoint)) {
                        LogUtility::msg("Bootstrap 4 does not support conditional value for the attribute (" . self::ATTRIBUTE_NAME . "). Therefore, the value ($textAlignValue) cannot be applied", LogUtility::LVL_MSG_WARNING, self::CANONICAL);
                    }
                    $value = $conditionalTextAlignValue->getLength();
                    // Bootstrap 4
                    switch ($value) {
                        case "left":
                        case "start":
                            $attributes->addStyleDeclarationIfNotSet(self::ATTRIBUTE_NAME, "left");
                            break;
                        case "right":
                        case "end":
                            $attributes->addStyleDeclarationIfNotSet(self::ATTRIBUTE_NAME, "right");
                            break;
                        case "center":
                        case "justify":
                            $attributes->addStyleDeclarationIfNotSet(self::ATTRIBUTE_NAME, $value);
                            break;
                        default:
                            LogUtility::msg("The text-align value ($value) is unknown.", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                            break;
                    }

                } else {
                    $breakpoint = $conditionalTextAlignValue->getBreakpoint();
                    if (!empty($breakpoint)) {
                        switch ($breakpoint) {
                            case "sm":
                            case "small":
                                $breakpoint = "sm";
                                break;
                            case "md":
                            case "medium":
                                $breakpoint = "md";
                                break;
                            case "lg":
                            case "large":
                                $breakpoint = "lg";
                                break;
                            case "xl":
                            case "extra-large":
                                $breakpoint = "xl";
                                break;
                            default:
                                LogUtility::msg("The breakpoint ($breakpoint) of the text-align value ($textAlignValue) is not correct.", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                                $breakpoint = "";
                                break;
                        }
                    }
                    $value = $conditionalTextAlignValue->getLength();
                    // Bootstrap 5
                    switch ($value) {
                        case "start":
                        case "left": // from bs4
                            $valueClass = "start";
                            if (empty($breakpoint)) {
                                $attributes->addClassName("text-$valueClass");
                            } else {
                                $attributes->addClassName("text-$breakpoint-$valueClass");
                            }
                            break;
                        case "end":
                        case "right": // from bs4
                            $valueClass = "end";
                            if (empty($breakpoint)) {
                                $attributes->addClassName("text-$valueClass");
                            } else {
                                $attributes->addClassName("text-$breakpoint-$valueClass");
                            }
                            break;
                        case "center":
                            $valueClass = "center";
                            if (empty($breakpoint)) {
                                $attributes->addClassName("text-$valueClass");
                            } else {
                                $attributes->addClassName("text-$breakpoint-$valueClass");
                            }
                            break;
                        case "justify":
                            if (!empty($breakpoint)) {
                                LogUtility::msg("The `justify` value of the text-align attribute does not support actually breakpoint. The breakpoint value ($$breakpoint) was then not applied.", LogUtility::LVL_MSG_WARNING, self::CANONICAL);
                            }
                            $attributes->addStyleDeclarationIfNotSet(self::ATTRIBUTE_NAME, $value);
                            break;
                        default:
                            LogUtility::msg("The text-align value ($value) is unknown.", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                            break;
                    }
                }
            }
        }
    }
}
