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

/**
 * Class Hover
 * Manage the hover animation
 * @package ComboStrap
 */
class Hover
{
    const ON_HOVER_ATTRIBUTE = "onhover";
    const HOVER = "hover";

    /**
     * Process hover animation
     * @param $attributes
     */
    public static function processHover(&$attributes)
    {
        if (isset($attributes[self::ON_HOVER_ATTRIBUTE])) {
            $hover = strtolower($attributes[self::ON_HOVER_ATTRIBUTE]);
            unset($attributes[self::ON_HOVER_ATTRIBUTE]);
            switch ($hover) {
                case "grow":
                case "float":
                    PluginUtility::getSnippetManager()->upsertCssSnippetForBar(self::HOVER);
                    PluginUtility::addClass2Attributes("combo-hover-$hover", $attributes);
                    break;
                default:
                    PluginUtility::getSnippetManager()->upsertHeadTagsForBar(self::ON_HOVER_ATTRIBUTE,
                        array("link" =>
                            [
                                array(
                                    "rel" => "stylesheet",
                                    "href" => "https://cdnjs.cloudflare.com/ajax/libs/hover.css/2.3.1/css/hover-min.css",
                                    "integrity" => "sha512-csw0Ma4oXCAgd/d4nTcpoEoz4nYvvnk21a8VA2h2dzhPAvjbUIK6V3si7/g/HehwdunqqW18RwCJKpD7rL67Xg==",
                                    "crossorigin" => "anonymous"
                                )
                            ]
                        ));
                    PluginUtility::addClass2Attributes("hvr-$hover", $attributes);
                    break;
            }

        }

    }
}
