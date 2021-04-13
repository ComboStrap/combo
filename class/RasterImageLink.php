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

require_once(__DIR__ . '/InternalMediaLink.php');
require_once(__DIR__ . '/LazyLoad.php');
require_once(__DIR__ . '/PluginUtility.php');

/**
 * Image
 * This is the class that handles the
 * raster image type of the dokuwiki {@link InternalMediaLink}
 */
class RasterImageLink extends InternalMediaLink
{

    const CANONICAL = "image";
    const CONF_LAZY_LOADING_ENABLE = "rasterImageLazyLoadingEnable";

    const RESPONSIVE_CLASS = "img-fluid";

    const CONF_RESPONSIVE_IMAGE_MARGIN = "responsiveImageMargin";
    const CONF_RESPONSIVE_IMAGE_DPI_CORRECTION = "responsiveImageDpiCorrection";
    const LAZY_CLASS = "combo-lazy-raster";


    private $imageWidth;
    /**
     * @var int
     */
    private $imageWeight;
    /**
     * See {@link image_type_to_mime_type}
     * @var int
     */
    private $imageType;
    private $wasAnalyzed = false;

    /**
     * @var bool
     */
    private $analyzable = false;

    /**
     * @var mixed - the mime from the {@link RasterImageLink::analyzeImageIfNeeded()}
     */
    private $mime;


    /**
     * @param bool $absolute - use for semantic data
     * @param null $localWidth - the asked width - use for responsive image
     * @return string|null
     */
    public function getUrl($absolute = true, $localWidth = null)
    {

        if ($this->exists()) {

            /**
             * Link attribute
             */
            $att = array();

            // Width is driving the computation
            if ($localWidth != null && $localWidth != $this->getMediaWidth()) {

                $att['w'] = $localWidth;

                // Height
                $height = $this->getImgTagHeightValue($localWidth);
                if (!empty($height)) {
                    $att['h'] = $height;
                }

            }

            if ($this->getCache()) {
                $att['cache'] = $this->getCache();
            }
            $direct = true;
            /**
             * This URL encoding is mandatory
             * The below function uses them when
             * there is a width and
             * use them not otherwise
             */
            $urlEncodedAnd = '&amp;';
            return ml($this->getId(), $att, $direct, $urlEncodedAnd, $absolute);

        } else {

            return null;

        }
    }

    public function getAbsoluteUrl()
    {

        return $this->getUrl(true);

    }


    /**
     * Render a link
     * Snippet derived from {@link \Doku_Renderer_xhtml::internalmedia()}
     * A media can be a video also (Use
     * @param TagAttributes $attributes
     * @return string
     */
    public function renderMediaTag(&$attributes = null)
    {

        parent::renderMediaTag($attributes);

        if ($this->exists()) {


            /**
             * Snippet Lazy load
             * To add it to the class name
             */
            $lazyLoad = $this->getLazyLoad();
            if ($lazyLoad) {
                LazyLoad::addLozadSnippet();
                PluginUtility::getSnippetManager()->attachJavascriptSnippetForBar("lozad-raster");
                $attributes->addClassName(self::LAZY_CLASS);
            }

            /**
             * Responsive image
             * https://getbootstrap.com/docs/5.0/content/images/
             * to apply max-width: 100%; and height: auto;
             */
            $attributes->addClassName(self::RESPONSIVE_CLASS);


            /**
             * width and height to give the dimension ratio
             * They have an effect on the space reservation
             * but not on responsive image at all
             * To allow responsive height, the height style property is set at auto
             * (ie img-fluid in bootstrap)
             */
            $imgTagHeightValue = $this->getImgTagHeightValue();
            if (!empty($imgTagHeightValue)) {
                $attributes->addHtmlAttributeValue("height", $imgTagHeightValue . 'px');
                /**
                 * By default, the browser with a height auto due to the img-fluid class
                 * takes the value of the width. To constraint it, we use max-height
                 */
                $attributes->addStyleDeclaration("max-height", $imgTagHeightValue . "px");
            }
            $widthValue = $this->getImgTagWidthValue();

            /**
             * Src is always set, this is the default
             * src attribute is served to browsers that do not take the srcset attribute into account.
             */
            $srcValue = $this->getUrl(true,$widthValue);
            $attributes->addHtmlAttributeValue("src", $srcValue);

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

            // Xsmall
            $extraSmallBreakPointWidth = 375;
            $xsmWidth = $extraSmallBreakPointWidth - $imageMargin;
            // Small
            $smallBreakPointWidth = 576;
            $smWidth = $smallBreakPointWidth - $imageMargin;
            // Medium
            $mediumBreakpointWith = 768;
            $mediumWith = $mediumBreakpointWith - $imageMargin;
            // Large
            $largeBreakpointWidth = 992;
            $largeWidth = $largeBreakpointWidth - $imageMargin;

            /**
             * Srcset and sizes for responsive image
             * Width is mandatory for responsive image
             * Ref https://developers.google.com/search/docs/advanced/guidelines/google-images#responsive-images
             */
            if (!empty($widthValue)) {

                $attributes->addHtmlAttributeValue("width", $this->getImgTagWidthValue() . 'px');

                // Xs
                if ($widthValue >= $xsmWidth) {
                    $xsmUrl = $this->getUrl(true, $xsmWidth);
                    $srcSet = "$xsmUrl {$xsmWidth}w";
                    $sizes = $this->getSizes($extraSmallBreakPointWidth, $xsmWidth);

                    // Small
                    if ($widthValue >= $smWidth) {

                        $smUrl = $this->getUrl(true, $smWidth);
                        $srcSet .= ", $smUrl {$smWidth}w";
                        $sizes .= ", " . $this->getSizes($smallBreakPointWidth, $smWidth);

                        // Medium
                        if ($widthValue >= $mediumWith) {
                            $srcMediumUrl = $this->getUrl(true, $mediumWith);
                            $srcSet .= ", $srcMediumUrl {$mediumWith}w";
                            $sizes .= ", " . $this->getSizes($mediumBreakpointWith, $mediumWith);

                            // Large
                            if ($widthValue >= $largeWidth) {
                                $srcLargeUrl = $this->getUrl(true, $largeWidth);
                                $srcSet .= ", $srcLargeUrl {$largeWidth}w";
                                $sizes .= ", " . $this->getSizes($largeBreakpointWidth, $largeWidth);
                            }

                        }
                    }
                }

                // Add the last one
                // two times not empty to beat the linter
                // otherwise it thinks that $sizes may be not initialized
                if (!empty($srcSet) && !empty($sizes)) {
                    $srcSet .= ", ";
                    $sizes .= ", ";
                } else {
                    $srcSet = "";
                    $sizes = "";
                }

                /**
                 * If the image is really small,
                 * there is no set
                 */
                if (!empty($srcSet)) {
                    $srcUrl = $this->getUrl(true);
                    $srcSet .= "$srcUrl {$widthValue}w";
                }

                /**
                 * Sizes is added in all cases (lazy loading or not)
                 * if there is more than one
                 */
                if (!empty($sizes)) {
                    $sizes .= "{$widthValue}px";
                    $attributes->addHtmlAttributeValue("sizes", $sizes);
                }


                /**
                 * Lazy load
                 */
                if ($lazyLoad) {

                    /**
                     * Placeholder
                     */
                    // Modern transparent srcset pattern
                    // normal src attribute with a transparent or low quality image as srcset value
                    // https://github.com/aFarkas/lazysizes/#modern-transparent-srcset-pattern
                    // srcset is a base64 encoded transparent gif
                    $attributes->addHtmlAttributeValue("srcset", LazyLoad::TRANSPARENT_GIF);

                    LazyLoad::addPlaceholderBackground($attributes);

                    $attributes->addHtmlAttributeValue("data-srcset", $srcSet);

                } else {

                    if (!empty($srcSet)) {
                        $attributes->addHtmlAttributeValue("srcset", $srcSet);
                    }

                }

            } else {

                // No width, no responsive possibility
                if ($lazyLoad) {

                    LazyLoad::addPlaceholderBackground($attributes);
                    $attributes->addHtmlAttributeValue("src", LazyLoad::TRANSPARENT_GIF);
                    $attributes->addHtmlAttributeValue("data-src", $srcValue);

                }

            }


            /**
             * Title
             */
            $attributes->addHtmlAttributeValueIfNotEmpty("alt", $this->getTitle());


            /**
             *
             */
            $htmlAttributes = $attributes->toHTMLString();

            $imgHTML = "<img $htmlAttributes>";

        } else {

            $imgHTML = "<span class=\"text-danger\">The image ($this) does not exist</span>";

        }

        return $imgHTML;
    }

    /**
     * @return int - the width of the image from the file
     */
    public
    function getMediaWidth()
    {
        $this->analyzeImageIfNeeded();
        return $this->imageWidth;
    }

    /**
     * @return int - the height of the image from the file
     */
    public
    function getMediaHeight()
    {
        $this->analyzeImageIfNeeded();
        return $this->imageWeight;
    }

    private
    function analyzeImageIfNeeded()
    {

        if (!$this->wasAnalyzed) {

            if ($this->exists()) {

                /**
                 * Based on {@link media_image_preview_size()}
                 * $dimensions = media_image_preview_size($this->id, '', false);
                 */
                $imageInfo = array();
                $imageSize = getimagesize($this->getPath(), $imageInfo);
                if ($imageSize === false) {
                    $this->analyzable = false;
                    LogUtility::msg("The image ($this) could not be analyzed", LogUtility::LVL_MSG_ERROR, "image");
                } else {
                    $this->analyzable = true;
                }
                $this->imageWidth = (int)$imageSize[0];
                if (empty($this->imageWidth)) {
                    $this->analyzable = false;
                }
                $this->imageWeight = (int)$imageSize[1];
                if (empty($this->imageWeight)) {
                    $this->analyzable = false;
                }
                $this->imageType = (int)$imageSize[2];
                $this->mime = $imageSize[3];

            }
        }
        $this->wasAnalyzed = true;
    }


    /**
     *
     * @return bool true if we could extract the dimensions
     */
    public
    function isAnalyzable()
    {
        $this->analyzeImageIfNeeded();
        return $this->analyzable;

    }


    /**
     * @return int - the width value attribute in a img
     */
    public
    function getImgTagWidthValue()
    {
        $linkWidth = $this->getRequestedWidth();
        if (empty($linkWidth)) {
            if (empty($this->getRequestedHeight())) {

                $linkWidth = $this->getMediaWidth();

            } else {

                // Height is not empty
                // We derive the width from it
                if ($this->getMediaHeight() != 0
                    && !empty($this->getMediaHeight())
                    && !empty($this->getMediaWidth())
                ) {
                    $linkWidth = $this->getMediaWidth() * ($this->getRequestedHeight() / $this->getMediaHeight());
                }

            }
        }
        /**
         * Rounding to integer
         * The fetch.php file takes int as value for width and height
         * making a rounding if we pass a double (such as 37.5)
         * This is important because the security token is based on width and height
         * and therefore the fetch will failed
         */
        return intval($linkWidth);
    }

    /**
     * @param null $localWidth - the width to derive the height from (in case the image is created for responsive lazy loading)
     * @return int the height value attribute in a img
     */
    public
    function getImgTagHeightValue($localWidth = null)
    {

        /**
         * Height default
         */
        $height = $this->getRequestedHeight();
        if (empty($height)) {
            $height = $this->getMediaHeight();
        }

        $width = $localWidth;
        if ($width == null) {
            $width = $this->getRequestedWidth();
            if (empty($width)) {
                $width = $this->getMediaWidth();
            }
        }

        /**
         * Scale the height by size parameter
         */
        if (!empty($height) &&
            !empty($width) &&
            !empty($this->getMediaWidth()) &&
            $this->getMediaWidth() != 0
        ) {
            $height = $height * ($width / $this->getMediaWidth());
        }

        /**
         * Rounding to integer
         * The fetch.php file takes int as value for width and height
         * making a rounding if we pass a double (such as 37.5)
         * This is important because the security token is based on width and height
         * and therefore the fetch will failed
         */
        return intval($height);

    }

    public
    function getLazyLoad()
    {
        $lazyLoad = parent::getLazyLoad();
        if ($lazyLoad !== null) {
            return $lazyLoad;
        } else {
            return PluginUtility::getConfValue(RasterImageLink::CONF_LAZY_LOADING_ENABLE);
        }
    }

    /**
     * @param $screenWidth
     * @param $imageWidth
     * @return string sizes with a dpi correction if
     */
    private
    function getSizes($screenWidth, $imageWidth)
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
    function getWithDpiCorrection()
    {
        return true;
    }


}
