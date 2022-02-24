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

    const CANONICAL = ImageRaster::CANONICAL;
    const CONF_LAZY_LOADING_ENABLE = "rasterImageLazyLoadingEnable";
    const CONF_LAZY_LOADING_ENABLE_DEFAULT = 1;

    const RESPONSIVE_CLASS = "img-fluid";

    const CONF_RESPONSIVE_IMAGE_MARGIN = "responsiveImageMargin";
    const CONF_RETINA_SUPPORT_ENABLED = "retinaRasterImageEnable";
    const LAZY_CLASS = "lazy-raster-combo";

    const BREAKPOINTS =
        array(
            "xs" => 375,
            "sm" => 576,
            "md" => 768,
            "lg" => 992
        );


    /**
     * RasterImageLink constructor.
     * @param ImageRaster $imageRaster
     */
    public function __construct($imageRaster)
    {
        parent::__construct($imageRaster);


    }


    /**
     * Render a link
     * Snippet derived from {@link \Doku_Renderer_xhtml::internalmedia()}
     * A media can be a video also (Use
     * @return string
     * @throws ExceptionCombo
     */
    public function renderMediaTag(): string
    {
        /**
         * @var ImageRaster $image
         */
        $image = $this->getDefaultImage();
        if ($image->exists()) {

            $attributes = $image->getAttributes();

            /**
             * No dokuwiki type attribute
             */
            $attributes->removeComponentAttributeIfPresent(MediaLink::MEDIA_DOKUWIKI_TYPE);
            $attributes->removeComponentAttributeIfPresent(MediaLink::DOKUWIKI_SRC);

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
             */
            try {
                $targetHeight = $image->getTargetHeight();
            } catch (ExceptionCombo $e) {
                LogUtility::msg("No rendering for the image ($image). The target height reports a problem: {$e->getMessage()}");
                return "";
            }
            if (!empty($targetHeight)) {
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
            }


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
            try {
                $mediaWidthValue = $image->getIntrinsicWidth();
            } catch (ExceptionCombo $e) {
                LogUtility::msg("No rendering for the image ($image). The intrinsic width reports a problem: {$e->getMessage()}");
                return "";
            }
            $srcValue = $image->getUrl();

            /**
             * Responsive image src set building
             * We have chosen
             *   * 375: Iphone6
             *   * 768: Ipad
             *   * 1024: Ipad Pro
             *
             */
            // The image margin applied
            $imageMargin = PluginUtility::getConfValue(self::CONF_RESPONSIVE_IMAGE_MARGIN, "20px");


            /**
             * Srcset and sizes for responsive image
             * Width is mandatory for responsive image
             * Ref https://developers.google.com/search/docs/advanced/guidelines/google-images#responsive-images
             */
            if (!empty($mediaWidthValue)) {

                /**
                 * The value of the target image
                 */
                try {
                    $targetWidth = $image->getTargetWidth();
                } catch (ExceptionCombo $e) {
                    LogUtility::msg("No rendering for the image ($image). The target width reports a problem: {$e->getMessage()}");
                    return "";
                }
                if (!empty($targetWidth)) {

                    if (!empty($targetHeight)) {
                        $image->checkLogicalRatioAgainstTargetRatio($targetWidth, $targetHeight);
                    }
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

                }

                /**
                 * Continue
                 */
                $srcSet = "";
                $sizes = "";

                /**
                 * Add smaller sizes
                 */
                foreach (self::BREAKPOINTS as $breakpointWidth) {

                    if ($targetWidth > $breakpointWidth) {

                        if (!empty($srcSet)) {
                            $srcSet .= ", ";
                            $sizes .= ", ";
                        }
                        $breakpointWidthMinusMargin = $breakpointWidth - $imageMargin;

                        $xsmUrl = $image->getUrlAtBreakpoint($breakpointWidthMinusMargin);
                        $srcSet .= "$xsmUrl {$breakpointWidthMinusMargin}w";
                        $sizes .= $this->getSizes($breakpointWidth, $breakpointWidthMinusMargin);

                    }

                }

                /**
                 * Add the last size
                 * If the target image is really small, srcset and sizes are empty
                 */
                if (!empty($srcSet)) {
                    $srcSet .= ", ";
                    $sizes .= ", ";
                    $srcUrl = $image->getUrlAtBreakpoint($targetWidth);
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
                    $lazyLoadMethod = $this->getLazyLoadMethod();
                    switch ($lazyLoadMethod) {
                        case MediaLink::LAZY_LOAD_METHOD_HTML_VALUE:
                            $attributes->addOutputAttributeValue("src", $srcValue);
                            if (!empty($srcSet)) {
                                // it the image is small, no srcset for instance
                                $attributes->addOutputAttributeValue("srcset", $srcSet);
                            }
                            $attributes->addOutputAttributeValue("loading", "lazy");
                            break;
                        default:
                        case MediaLink::LAZY_LOAD_METHOD_LOZAD_VALUE:
                            /**
                             * Snippet Lazy loading
                             */
                            LazyLoad::addLozadSnippet();
                            PluginUtility::getSnippetManager()->attachInternalJavascriptForSlot("lozad-raster");
                            $attributes->addClassName(self::LAZY_CLASS);
                            $attributes->addClassName(LazyLoad::LAZY_CLASS);

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

            } else {

                // No width, no responsive possibility
                $lazyLoad = $this->getLazyLoad();
                if ($lazyLoad) {

                    LazyLoad::addPlaceholderBackground($attributes);
                    $attributes->addOutputAttributeValue("src", LazyLoad::getPlaceholder());
                    $attributes->addOutputAttributeValue("data-src", $srcValue);

                }

            }


            /**
             * Title (ie alt)
             */
            $attributes->addOutputAttributeValueIfNotEmpty("alt", $image->getAltNotEmpty());

            /**
             * TODO: Side effect of the fact that we use the same attributes
             * Title attribute of a media is the alt of an image
             * And title should not be in an image tag
             */
            $attributes->removeAttributeIfPresent(TagAttributes::TITLE_KEY);

            /**
             * Old model where the src is parsed and the path
             * is in the attributes
             */
            $attributes->removeAttributeIfPresent(PagePath::PROPERTY_NAME);

            /**
             * Create the img element
             */
            $htmlAttributes = $attributes->toHTMLAttributeString();
            $imgHTML = '<img ' . $htmlAttributes . '/>';

        } else {

            $imgHTML = "<span class=\"text-danger\">The image ($this) does not exist</span>";

        }

        return $imgHTML;
    }


    public
    function getLazyLoad()
    {
        $lazyLoad = parent::getLazyLoad();
        if ($lazyLoad !== null) {
            return $lazyLoad;
        } else {
            return PluginUtility::getConfValue(RasterImageLink::CONF_LAZY_LOADING_ENABLE, RasterImageLink::CONF_LAZY_LOADING_ENABLE_DEFAULT);
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
        $retinaEnabled = PluginUtility::getConfValue(self::CONF_RETINA_SUPPORT_ENABLED, 0);
        return !$retinaEnabled;
    }


}
