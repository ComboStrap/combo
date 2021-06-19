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
 */
class RasterImageLink extends MediaLink
{

    const CANONICAL = "raster";
    const CONF_LAZY_LOADING_ENABLE = "rasterImageLazyLoadingEnable";

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
     * RasterImageLink constructor.
     * @param $id
     * @param TagAttributes $tagAttributes
     */
    public function __construct($id, $tagAttributes = null)
    {
        parent::__construct($id, $tagAttributes);
        $this->getTagAttributes()->setLogicalTag(self::CANONICAL);

    }


    /**
     * @param string $ampersand
     * @param null $localWidth - the asked width - use for responsive image
     * @return string|null
     */
    public function getUrl($ampersand = Url::URL_ENCODED_AND, $localWidth = null)
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
                $att[CacheMedia::CACHE_KEY] = $this->getCache();
            }
            $direct = true;

            return ml($this->getId(), $att, $direct, $ampersand, true);

        } else {

            return false;

        }
    }

    public function getAbsoluteUrl()
    {

        return $this->getUrl();

    }


    /**
     * Render a link
     * Snippet derived from {@link \Doku_Renderer_xhtml::internalmedia()}
     * A media can be a video also (Use
     * @return string
     */
    public function renderMediaTag()
    {


        if ($this->exists()) {


            /**
             * No dokuwiki type attribute
             */
            $this->tagAttributes->removeComponentAttributeIfPresent(MediaLink::MEDIA_DOKUWIKI_TYPE);
            $this->tagAttributes->removeComponentAttributeIfPresent(MediaLink::DOKUWIKI_SRC);

            /**
             * Responsive image
             * https://getbootstrap.com/docs/5.0/content/images/
             * to apply max-width: 100%; and height: auto;
             */
            $this->tagAttributes->addClassName(self::RESPONSIVE_CLASS);


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

            /**
             * Height
             * The logical height that the image should take on the page
             *
             * Note: The style is also set in {@link Dimension::processWidthAndHeight()}
             */
            $imgTagHeight = $this->getImgTagHeightValue();
            if (!empty($imgTagHeight)) {
                $this->tagAttributes->addHtmlAttributeValue("height", $imgTagHeight . $htmlLengthUnit);
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
            $widthValue = $this->getMediaWidth();
            $srcValue = $this->getUrl();

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
            if (!empty($widthValue)) {

                /**
                 * The internal intrinsic value of the image
                 */
                $imgTagWidth = $this->getImgTagWidthValue();
                if (!empty($imgTagWidth)) {

                    $this->tagAttributes->addHtmlAttributeValue("width", $imgTagWidth . $htmlLengthUnit);

                    if (!empty($imgTagHeight)) {
                        /**
                         * Check of height and width dimension
                         * as specified here
                         * https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height
                         */
                        $targetRatio = $this->getMediaWidth() / $this->getMediaHeight();
                        if (!(
                            $imgTagHeight * $targetRatio >= $imgTagWidth - 0.5
                            &&
                            $imgTagHeight * $targetRatio <= $imgTagWidth + 0.5
                        )) {
                            // check the second statement
                            if (!(
                                $imgTagWidth / $targetRatio >= $imgTagHeight - 0.5
                                &&
                                $imgTagWidth / $targetRatio <= $imgTagHeight + 0.5
                            )) {
                                LogUtility::msg("The width and height specified on the image ($this) does not pass the ratio test.");
                            }
                        }
                    }
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

                    if ($imgTagWidth > $breakpointWidth) {

                        if (!empty($srcSet)) {
                            $srcSet .= ", ";
                            $sizes .= ", ";
                        }
                        $breakpointWidthMinusMargin = $breakpointWidth - $imageMargin;
                        $xsmUrl = $this->getUrl(Url::URL_ENCODED_AND, $breakpointWidthMinusMargin);
                        $srcSet .= "$xsmUrl {$breakpointWidthMinusMargin}w";
                        $sizes .= $this->getSizes($breakpointWidth, $breakpointWidthMinusMargin);

                    }

                }

                /**
                 * Add the last size
                 * If the image is really small, srcset and sizes are empty
                 */
                if (!empty($srcSet)) {
                    $srcSet .= ", ";
                    $sizes .= ", ";
                    $srcUrl = $this->getUrl(Url::URL_ENCODED_AND,$imgTagWidth);
                    $srcSet .= "$srcUrl {$imgTagWidth}w";
                    $sizes .= "{$imgTagWidth}px";
                }

                /**
                 * Lazy load
                 */
                $lazyLoad = $this->getLazyLoad();
                if ($lazyLoad) {

                    /**
                     * Snippet Lazy loading
                     */
                    LazyLoad::addLozadSnippet();
                    PluginUtility::getSnippetManager()->attachJavascriptSnippetForBar("lozad-raster");
                    $this->tagAttributes->addClassName(self::LAZY_CLASS);
                    $this->tagAttributes->addClassName(LazyLoad::LAZY_CLASS);

                    /**
                     * A small image has no srcset
                     *
                     */
                    if (!empty($srcSet)) {

                        /**
                         * Src is always set, this is the default
                         * src attribute is served to browsers that do not take the srcset attribute into account.
                         * When lazy loading, we set the srcset to a transparent image to not download the image in the src
                         * https://github.com/aFarkas/lazysizes/#modern-transparent-srcset-pattern
                         */
                        $this->tagAttributes->addHtmlAttributeValue("src", $srcValue);
                        $this->tagAttributes->addHtmlAttributeValue("srcset", LazyLoad::TRANSPARENT_GIF);
                        $this->tagAttributes->addHtmlAttributeValue("sizes", $sizes);
                        $this->tagAttributes->addHtmlAttributeValue("data-srcset", $srcSet);

                    } else {

                        /**
                         * Small image but there is no little improvement
                         */
                        $this->tagAttributes->addHtmlAttributeValue("data-src", $srcValue);

                    }

                    LazyLoad::addPlaceholderBackground($this->tagAttributes);


                } else {

                    if (!empty($srcSet)) {
                        $this->tagAttributes->addHtmlAttributeValue("srcset", $srcSet);
                        $this->tagAttributes->addHtmlAttributeValue("sizes", $sizes);
                    } else {
                        $this->tagAttributes->addHtmlAttributeValue("src", $srcValue);
                    }

                }

            } else {

                // No width, no responsive possibility
                $lazyLoad = $this->getLazyLoad();
                if ($lazyLoad) {

                    LazyLoad::addPlaceholderBackground($this->tagAttributes);
                    $this->tagAttributes->addHtmlAttributeValue("src", LazyLoad::TRANSPARENT_GIF);
                    $this->tagAttributes->addHtmlAttributeValue("data-src", $srcValue);

                }

            }


            /**
             * Title (ie alt)
             */
            if ($this->tagAttributes->hasComponentAttribute(TagAttributes::TITLE_KEY)) {
                $title = $this->tagAttributes->getValueAndRemove(TagAttributes::TITLE_KEY);
                $this->tagAttributes->addHtmlAttributeValueIfNotEmpty("alt", $title);
            }

            /**
             * Create the img element
             */
            $htmlAttributes = $this->tagAttributes->toHTMLAttributeString();
            $imgHTML = '<img ' . $htmlAttributes . '/>';

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
                $imageSize = getimagesize($this->getFileSystemPath(), $imageInfo);
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
     * @return int - the width value attribute in a img (in CSS pixel that the image should takes)
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
         *
         * And this is also ask by the specification
         * a non-null positive integer
         * https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height
         *
         * And not {@link intval} because it will make from 3.6, 3 and not 4
         */
        return round($linkWidth);
    }

    public function getRequestedHeight()
    {
        $requestedHeight = parent::getRequestedHeight();
        if (!empty($requestedHeight)) {
            // it should not be bigger than the media Height
            $mediaHeight = $this->getMediaHeight();
            if (!empty($mediaHeight)) {
                if ($requestedHeight > $mediaHeight) {
                    LogUtility::msg("For the image ($this), the requested height of ($requestedHeight) can not be bigger than the intrinsic height of ($mediaHeight). The height was then set to its natural height ($mediaHeight)", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    $requestedHeight = $mediaHeight;
                }
            }
        }
        return $requestedHeight;
    }

    public function getRequestedWidth()
    {
        $requestedWidth = parent::getRequestedWidth();
        if (!empty($requestedWidth)) {
            // it should not be bigger than the media Height
            $mediaWidth = $this->getMediaWidth();
            if (!empty($mediaWidth)) {
                if ($requestedWidth > $mediaWidth) {
                    LogUtility::msg("For the image ($this), the requested width of ($requestedWidth) can not be bigger than the intrinsic width of ($mediaWidth). The width was then set to its natural width ($mediaWidth)", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    $requestedWidth = $mediaWidth;
                }
            }
        }
        return $requestedWidth;
    }


    /**
     * Return the height that the image should take on the screen
     * for the specified size
     *
     * @param null $localWidth - the width to derive the height from (in case the image is created for responsive lazy loading)
     * if not specified, the requested width and if not specified the intrinsic width
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
         *
         * And not {@link intval} because it will make from 3.6, 3 and not 4
         */
        return round($height);

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
        /**
         * Support for retina means no DPI correction
         */
        $retinaEnabled = PluginUtility::getConfValue(self::CONF_RETINA_SUPPORT_ENABLED, 0);
        return !$retinaEnabled;
    }


}
