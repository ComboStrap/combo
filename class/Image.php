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


class Image
{


    const IMAGE_PATTERN = "\{\{(?:[^>\}]|(?:\}[^\}]))+\}\}";
    const CANONICAL = "image";
    private $id;
    private $width;
    /**
     * @var int
     */
    private $height;
    /**
     * See {@link image_type_to_mime_type}
     * @var int
     */
    private $imageType;
    private $wasAnalyzed = false;
    /**
     * @var mixed
     */
    private $mime;
    /**
     * @var bool
     */
    private $analyzable = false;

    /**
     * Image constructor.
     * @param $id
     */
    public function __construct($id)
    {
        /**
         * The id of image should starts with the root `:`
         * otherwise the image does not exist
         * It should then not be {@link cleanID()}
         */
        $this->id = cleanID($id);
        if ($id != $this->id){
            LogUtility::msg("The image id value ($id) is not conform and should be ($this->id)", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
        }
    }

    public function getAlt()
    {
        return null;
    }

    public function getUrl()
    {

        if ($this->exists()) {

            return ml($this->id, '', true, '', true);

        } else {

            return null;

        }

    }

    public function exists()
    {

        return file_exists($this->getFsPath());

    }


    public static function parse($match)
    {
        return Doku_Handler_Parse_Media($match);
    }

    public static function render($attributes)
    {
        $src = $attributes['src'];
        $width = $attributes['width'];
        $height = $attributes['height'];
        $title = $attributes['title'];
        $class = $attributes['class'];
        //Snippet taken from $renderer->doc .= $renderer->internalmedia($src, $linking = 'nolink');
        $linkAttributes = array('cache' => true);
        if ($width != null) {
            $linkAttributes['w'] = $width;
        }
        if ($height != null) {
            $linkAttributes['h'] = $height;
        }
        $imgHTML = '<img class="' . $class . '" src="' . ml($src, array('w' => $width, 'h' => $height, 'cache' => true)) . '"';
        if ($title != null) {
            $imgHTML .= ' alt="' . $title . '"';
        }
        if ($width != null) {
            $imgHTML .= 'width="' . $width . '"';
        }
        return $imgHTML . '>';
    }

    function isImage($text)
    {
        return preg_match(' / ' . self::IMAGE_PATTERN . ' / msSi', $text);
    }

    private function getFsPath()
    {
        return mediaFN($this->id);
    }

    public function getWidth()
    {
        $this->analyzeIfNeeded();
        return $this->width;
    }

    public function getHeight()
    {
        $this->analyzeIfNeeded();
        return $this->height;
    }

    private function analyzeIfNeeded()
    {

        if (!$this->wasAnalyzed) {

            if ($this->exists()) {
                /**
                 * Based on {@link media_image_preview_size()}
                 * $dimensions = media_image_preview_size($this->id, '', false);
                 */
                $imageInfo = array();
                $imageSize = getimagesize(mediaFN($this->id), $imageInfo);
                if ($imageSize === false) {
                    $this->analyzable = false;
                    LogUtility::msg("The image ($this->id) could not be analyzed", LVL_MSG_ERROR, "image");
                } else {
                    $this->analyzable = true;
                }
                $this->width = (int)$imageSize[0];
                if (empty($this->width)){
                    $this->analyzable = false;
                }
                $this->height = (int)$imageSize[1];
                if (empty($this->height)){
                    $this->analyzable = false;
                }
                $this->imageType = (int)$imageSize[2];
                $this->mime = $imageSize[3];
            }
            $this->wasAnalyzed = true;
        }

    }

    public function getMime()
    {

//        $this->analyzeIfNeeded();
//        $mime = $this->mime;
//        if ($mime == null) {
//            if (!empty($this->imagetype)) {
//                $mime = image_type_to_mime_type($this->imageType);
//            }
//        }
        return mimetype($this->id);


    }

    public function __toString()
    {
        return $this->id;
    }

    /**
     *
     * @return bool true if we could extract the dimensions
     */
    public function isAnalyzable()
    {
        $this->analyzeIfNeeded();
        return $this->analyzable;

    }

    public function getId()
    {
        return $this->id;
    }


}
