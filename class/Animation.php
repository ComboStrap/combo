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
    const ON_HOVER_SNIPPET_ID = "onhover";

    const ON_VIEW_ATTRIBUTE = "onview";
    const ON_VIEW_ID = "onview";
    const ANIMATE_CLASS = "animate__animated";

    /**
     * Supported Animation name of hover
     * float and grow are not in
     */
    const HOVER_ANIMATIONS = ["shrink", "pulse", "pulse-grow", "pulse-shrink", "push", "pop", "bounce-in", "bounce-out", "rotate", "grow-rotate", "sink", "bob", "hang", "skew", "skew-forward", "skew-backward", "wobble-horizontal", "wobble-vertical", "wobble-to-bottom-right", "wobble-to-top-right", "wobble-top", "wobble-bottom", "wobble-skew", "buzz", "buzz-out", "forward", "backward", "fade", "back-pulse", "sweep-to-right", "sweep-to-left", "sweep-to-bottom", "sweep-to-top", "bounce-to-right", "bounce-to-left", "bounce-to-bottom", "bounce-to-top", "radial-out", "radial-in", "rectangle-in", "rectangle-out", "shutter-in-horizontal", "shutter-out-horizontal", "shutter-in-vertical", "shutter-out-vertical", "icon-back", "hollow", "trim", "ripple-out", "ripple-in", "outline-out", "outline-in", "round-corners", "underline-from-left", "underline-from-center", "underline-from-right", "reveal", "underline-reveal", "overline-reveal", "overline-from-left", "overline-from-center", "overline-from-right", "grow-shadow", "float-shadow", "glow", "shadow-radial", "box-shadow-outset", "box-shadow-inset", "bubble-top", "bubble-right", "bubble-bottom", "bubble-left", "bubble-float-top", "bubble-float-right", "bubble-float-bottom", "bubble-float-left", "curl-top-left", "curl-top-right", "curl-bottom-right", "curl-bottom-left"];

    /**
     * Process hover animation
     * @param TagAttributes $attributes
     */
    public static function processOnHover(&$attributes)
    {
        if ($attributes->hasAttribute(self::ON_HOVER_ATTRIBUTE)) {
            $hover = strtolower($attributes->getValueAndRemove(self::ON_HOVER_ATTRIBUTE));
            $hoverAnimations = preg_split("/\s/", $hover);

            $comboDataHoverClasses = "";
            foreach ($hoverAnimations as $hover) {

                if (in_array($hover, self::HOVER_ANIMATIONS)) {
                    PluginUtility::getSnippetManager()->upsertHeadTagsForBar(self::ON_HOVER_SNIPPET_ID,
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
                    $attributes->addClassName("hvr-$hover");

                } else {

                    /**
                     *
                     */
                    if (in_array($hover, ["float", "grow"])) {
                        $hover = "combo-" . $hover;
                    }

                    /**
                     * Shadow translation between animation name
                     * and class
                     */
                    switch ($hover){
                        case "shadow":
                            $hover = Shadow::getDefaultClass();
                            break;
                        case "shadow-md":
                            $hover = Shadow::MEDIUM_ELEVATION_CLASS;
                            break;
                        case "shadow-lg":
                            $hover = "shadow";
                            break;
                        case "shadow-xl":
                            $hover = "shadow-lg";
                            break;
                    }

                    /**
                     * Add it to the list of class
                     */
                    $comboDataHoverClasses .= " " . $hover;

                }

            }
            if (!empty($comboDataHoverClasses)) {

                // Grow, float and easing are in the css
                PluginUtility::getSnippetManager()->upsertCssSnippetForBar(self::ON_HOVER_SNIPPET_ID);

                // Smooth Transition in and out of hover
                $attributes->addClassName("combo-hover-easing");

                $attributes->addAttributeValue("data-hover-class", trim($comboDataHoverClasses));

                // The javascript that manage the hover effect by adding the class in the data-hover class
                PluginUtility::getSnippetManager()->upsertJavascriptForBar(self::ON_HOVER_SNIPPET_ID);

            }

        }

    }

    /**
     * Based on https://wowjs.uk/
     * @param TagAttributes $attributes
     */
    public static function processOnView(&$attributes)
    {
        if ($attributes->hasAttribute(self::ON_VIEW_ATTRIBUTE)) {
            $onView = $attributes->getValueAndRemove(self::ON_VIEW_ATTRIBUTE);

            $animateClass = self::ANIMATE_CLASS;
            $attributes->addClassName($animateClass);

            $animationClass = "animate__" . $onView;
            $attributes->addAttributeValue("data-animated-class", $animationClass);

            // TODO: Add attributes
            //$delay = "animate__delay-2s";
            //PluginUtility::addClass2Attributes($delay, $attributes);

            $snippetManager = PluginUtility::getSnippetManager();

            self::scrollMagicInit();

            $snippetManager->upsertHeadTagsForBar(self::ON_VIEW_ID,

                array(

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

    /**
     * https://www.delac.io/wow/docs.html
     * Offset: Define the distance between the bottom of browser viewport and the top of hidden box.
     *         When the user scrolls and reach this distance the hidden box is revealed.
     * Live  : Constantly check for new WOW elements on the page.
     * @param $attributes
     * @deprecated - wow permits only one trigger by animation
     */
    private static function wowInit(&$attributes)
    {
        $snippetManager = PluginUtility::getSnippetManager();
        $wowClass = "wow";
        $wowSnippetId = "wow";
        PluginUtility::addClass2Attributes($wowClass, $attributes);
        $snippetManager->upsertCssSnippetForBar($wowSnippetId);


        $animateClass = self::ANIMATE_CLASS;
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
        $snippetManager->upsertJavascriptForBar($wowSnippetId, $js);
        $snippetManager->upsertHeadTagsForBar($wowSnippetId,

            array(
                "script" =>
                    [
                        array(
                            "src" => "https://cdn.jsdelivr.net/npm/wowjs@1.1.3/dist/wow.min.js",
                            "integrity" => "sha256-gHiUEskgBO+3ccSDRM+c5+nEwTGp64R99KYPfITpnuo=",
                            "crossorigin" => "anonymous"
                        )
                    ],

            ));
    }

    /**
     * https://scrollmagic.io/docs/index.html
     */
    private static function scrollMagicInit()
    {
        $snippetManager = PluginUtility::getSnippetManager();

        $scrollMagicSnippetId = "scroll-magic";
        $snippetManager->upsertJavascriptForBar($scrollMagicSnippetId);
        $snippetManager->upsertHeadTagsForBar($scrollMagicSnippetId,
            array(
                "script" =>
                    [
                        array(
                            "src" => "https://cdnjs.cloudflare.com/ajax/libs/ScrollMagic/2.0.8/ScrollMagic.min.js",
                            "integrity" => "sha512-8E3KZoPoZCD+1dgfqhPbejQBnQfBXe8FuwL4z/c8sTrgeDMFEnoyTlH3obB4/fV+6Sg0a0XF+L/6xS4Xx1fUEg==",
                            "crossorigin" => "anonymous"
                        ),
                        array(
                            "src" => "https://cdnjs.cloudflare.com/ajax/libs/ScrollMagic/2.0.8/plugins/debug.addIndicators.min.js",
                            "integrity" => "sha512-RvUydNGlqYJapy0t4AH8hDv/It+zKsv4wOQGb+iOnEfa6NnF2fzjXgRy+FDjSpMfC3sjokNUzsfYZaZ8QAwIxg==",
                            "crossorigin" => "anonymous"
                        )
                    ],

            ));
    }

}
