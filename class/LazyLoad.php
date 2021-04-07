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
 * This one support background loading
 * https://github.com/ApoorvSaxena/lozad.js
 *
 *
 * TODO: implement no script pattern ? the https://github.com/aFarkas/lazysizes#the-noscript-pattern
 *
 */
class LazyLoad
{

    /**
     * Lozad was choosen because
     * it was easier to add svg injection
     * it supports background image
     * it's most used (JsDelivr stats)
     */
    const ACTIVE = self::LOZAD_ID;

    /**
     * The id of the lazy loaders
     */
    const LAZY_SIDE_ID = "lazy-sizes";
    const LOZAD_ID = "lozad";


    public static function addSnippet()
    {
        switch (self::ACTIVE) {
            case self::LAZY_SIDE_ID:
                LazyLoad::addLazySizesSnippet();
                break;
            case self::LOZAD_ID:
                LazyLoad::addLozadSnippet();
                break;
            default:
                throw new \RuntimeException("The active lazy loaded is unknown (" . self::ACTIVE . ")");
        }

    }

    /**
     * Add the lazy sizes snippet
     */
    private static function addLazySizesSnippet()
    {

        $snippetManager = PluginUtility::getSnippetManager();

        $snippetManager->upsertTagsForBar(self::LAZY_SIDE_ID,
            array(
                'script' => [
                    array(
                        "src" => "https://cdn.jsdelivr.net/npm/lazysizes@5.3.1/lazysizes.min.js",
                        "integrity" => "sha256-bmG+LzdKASJRACVXiUC69++Nu8rz7MX1U1z8gb0c/Tk=",
                        "crossorigin" => "anonymous"
                    )
                ]
            )
        );
        /**
         * The Spinner effect
         * lazysizes adds the class lazy loading while the images are loading
         * and the class lazyloaded as soon as the image is loaded.
         */
        $snippetManager->upsertCssSnippetForBar(self::LAZY_SIDE_ID);

    }

    public static function getPlaceholderAttributes($srcValue)
    {
        $placeholder = "";
        switch (self::ACTIVE) {
            case self::LAZY_SIDE_ID:
                // Modern transparent srcset pattern
                // normal src attribute with a transparent or low quality image as srcset value
                // https://github.com/aFarkas/lazysizes/#modern-transparent-srcset-pattern
                $placeholder = "src=\"$srcValue\"";
                $placeholder .= " srcset=\"data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==\"";
                break;
            case self::LOZAD_ID:
                // https://github.com/ApoorvSaxena/lozad.js#large-image-improvment
                $placeholder = "data-placeholder-background=\"#e5534b\"";
                break;
        }

        return $placeholder;
    }

    /**
     * Add lozad
     * Support background image
     * https://github.com/ApoorvSaxena/lozad.js
     */
    public static function addLozadSnippet()
    {

        $snippetManager = PluginUtility::getSnippetManager();

        // https://www.jsdelivr.com/package/npm/lozad
        $snippetManager->upsertTagsForBar(self::LOZAD_ID,
            array(
                'script' => [
                    array(
                        "src"=>"https://cdn.jsdelivr.net/npm/lozad@1.16.0/dist/lozad.min.js",
                        "integrity"=>"sha256-mOFREFhqmHeQbXpK2lp4nA3qooVgACfh88fpJftLBbc=",
                        "crossorigin"=>"anonymous"

                    )
                ]
            )
        );

        /**
         * The snippet depend on the image type and features
         * and was added in the code
         */


    }

    /**
     * Class selector to identify the element to lazy load
     */
    public static function getClass()
    {
        switch (self::ACTIVE) {
            case self::LAZY_SIDE_ID:
                return "lazyload";
            case self::LOZAD_ID:
                return "lozad";
            default:
                throw new \RuntimeException("The active lazy loaded is unknown (" . self::ACTIVE . ")");
        }
    }
}
