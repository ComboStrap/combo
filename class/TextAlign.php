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
    public static function processTextAlign(&$attributes){

        if ($attributes->hasComponentAttribute(self::ATTRIBUTE_NAME)) {
            $textAlignValue = trim($attributes->getValueAndRemove(self::ATTRIBUTE_NAME));

            $bootstrapMajorVersion = Bootstrap::getBootStrapMajorVersion();
            if ($bootstrapMajorVersion==Bootstrap::BootStrapFourMajorVersion) {
                $attributes->addStyleDeclaration(self::ATTRIBUTE_NAME, $textAlignValue);
            } else {
                // Bootstrap 5
                switch ($textAlignValue){
                    case "start":
                    case "left": // from bs4
                        $attributes->addClassName("text-start");
                        break;
                    case "end":
                    case "right": // from bs4
                        $attributes->addClassName("text-end");
                        break;
                    case "center":
                        $attributes->addClassName("text-center");
                        break;
                    case "justify":
                        $attributes->addStyleDeclaration(self::ATTRIBUTE_NAME, $textAlignValue);
                        break;
                    default:
                        LogUtility::msg("The text-align value ($textAlignValue) is unknown.",self::CANONICAL);
                        break;
                }
            }
        }
    }
}
