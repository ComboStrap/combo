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
class Sticky
{
    const STICKY_ATTRIBUTE = "sticky";
    const STICKY = "sticky";
    const STICKY_CLASS = "combo-sticky";

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
}
