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
    const CONF_LAZY_LOAD_ENABLE = "rasterImageLazyLoadEnable";


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

        if ($this->getFile()->exists()) {

            /**
             * Link attribute
             */
            $att = array();

            // Width is driving the computation
            $urlWidth = $localWidth;
            if ($urlWidth == null) {
                $urlWidth = $this->getImgTagWidthValue();
            }
            if (!empty($urlWidth)) {

                $att['w'] = $urlWidth;

                // Height
                $height = $this->getImgTagHeightValue($urlWidth);
                if (!empty($height)) {
                    $att['h'] = $height;
                }

            }

            if ($this->getCache()) {
                $att['cache'] = $this->getCache();
            }
            $direct = true;
            return ml($this->getId(), $att, $direct, '&', $absolute);

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

        if ($this->getFile()->exists()) {

            $imgHTML = '<img';


            /**
             * Snippet Lazy load
             */
            $lazyLoad = $this->getLazyLoad();
            if ($lazyLoad) {
                LazyLoad::addLozadSnippet();
                PluginUtility::getSnippetManager()->upsertJavascriptForBar("lozad-raster");
                $attributes->addClassName("combo-lazy-raster");
            }

            /**
             * To get the real class
             */
            $attributes->process();

            /**
             * Class
             */
            if (!empty($attributes->getClass())) {
                $imgHTML .= ' class="' . $attributes->getClass() . '"';
            }

            /**
             * Src
             */
            $srcValue = $this->getUrl();
            if ($lazyLoad) {


                /**
                 * Placeholder
                 */
                $imgHTML .= " " . LazyLoad::getPlaceholderAttributes($srcValue);

                /**
                 * max-width as asked
                 */
                $widthValue = $this->getImgTagWidthValue();
                $imgHTML .= ' width="' . $this->getImgTagWidthValue() . '"';

                /**
                 * Responsive image src set building
                 */
                // Smallest size always on otherwise for small image, there is no image at all
                // in the set
                $smWidth = 300;
                if ($widthValue<$smWidth){
                    $smWidth = $widthValue;
                }
                $src300Url = $this->getUrl(true, $smWidth);
                $srcSet = "$src300Url {$smWidth}w";

                $mediumWith = 600;
                if ($widthValue >= $mediumWith) {
                    $srcMediumUrl = $this->getUrl(true, $mediumWith);
                    if (!empty($srcSet)) {
                        $srcSet .= ", ";
                    }
                    $srcSet .= "$srcMediumUrl {$mediumWith}w";
                }
                $largeWidth = 900;
                if ($widthValue >= $largeWidth) {
                    $srcLargeUrl = $this->getUrl(true, $largeWidth);
                    if (!empty($srcSet)) {
                        $srcSet .= ", ";
                    }
                    $srcSet .= "$srcLargeUrl {$largeWidth}w";
                }
                if (!empty($srcSet)) {
                    $imgHTML .= " data-sizes=\"auto\" data-srcset=\"$srcSet\"";
                }


            } else {

                $imgHTML .= " src=\"$srcValue\"";
                if (!empty($this->getImgTagWidthValue())) {
                    $imgHTML .= ' width="' . $this->getImgTagWidthValue() . '"';
                }
                if (!empty($this->getImgTagHeightValue())) {
                    $imgHTML .= ' height="' . $this->getImgTagHeightValue() . '"';
                }

            }


            /**
             * Title
             */
            if (!empty($this->getTitle())) {
                $imgHTML .= ' alt="' . $this->getTitle() . '"';
            }



            $imgHTML .= '>';

        } else {

            $imgHTML = "<span class=\"text-danger\">The image ($this) does not exist</span>";

        }
        return $imgHTML;
    }

    /**
     * @return int - the width of the image from the file
     */
    public function getMediaWidth()
    {
        $this->analyzeImageIfNeeded();
        return $this->imageWidth;
    }

    /**
     * @return int - the height of the image from the file
     */
    public function getMediaHeight()
    {
        $this->analyzeImageIfNeeded();
        return $this->imageWeight;
    }

    private function analyzeImageIfNeeded()
    {

        if (!$this->wasAnalyzed) {

            if ($this->getFile()->exists()) {

                /**
                 * Based on {@link media_image_preview_size()}
                 * $dimensions = media_image_preview_size($this->id, '', false);
                 */
                $imageInfo = array();
                $imageSize = getimagesize($this->getFile()->getPath(), $imageInfo);
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
    public function isAnalyzable()
    {
        $this->analyzeImageIfNeeded();
        return $this->analyzable;

    }


    /**
     * @return int - the width value attribute in a img
     */
    private function getImgTagWidthValue()
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
    private function getImgTagHeightValue($localWidth = null)
    {

        /**
         * Height default
         */
        $linkHeight = $this->getRequestedHeight();
        if (empty($linkHeight)) {
            $linkHeight = $this->getMediaHeight();
        }

        /**
         * Scale the height by size parameter
         */
        if (!empty($linkHeight) &&
            !empty($localWidth) &&
            !empty($this->getMediaWidth()) &&
            $this->getMediaWidth() != 0
        ) {
            $linkHeight = $linkHeight * ($localWidth / $this->getMediaWidth());
        }

        /**
         * Rounding to integer
         * The fetch.php file takes int as value for width and height
         * making a rounding if we pass a double (such as 37.5)
         * This is important because the security token is based on width and height
         * and therefore the fetch will failed
         */
        return intval($linkHeight);

    }

    public function getLazyLoad()
    {
        $lazyLoad = parent::getLazyLoad();
        if ($lazyLoad !== null) {
            return $lazyLoad;
        } else {
            return PluginUtility::getConfValue(RasterImageLink::CONF_LAZY_LOAD_ENABLE);
        }
    }


}
