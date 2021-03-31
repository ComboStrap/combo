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

require_once(__DIR__ . '/InternalMedia.php');
require_once(__DIR__ . '/PluginUtility.php');

/**
 * Image
 * This is the class that handles the
 * image type of the dokuwiki {@link InternalMedia}
 */
class SvgImage extends InternalMedia
{

    const CANONICAL = "svg";



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
     * @param bool $absolute - use for semantic data
     * @return string|null
     */
    public function getUrl($absolute = true)
    {

        if ($this->exists()) {

            /**
             * Link attribute
             * No width and height
             */
            $att = array();
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
     * @return string
     */
    public function renderMediaTag()
    {

        if ($this->exists()) {

            $imgHTML = '<img';

            $lazyLoad = $this->getLazyLoad();
            /**
             * Snippet
             */
            if ($lazyLoad) {
                LazyLoad::addSnippet();
            }

            /**
             * Class
             */
            if ($lazyLoad) {
                $this->addClass("lazyload");
            }
            if (!empty($this->getClass())) {
                $imgHTML .= ' class="' . $this->getClass() . '"';
            }

            /**
             * Src
             */
            $srcValue = $this->getUrl();
            if ($lazyLoad) {

                // Modern transparent srcset pattern
                // normal src attribute with a transparent or low quality image as srcset value
                // https://github.com/aFarkas/lazysizes/#modern-transparent-srcset-pattern
                $imgHTML .= " src=\"$srcValue\"";
                $imgHTML .= " srcset=\"data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==\"";

                /**
                 * max-width as asked
                 */
                $widthValue = $this->getImgTagWidthValue();
                if(!empty($widthValue)) {
                    $imgHTML .= ' width="' . $this->getImgTagWidthValue() . '"';
                }

                /**
                 * Responsive image src set
                 * is not needed for svg
                 */


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
                $imgHTML .= ' alt = "' . $this->getTitle() . '"';
            }


            $imgHTML .= '>';

        } else {

            $imgHTML = "<span class=\"text-danger\">The svg ($this) does not exist</span>";

        }
        return $imgHTML;
    }

    /**
     * @return int - the width of the image from the file
     */
    public function getMediaWidth()
    {
        return $this->imageWidth;
    }

    /**
     * @return int - the height of the image from the file
     */
    public function getMediaHeight()
    {
        return $this->imageWeight;
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


}
