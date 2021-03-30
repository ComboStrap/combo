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

/**
 * Image
 * This is the class that handles the
 * image type of the dokuwiki {@link InternalMedia}
 */
class Image extends InternalMedia
{

    const CANONICAL = "image";


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
     * @var mixed - the mime from the {@link Image::analyzeImageIfNeeded()}
     */
    private $mime;


    public function getUrl($absolute = true)
    {

        if (InternalMedia::exists($this)) {

            $att = array();
            if (!empty($this->getImgTagWidthValue())) {
                $att['w'] = $this->getImgTagWidthValue();
            }
            if (!empty($this->getImgTagHeightValue())) {
                $att['h'] = $this->getImgTagHeightValue();
            }
            if ($this->getCache()) {
                $att['cache'] = $this->getCache();
            }
            $direct = true;
            return ml($this->getId(), $att, $direct, '', $absolute);

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

            $imgHTML = '<img ';

            /**
             * Class
             */
            if (!empty($this->getClass())) {
                $imgHTML .= ' class="' . $this->getClass() . '"';
            }

            /**
             * Src
             */
            $srcValue = $this->getUrl();
            $imgHTML .= " src=\"$srcValue\"";

            /**
             * Title
             */
            if (!empty($this->getTitle())) {
                $imgHTML .= ' alt = "' . $this->getTitle() . '"';
            }
            if (!empty($this->getImgTagWidthValue())) {
                $imgHTML .= ' width = "' . $this->getImgTagWidthValue() . '"';
            }
            if (!empty($this->getImgTagHeightValue())) {
                $imgHTML .= ' height = "' . $this->getImgTagHeightValue() . '"';
            }

            $imgHTML .= ' > ';

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

            if (InternalMedia::exists($this)) {
                /**
                 * Based on {@link media_image_preview_size()}
                 * $dimensions = media_image_preview_size($this->id, '', false);
                 */
                $imageInfo = array();
                $imageSize = getimagesize(InternalMedia::getPath($this), $imageInfo);
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
            $this->wasAnalyzed = true;
        }

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
            $linkWidth = $this->getMediaWidth();
        }
        return $linkWidth;
    }

    /**
     * @return int the height value attribute in a img
     */
    private function getImgTagHeightValue()
    {
        $linkHeight = $this->getRequestedHeight();
        if (empty($linkHeight)) {
            $linkHeight = $this->getMediaHeight();
        }
        return $linkHeight;
    }


}
