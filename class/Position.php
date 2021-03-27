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
 * Class Sticky
 * Manage the stickiness of component
 * with https://sticksy.js.org/
 * @package ComboStrap
 */
class Position
{
    const STICKY_ATTRIBUTE = "sticky";
    const STICKY = "sticky";
    const STICKY_CLASS = "combo-sticky";

    const POSITION_ATTRIBUTE = "position";
    const POSITION_SNIPPET_ID = "position";
    const POSITION_QUARTILE_SNIPPET_ID = "position-quartile";

    /**
     * Process stickiness
     * @param $attributes
     */
    public static function processStickiness(&$attributes)
    {
        if (isset($attributes[self::STICKY_ATTRIBUTE])) {
            $sticky = strtolower($attributes[self::STICKY_ATTRIBUTE]);
            unset($attributes[self::STICKY_ATTRIBUTE]);
            if ($sticky == "true") {
                $stickyClass = self::STICKY_CLASS;
                PluginUtility::addClass2Attributes($stickyClass, $attributes);
                $snippetManager = PluginUtility::getSnippetManager();
                $snippetManager->upsertHeadTagsForBar(self::STICKY,
                    array(
                        "script" => [
                            array(
                                "src" => "https://cdn.jsdelivr.net/npm/sticksy@0.2.0/dist/sticksy.min.js",
                                "integrity" => "sha256-H6uQ878/jyt6w1oBNhL6s01iAfWxACrWvVXCBjZsrGM=",
                                "crossorigin" => "anonymous"
                            )]
                    ));
                /**
                 * If top bar
                 */
                $topSpacing = Site::getTopSpacing();
                $jsSnippet = <<<EOF
var stickyElements = Sticksy.initializeAll('.$stickyClass',{topSpacing: $topSpacing})
EOF;
                $snippetManager->upsertJavascriptForBar(self::STICKY, $jsSnippet);
            }

        }

    }

    public static function processPosition(&$attributes)
    {
        if (isset($attributes[self::POSITION_ATTRIBUTE])) {
            $position = strtolower($attributes[self::POSITION_ATTRIBUTE]);
            unset($attributes[self::POSITION_ATTRIBUTE]);
            if (Bootstrap::getBootStrapMajorVersion() < Bootstrap::BootStrapFiveMajorVersion) {
                $snippetManager = PluginUtility::getSnippetManager();
                $snippetManager->upsertCssSnippetForBar(self::POSITION_SNIPPET_ID);
            }

            // Class value comes from
            // https://getbootstrap.com/docs/5.0/utilities/position/#center-elements
            switch ($position) {
                case "top-left":
                case "left-top":
                    PluginUtility::addClass2Attributes("position-absolute top-0 start-0 translate-middle", $attributes);
                    break;
                case "top-quartile-1":
                case "quartile-1-top":
                    self::addQuartileCss();
                    PluginUtility::addClass2Attributes("position-absolute top-0 start-25 translate-middle", $attributes);
                    break;
                case "top-center":
                case "center-top":
                    PluginUtility::addClass2Attributes("position-absolute top-0 start-50 translate-middle", $attributes);
                    break;
                case "top-quartile-3":
                case "quartile-3-top":
                    self::addQuartileCss();
                    PluginUtility::addClass2Attributes("position-absolute top-0 start-75 translate-middle", $attributes);
                    break;
                case "top-right":
                case "right-top":
                    PluginUtility::addClass2Attributes("position-absolute top-0 start-100 translate-middle", $attributes);
                    break;
                case "left-quartile-1":
                case "quartile-1-left":
                    self::addQuartileCss();
                    PluginUtility::addClass2Attributes("position-absolute top-25 start-0 translate-middle", $attributes);
                    break;
                case "left-center":
                case "center-left":
                    PluginUtility::addClass2Attributes("position-absolute top-50 start-0 translate-middle", $attributes);
                    break;
                case "center-center":
                case "center":
                    PluginUtility::addClass2Attributes("position-absolute top-50 start-50 translate-middle", $attributes);
                    break;
                case "right-center":
                case "center-right":
                    PluginUtility::addClass2Attributes("position-absolute top-50 start-100 translate-middle", $attributes);
                    break;
                case "bottom-left":
                case "left-bottom":
                    PluginUtility::addClass2Attributes("position-absolute top-100 start-0 translate-middle", $attributes);
                    break;
                case "bottom-center":
                case "center-bottom":
                    PluginUtility::addClass2Attributes("position-absolute top-100 start-50 translate-middle", $attributes);
                    break;
                case "bottom-right":
                case "right-bottom":
                    PluginUtility::addClass2Attributes("position-absolute top-100 start-100 translate-middle", $attributes);
                    break;

            }
        }

    }

    private static function addQuartileCss()
    {
        $snippetManager = PluginUtility::getSnippetManager();
        $snippetManager->upsertCssSnippetForBar(self::POSITION_QUARTILE_SNIPPET_ID);
    }
}
