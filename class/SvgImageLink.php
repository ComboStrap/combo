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
require_once(__DIR__ . '/PluginUtility.php');

/**
 * Image
 * This is the class that handles the
 * svg link type
 */
class SvgImageLink extends InternalMediaLink
{

    const CANONICAL = "svg";

    /**
     * The maximum size to be embedded
     * Above this size limit they are fetched
     */
    const DEFAULT_MAX_INLINE_SIZE = 2048; // 2kb


    private $svgWidth;
    /**
     * @var int
     */
    private $svgWeight;

    private  function createImgHTMLTag()
    {
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
            $this->getComponentAttributes()->addClassName("lazyload");
        }
        if (!empty($this->getComponentAttributes()->getClass())) {
            $imgHTML .= ' class="' . $this->getComponentAttributes()->getClass() . '"';
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
            if (!empty($widthValue)) {
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
        return $imgHTML;
    }


    /**
     * @param bool $absolute - use for semantic data
     * @return string|null
     */
    public function getUrl($absolute = true)
    {

        if ($this->getFile()->exists()) {

            /**
             * Link attribute
             * No width and height
             * There are embedded in the inline
             * or set in the img element
             * No need to resize, the browser do it
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
     * @param $tagAttributes
     * @return string
     */
    public function renderMediaTag($tagAttributes)
    {
        if ($this->getFile()->exists()) {

            if ($this->getFile()->getSize() > $this->getMaxInlineSize()) {

               $imgHTML = $this->createImgHTMLTag($tagAttributes);

            } else {

                $imgHTML = $this->createInlineHTMLTag($tagAttributes);

            }
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
        return $this->svgWidth;
    }

    /**
     * @return int - the height of the image from the file
     */
    public function getMediaHeight()
    {
        return $this->svgWeight;
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

    private function getMaxInlineSize()
    {
        return self::DEFAULT_MAX_INLINE_SIZE;
    }

    private function createInlineHTMLTag($tagAttributes)
    {
        /**
         * inlineSVG from common.php
         */
        return inlineSVG($this->getFile()->getXmlText($tagAttributes));

    }

    /**
     * @return File|SvgFile
     */
    public function getFile()
    {
        return new SvgFile(mediaFN($this->getId()));
    }


}
