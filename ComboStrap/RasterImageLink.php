<?php
/**
 * Copyright (c) 2020. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;

require_once(__DIR__ . '/MediaLink.php');
require_once(__DIR__ . '/LazyLoad.php');
require_once(__DIR__ . '/PluginUtility.php');

/**
 * Image
 * This is the class that handles the
 * raster image type of the dokuwiki {@link MediaLink}
 *
 * The real documentation can be found on the image page
 * @link https://www.dokuwiki.org/images
 *
 * Doc:
 * https://web.dev/optimize-cls/#images-without-dimensions
 * https://web.dev/cls/
 */
class RasterImageLink extends ImageLink
{

    const CANONICAL = FetcherRaster::CANONICAL;
    const CONF_LAZY_LOADING_ENABLE = "rasterImageLazyLoadingEnable";
    const CONF_LAZY_LOADING_ENABLE_DEFAULT = 1;

    const RESPONSIVE_CLASS = "img-fluid";

    const CONF_RESPONSIVE_IMAGE_MARGIN = "responsiveImageMargin";
    const CONF_RETINA_SUPPORT_ENABLED = "retinaRasterImageEnable";

    private FetcherRaster $fetchRaster;


    /**
     * @throws ExceptionBadArgument
     */
    public function __construct(MediaMarkup $mediaMarkup)
    {
        $fetcher = $mediaMarkup->getFetcher();
        if(!($fetcher instanceof FetcherRaster)) {
           throw new ExceptionBadArgument("The fetcher is not a raster fetcher but is a ".get_class($fetcher));
        }
        $this->fetchRaster = $fetcher;
        parent::__construct($mediaMarkup);
    }


    /**
     * Render a link
     * Snippet derived from {@link \Doku_Renderer_xhtml::internalmedia()}
     * A media can be a video also (Use
     * @return string
     * @throws ExceptionNotFound
     */
    public function renderMediaTag(): string
    {

        $path = $this->mediaMarkup->getPath();
        if (!FileSystems::exists($path)) {
            return "<span class=\"text-danger\">The image ($path) does not exist</span>";
        }


        $fetchRaster = $this->fetchRaster;

        $attributes = $this->mediaMarkup->getTagAttributes()
            ->setLogicalTag(self::CANONICAL);

        /**
         * Responsive image
         * https://getbootstrap.com/docs/5.0/content/images/
         * to apply max-width: 100%; and height: auto;
         *
         * Even if the resizing is requested by height,
         * the height: auto on styling is needed to conserve the ratio
         * while scaling down the screen
         */
        $attributes->addClassName(self::RESPONSIVE_CLASS);


        /**
         * width and height to give the dimension ratio
         * They have an effect on the space reservation
         * but not on responsive image at all
         * To allow responsive height, the height style property is set at auto
         * (ie img-fluid in bootstrap)
         */
        // The unit is not mandatory in HTML, this is expected to be CSS pixel
        // https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height
        // The HTML validator does not expect an unit otherwise it send an error
        // https://validator.w3.org/
        $htmlLengthUnit = "";
        $cssLengthUnit = "px";

        /**
         * Height
         * The logical height that the image should take on the page
         *
         * Note: The style is also set in {@link Dimension::processWidthAndHeight()}
         *
         * Cannot be empty
         */
        $targetHeight = $fetchRaster->getTargetHeight();

        /**
         * HTML height attribute is important for the ratio calculation
         * No layout shift
         */
        $attributes->addOutputAttributeValue("height", $targetHeight . $htmlLengthUnit);
        /**
         * We don't allow the image to scale up by default
         */
        $attributes->addStyleDeclarationIfNotSet("max-height", $targetHeight . $cssLengthUnit);
        /**
         * if the image has a class that has a `height: 100%`, the image will stretch
         */
        $attributes->addStyleDeclarationIfNotSet("height", "auto");


        /**
         * Responsive image src set building
         * We have chosen
         *   * 375: Iphone6
         *   * 768: Ipad
         *   * 1024: Ipad Pro
         *
         */
        // The image margin applied
        $imageMargin = Site::getConfValue(self::CONF_RESPONSIVE_IMAGE_MARGIN, "20px");


        /**
         * Srcset and sizes for responsive image
         * Width is mandatory for responsive image
         * Ref https://developers.google.com/search/docs/advanced/guidelines/google-images#responsive-images
         */


        /**
         * The value of the target image
         */
        $targetWidth = $fetchRaster->getTargetWidth();
        $fetchRaster->checkLogicalRatioAgainstTargetRatio($targetWidth, $targetHeight);

        /**
         * HTML Width attribute is important to avoid layout shift
         */
        $attributes->addOutputAttributeValue("width", $targetWidth . $htmlLengthUnit);
        /**
         * We don't allow the image to scale up by default
         */
        $attributes->addStyleDeclarationIfNotSet("max-width", $targetWidth . $cssLengthUnit);
        /**
         * We allow the image to scale down up to 100% of its parent
         */
        $attributes->addStyleDeclarationIfNotSet("width", "100%");


        /**
         * Continue
         */
        $srcSet = "";
        $sizes = "";

        /**
         * Width
         *
         * We create a series of URL
         * for different width and let the browser
         * download the best one for:
         *   * the actual container width
         *   * the actual of screen resolution
         *   * and the connection speed.
         *
         * The max-width value is set
         */
        $srcValue = $fetchRaster->getFetchUrl();
        /**
         * Add smaller sizes
         */
        foreach (Breakpoint::getBreakpoints() as $breakpoint) {

            try {
                $breakpointPixels = $breakpoint->getWidth();
            } catch (ExceptionInfinite $e) {
                continue;
            }

            if ($breakpointPixels > $targetWidth) {
                continue;
            }

            if (!empty($srcSet)) {
                $srcSet .= ", ";
                $sizes .= ", ";
            }
            $breakpointWidthMinusMargin = $breakpointPixels - $imageMargin;

            try {

                $breakpointRaster = FetcherRaster::createRasterFromFetchUrl($fetchRaster->getFetchUrl());
                if (
                    !$fetchRaster->hasHeightRequested() // breakpoint url needs only the h attribute in this case
                    || $fetchRaster->hasAspectRatioRequested() // width and height are mandatory
                ) {
                    $breakpointRaster->setRequestedWidth($breakpointWidthMinusMargin);
                }
                if ($fetchRaster->hasHeightRequested() // if this is a height request
                    || $fetchRaster->hasAspectRatioRequested() // width and height are mandatory
                ) {
                    $breakPointHeight = FetcherRaster::round($breakpointWidthMinusMargin / $fetchRaster->getTargetAspectRatio());
                    $breakpointRaster->setRequestedHeight($breakPointHeight);
                }

                $breakpointUrl = $breakpointRaster->getFetchUrl()
                    ->toString();

            } catch (ExceptionCompile $e) {
                // should not happen as the fetch url was already validated at build time but yeah
                LogUtility::internalError("We are unable to create the breakpoint image url ($fetchRaster) for the size ($breakpoint). Error:{$e->getMessage()}");
                continue;
            }
            $srcSet .= "$breakpointUrl {$breakpointWidthMinusMargin}w";
            $sizes .= $this->getSizes($breakpoint->getWidth(), $breakpointWidthMinusMargin);


        }

        /**
         * Add the last size
         * If the target image is really small, srcset and sizes are empty
         */
        if (!empty($srcSet)) {
            $srcSet .= ", ";
            $sizes .= ", ";
            $srcUrl = $fetchRaster->getFetchUrl()->toString();
            $srcSet .= "$srcUrl {$targetWidth}w";
            $sizes .= "{$targetWidth}px";
        }

        /**
         * Lazy load
         */
        $lazyLoad = $this->getLazyLoad();
        if ($lazyLoad) {

            /**
             * Html Lazy loading
             */
            $lazyLoadMethod = $this->mediaMarkup->getLazyLoadMethodOrDefault();
            switch ($lazyLoadMethod) {
                case MediaMarkup::LAZY_LOAD_METHOD_HTML_VALUE:
                    $attributes->addOutputAttributeValue("src", $srcValue);
                    if (!empty($srcSet)) {
                        // it the image is small, no srcset for instance
                        $attributes->addOutputAttributeValue("srcset", $srcSet);
                    }
                    $attributes->addOutputAttributeValue("loading", "lazy");
                    break;
                default:
                case MediaMarkup::LAZY_LOAD_METHOD_LOZAD_VALUE:
                    /**
                     * Snippet Lazy loading
                     */
                    LazyLoad::addLozadSnippet();
                    PluginUtility::getSnippetManager()->attachJavascriptFromComponentId("lozad-raster");
                    $attributes->addClassName(self::getLazyClass());
                    $attributes->addClassName(LazyLoad::getLazyClass());

                    /**
                     * A small image has no srcset
                     *
                     */
                    if (!empty($srcSet)) {

                        /**
                         * !!!!! DON'T FOLLOW THIS ADVICE !!!!!!!!!
                         * https://github.com/aFarkas/lazysizes/#modern-transparent-srcset-pattern
                         * The transparent image has a fix dimension aspect ratio of 1x1 making
                         * a bad reserved space for the image
                         * We use a svg instead
                         */
                        $attributes->addOutputAttributeValue("src", $srcValue);
                        $attributes->addOutputAttributeValue("srcset", LazyLoad::getPlaceholder($targetWidth, $targetHeight));
                        /**
                         * We use `data-sizes` and not `sizes`
                         * because `sizes` without `srcset`
                         * shows the broken image symbol
                         * Javascript changes them at the same time
                         */
                        $attributes->addOutputAttributeValue("data-sizes", $sizes);
                        $attributes->addOutputAttributeValue("data-srcset", $srcSet);

                    } else {

                        /**
                         * Small image but there is no little improvement
                         */
                        $attributes->addOutputAttributeValue("data-src", $srcValue);
                        $attributes->addOutputAttributeValue("src", LazyLoad::getPlaceholder($targetWidth, $targetHeight));

                    }
                    LazyLoad::addPlaceholderBackground($attributes);
                    break;
            }


        } else {

            if (!empty($srcSet)) {
                $attributes->addOutputAttributeValue("srcset", $srcSet);
                $attributes->addOutputAttributeValue("sizes", $sizes);
            } else {
                $attributes->addOutputAttributeValue("src", $srcValue);
            }

        }


        /**
         * Title (ie alt)
         */
        $attributes->addOutputAttributeValueIfNotEmpty("alt", $this->getAltNotEmpty());

        /**
         * Create the img element
         */
        $htmlAttributes = $attributes->toHTMLAttributeString();
        $imgHTML = '<img ' . $htmlAttributes . '/>';


        return $this->wrapMediaMarkupWithLink($imgHTML);
    }


    public function getLazyLoad(): bool
    {
        try {
            return $this->mediaMarkup->isLazy();
        } catch (ExceptionNotFound $e) {
            return Site::getConfValue(RasterImageLink::CONF_LAZY_LOADING_ENABLE, RasterImageLink::CONF_LAZY_LOADING_ENABLE_DEFAULT);
        }
    }

    /**
     * @param $screenWidth
     * @param $imageWidth
     * @return string sizes with a dpi correction if
     */
    private
    function getSizes($screenWidth, $imageWidth): string
    {

        if ($this->getWithDpiCorrection()) {
            $dpiBase = 96;
            $sizes = "(max-width: {$screenWidth}px) and (min-resolution:" . (3 * $dpiBase) . "dpi) " . intval($imageWidth / 3) . "px";
            $sizes .= ", (max-width: {$screenWidth}px) and (min-resolution:" . (2 * $dpiBase) . "dpi) " . intval($imageWidth / 2) . "px";
            $sizes .= ", (max-width: {$screenWidth}px) and (min-resolution:" . (1 * $dpiBase) . "dpi) {$imageWidth}px";
        } else {
            $sizes = "(max-width: {$screenWidth}px) {$imageWidth}px";
        }
        return $sizes;
    }

    /**
     * Return if the DPI correction is enabled or not for responsive image
     *
     * Mobile have a higher DPI and can then fit a bigger image on a smaller size.
     *
     * This can be disturbing when debugging responsive sizing image
     * If you want also to use less bandwidth, this is also useful.
     *
     * @return bool
     */
    private
    function getWithDpiCorrection(): bool
    {
        /**
         * Support for retina means no DPI correction
         */
        $retinaEnabled = Site::getConfValue(self::CONF_RETINA_SUPPORT_ENABLED, 0);
        return !$retinaEnabled;
    }

    /**
     * Used to select the raster image lazy loaded
     * @return string
     */
    public static function getLazyClass()
    {
        return StyleUtility::addComboStrapSuffix("lazy-raster");
    }


}
