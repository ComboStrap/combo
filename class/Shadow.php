<?php
/**
 * Copyright (c) 2021. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;


class Shadow
{

    const ELEVATION_ATT = "elevation";
    const SHADOW_ATT = "shadow";
    const CANONICAL = "shadow";

    const CONF_DEFAULT_VALUE = "defaultShadowLevel";

    public static function process(&$attributes, &$styleProperties)
    {
        $elevationValue = "";

        if (array_key_exists(self::ELEVATION_ATT, $attributes)) {
            $elevationValue = $attributes[self::ELEVATION_ATT];
            unset($attributes[self::ELEVATION_ATT]);
        } else if (array_key_exists(self::SHADOW_ATT, $attributes)) {
            $elevationValue = $attributes[self::SHADOW_ATT];
            unset($attributes[self::SHADOW_ATT]);
        }

        if (!empty($elevationValue)) {

            switch ($elevationValue) {
                case "sm":
                    PluginUtility::addClass2Attributes("shadow-sm", $attributes);
                    break;
                case "md":
                    PluginUtility::addClass2Attributes("shadow", $attributes);
                    break;
                case "lg":
                case "high": // old value
                    PluginUtility::addClass2Attributes("shadow-lg", $attributes);
                    break;
                case "true":
                case true:
                case "1":
                    $defaultValue = PluginUtility::getConfValue(self::CONF_DEFAULT_VALUE);
                    switch ($defaultValue){
                        case "medium":
                            PluginUtility::addClass2Attributes("shadow", $attributes);
                            // Old deprecated: $styleProperties["box-shadow"] = "0px 3px 1px -2px rgba(0,0,0,0.2), 0px 2px 2px 0px rgba(0,0,0,0.14), 0px 1px 5px 0px rgba(0,0,0,0.12)";
                            break;
                        case "small":
                            PluginUtility::addClass2Attributes("shadow-sm", $attributes);
                            break;
                        case "large":
                            PluginUtility::addClass2Attributes("shadow-lg", $attributes);
                            // Old deprecated: $styleProperties["box-shadow"] = "0 0 0 .2em rgba(3,102,214,0),0 13px 27px -5px rgba(50,50,93,.25),0 8px 16px -8px rgba(0,0,0,.3),0 -6px 16px -6px rgba(0,0,0,.025)";
                            break;
                        default:
                            LogUtility::msg("Internal error: The default value ($defaultValue) is unknown", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                            break;
                    }
                    break;
                default:
                    LogUtility::msg("The value ($elevationValue) of the shadow/elevation property is unknown", LogUtility::LVL_MSG_ERROR, self::CANONICAL);

            }

            /**
             * Easing
             */
            $styleProperties["transition"] = ".2s";
            $styleProperties["transition-property"] = "color,box-shadow";

        }

    }

}
