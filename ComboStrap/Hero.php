<?php

namespace ComboStrap;

class Hero
{

    const COMPONENT_NAME = "hero";
    const CANONICAL = self::COMPONENT_NAME;
    const ATTRIBUTE = self::COMPONENT_NAME;


    public static function processHero(TagAttributes &$attributes)
    {

        $hero = $attributes->getValueAndRemove(self::ATTRIBUTE);
        if ($hero === null) {
            return;
        }
        try {
            switch ($hero) {
                case "sm":
                case "small":
                    $attributes->addClassName(self::COMPONENT_NAME . "-sm");
                    break;
                case "md":
                case "medium":
                    $attributes->addClassName(self::COMPONENT_NAME . "-md");
                    break;
                case "lg":
                case "large":
                    $attributes->addClassName(self::COMPONENT_NAME . "-lg");
                    break;
                case "xl":
                case "extra-large":
                    $attributes->addClassName(self::COMPONENT_NAME . "-xl");
                    break;
                default:
                    throw new ExceptionBadArgument("The hero value ($hero) is unknown and was not applied");
            }
            /**
             * We could have used bootstrap specific class such as
             * `px-4 py-2`
             * but the unit scale goes only to 5 (=3 rem) and
             * the `xl` hero goes to 4 rem
             */
            PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot(self::COMPONENT_NAME);
        } catch (ExceptionBadArgument $e) {
            LogUtility::error($e->getMessage(), self::CANONICAL);
        }

    }
}
