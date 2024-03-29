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

use ComboStrap\TagAttribute\BackgroundAttribute;
use ComboStrap\TagAttribute\StyleAttribute;

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

    const CONF_LAZY_LOADING_PLACEHOLDER_COLOR = "lazyLoadingPlaceholderColor";

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


    const CANONICAL = "lazy";
    const DEFAULT_COLOR = "#cbf1ea";

    public const LAZY_LOAD_METHOD_HTML_VALUE = "html";
    public const LAZY_LOAD_METHOD_LOZAD_VALUE = "lozad";

    /**
     * The method on how to lazy load resources (Ie media)
     */
    public const LAZY_LOAD_METHOD = "lazy";
    /**
     * The default when the image are above the fold
     */
    public const LAZY_LOAD_METHOD_NONE_VALUE = "none";
    /**
     * Used internal for now on test
     */
    const CONF_LAZY_LOAD_METHOD = "internal-lazy-load-method-combo";
    public const CONF_RASTER_ENABLE = "rasterImageLazyLoadingEnable";
    public const CONF_RASTER_ENABLE_DEFAULT = 1;
    public const HTML_LOADING_ATTRIBUTE = "loading";

    /**
     * Used to select all lazy loaded
     * resources and load them before print
     */
    public static function getLazyClass(): string
    {
        return StyleAttribute::addComboStrapSuffix(self::CANONICAL);
    }

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

        $snippetManager->attachRemoteJavascriptLibrary(
            self::LAZY_SIDE_ID,
            "https://cdn.jsdelivr.net/npm/lazysizes@5.3.1/lazysizes.min.js",
            "sha256-bmG+LzdKASJRACVXiUC69++Nu8rz7MX1U1z8gb0c/Tk="
        );
        /**
         * The Spinner effect
         * lazysizes adds the class lazy loading while the images are loading
         * and the class lazyloaded as soon as the image is loaded.
         */
        $snippetManager->attachCssInternalStyleSheet(self::LAZY_SIDE_ID);

    }

    /**
     * @param TagAttributes $attributes
     */
    public static function addPlaceholderBackground(&$attributes)
    {
        // https://github.com/ApoorvSaxena/lozad.js#large-image-improvment
        $placeholderColor = LazyLoad::getPlaceholderColor();
        if ($attributes->hasComponentAttribute(BackgroundAttribute::BACKGROUND_COLOR)) {
            $placeholderColor = $attributes->getValueAndRemove(BackgroundAttribute::BACKGROUND_COLOR);
        }
        $attributes->addOutputAttributeValue("data-placeholder-background", "$placeholderColor");


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
        $snippetManager
            ->attachRemoteJavascriptLibrary(
                self::LOZAD_ID,
                "https://cdn.jsdelivr.net/npm/lozad@1.16.0/dist/lozad.min.js",
                "sha256-mOFREFhqmHeQbXpK2lp4nA3qooVgACfh88fpJftLBbc="
            )
            ->setDoesManipulateTheDomOnRun(false);

        /**
         * Add the fading effect
         */
        $snippetId = "lazy-load-fade";
        $snippetManager->attachCssInternalStyleSheet($snippetId);


        /**
         * Snippet to download the image before print
         *
         * The others javascript snippet to download lazy load depend on the image type
         * and features and was therefore added in the code for svg or raster
         */
        $snippetManager->attachJavascriptFromComponentId("lozad-print");


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

    /**
     * @return string - the lazy loading placeholder color
     */
    public static function getPlaceholderColor()
    {
        return SiteConfig::getConfValue(self::CONF_LAZY_LOADING_PLACEHOLDER_COLOR, self::DEFAULT_COLOR);
    }

    /**
     * The placeholder is not mandatory
     * but if present, it should have the same target ratio of the image
     *
     * This function is documenting this fact.
     *
     * @param null $imgTagWidth
     * @param null $imgTagHeight
     * @return string
     *
     *
     * Src is always set, this is the default
     * src attribute is served to browsers that do not take the srcset attribute into account.
     * When lazy loading, we set the srcset to a transparent image to not download the image in the src
     *
     */
    public static function getPlaceholder($imgTagWidth = null, $imgTagHeight = null): string
    {
        if ($imgTagWidth != null) {
            $svg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 $imgTagWidth $imgTagHeight'></svg>";
            /**
             * We encode it to be able to
             * use it in a `srcset` attribute that does not
             * want any space in the image definition
             */
            $svgBase64 = base64_encode($svg);
            $image = "data:image/svg+xml;base64,$svgBase64";
        } else {
            /**
             * Base64 transparent gif
             * 1x1 image, it will produce a square
             */
            $image = "data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==";
        }
        return $image;
    }

    /**
     * @return void
     * @deprecated use {@link SiteConfig::disableLazyLoad()}
     */
    public static function disable()
    {
        ExecutionContext::getActualOrCreateFromEnv()
            ->getConfig()
            ->disableLazyLoad();
    }

    /**
     *
     * By default, the image above the fold should not be lazy loaded
     * Above-the-fold images that are lazily loaded render later in the page lifecycle, which can delay the largest contentful paint.
     *
     *
     */
    public static function getDefault()
    {

        try {
            /**
             * Above-the-fold images that are lazily loaded render later in the page lifecycle,
             * which can delay the largest contentful paint.
             */
            $sourcePath = ExecutionContext::getActualOrCreateFromEnv()
                ->getExecutingMarkupHandler()
                ->getSourcePath();
            if(SlotSystem::isMainHeaderSlot($sourcePath)){
                return LazyLoad::LAZY_LOAD_METHOD_NONE_VALUE;
            }
        } catch (ExceptionNotFound $e) {
            // not a path execution
        }

        /**
         * HTML and not lozad as default because in a Hbs template, in a {@link TemplateForWebPage},
         * it would not work as the script would not be added
         */
        return ExecutionContext::getActualOrCreateFromEnv()
            ->getConfig()
            ->getValue(LazyLoad::CONF_LAZY_LOAD_METHOD,LazyLoad::LAZY_LOAD_METHOD_HTML_VALUE) ;
    }

}
