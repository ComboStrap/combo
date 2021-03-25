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
 * Class Animation
 * Manage the animation
 * @package ComboStrap
 */
class Animation
{
    const ON_HOVER_ATTRIBUTE = "onhover";
    const HOVER = "hover";

    const ON_VIEW_ATTRIBUTE = "onview";
    const ON_VIEW_ID = "onview";
    const WOW_CLASS = "wow";


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
                    PluginUtility::getSnippetManager()->upsertHeadTagsForBar(self::HOVER,
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

    /**
     * Based on https://wowjs.uk/
     * @param $attributes
     */
    public static function processOnView(&$attributes)
    {
        if (isset($attributes[self::ON_VIEW_ATTRIBUTE])) {
            $onView = $attributes[self::ON_VIEW_ATTRIBUTE];
            unset($attributes[self::ON_VIEW_ATTRIBUTE]);


            $wowClass = self::WOW_CLASS;
            PluginUtility::addClass2Attributes($wowClass,$attributes);
            $animationClass = "animate__".$onView;
            PluginUtility::addClass2Attributes($animationClass,$attributes);

            $snippetManager = PluginUtility::getSnippetManager();


            $snippetManager->upsertCssSnippetForBar(self::ON_VIEW_ID);

            /**
             * https://www.delac.io/wow/docs.html
             * Offset: Define the distance between the bottom of browser viewport and the top of hidden box.
             *         When the user scrolls and reach this distance the hidden box is revealed.
             * Live  : Constantly check for new WOW elements on the page.
             */
            $animateClass = "animate__animated";
            $js = <<<EOF
window.addEventListener("load", function(event) {
    var wow = new WOW(
      {
        boxClass:     '$wowClass',      // animated element css class (default is wow)
        animateClass: '$animateClass', // animation css class (default is animated)
        offset:       0,          // distance to the element when triggering the animation (default is 0)
        mobile:       true,       // trigger animations on mobile devices (default is true)
        live:         false,       // act on asynchronously loaded content (default is true)
        callback:     function(box) {
          // the callback is fired every time an animation is started
          // the argument that is passed in is the DOM node being animated
        },
        scrollContainer: null // optional scroll container selector, otherwise use window
      }
    );
    wow.init();
});
EOF;
            $snippetManager->upsertJavascriptForBar(self::ON_VIEW_ID,$js);
            $snippetManager->upsertHeadTagsForBar(self::ON_VIEW_ID,

                array(
                    "script" =>
                    [
                        array(
                            "src" => "https://cdn.jsdelivr.net/npm/wowjs@1.1.3/dist/wow.min.js",
                            "integrity" => "sha256-gHiUEskgBO+3ccSDRM+c5+nEwTGp64R99KYPfITpnuo=",
                            "crossorigin" => "anonymous"
                        )
                    ],
                    "link" =>
                        [
                            array(
                                "rel" => "stylesheet",
                                "href" => "https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css",
                                "integrity" => "sha256-X7rrn44l1+AUO65h1LGALBbOc5C5bOstSYsNlv9MhT8=",
                                "crossorigin" => "anonymous"
                            )
                        ]
                ));
        }

    }

}
