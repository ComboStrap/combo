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


    /**
     * Image, video or just file
     */
    const INTERNAL_MEDIA_PATTERN = "\{\{(?:[^>\}]|(?:\}[^\}]))+\}\}";
    const CANONICAL = "image";

    /**
     * The dokuwiki mode name
     * for an internal media
     */
    const INTERNAL_MEDIA = "internalmedia";

    private $id;
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
     * @var mixed
     */
    private $mime;
    /**
     * @var bool
     */
    private $analyzable = false;
    private $description = null;

    /**
     * @var string the alt attribute value (known as the title for dokuwiki)
     */
    private $alt;

    private $class;
    private $cache;

    /**
     * @var int The requested height on the link
     */
    private $linkHeight;
    /**
     * @var int The requested with on the link
     */
    private $linkWidth;

    /**
     * Link value:
     *   * 'nolink'
     *   * 'direct': directly to the image
     *   * 'linkonly': show only a url
     *   * 'details': go to the details media viewer
     *
     * @var
     */
    private $linking;

    /**
     * Render attribute
     *   * 'center'
     *   * 'right'
     *   * 'left'
     */
    private $align;


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
        if ($id != $this->id) {
            LogUtility::msg("The image id value ($id) is not conform and should be ($this->id)", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
        }
    }

    /**
     * Create an image from internal call media attributes
     * @param array $callAttributes
     * @return Image
     */
    public static function createFromCallAttributes(array $callAttributes)
    {
        $id = $callAttributes[0]; // path
        $title = $callAttributes[1];
        $align = $callAttributes[2]; // not sure what to do with that
        $width = $callAttributes[3];
        $height = $callAttributes[4];
        $cache = $callAttributes[5];// not sure what to do with that
        $linking = $callAttributes[6];// not sure what to do with that
        $image = new Image($id);
        $image->setTitle($title);
        $image->setImageWidth($width);
        $image->setImageHeight($height);
        $image->setCache($cache);
        $image->setLinking($linking);
        $image->setAlign($align);
        return $image;

    }

    /**
     * @param $attributes - the attributes created by the function {@link Image::parse()}
     * @return Image
     */
    public static function createFromRenderAttributes($attributes)
    {
        $src = $attributes['src'];
        $image = new Image($src);

        $width = $attributes['width'];
        $image->setImageWidth($width);
        $height = $attributes['height'];
        $image->setImageHeight($height);
        $title = $attributes['title'];
        $image->setTitle($title);
        $class = $attributes['class'];
        $image->setClass($class);
        $linking = $attributes['linking'];
        $image->setLinking($linking);
        return $image;

    }

    public function getAlt()
    {
        return $this->alt;
    }

    public function getUrl($absolute = true)
    {

        if ($this->exists()) {

            $att = array();
            if (!empty($this->getLinkWidth())) {
                $att['w'] = $this->getLinkWidth();
            }
            if (!empty($this->getLinkHeight())) {
                $att['h'] = $this->getLinkHeight();
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

    public function exists()
    {

        return file_exists($this->getFsPath());

    }


    /**
     * Parse the img dokuwiki syntax
     * The output can be used to create an image object with the {@link Image::createFromRenderAttributes()} function
     * @param $match
     * @return array
     */
    public static function parse($match)
    {
        return Doku_Handler_Parse_Media($match);
    }

    /**
     * Render a link
     * Snippet derived from {@link \Doku_Renderer_xhtml::internalmedia()}
     * A media can be a video also (Use
     * @return string
     */
    public function renderImgHtmlTag()
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
            if (!empty($this->getLinkWidth())) {
                $imgHTML .= ' width = "' . $this->getLinkWidth() . '"';
            }
            if (!empty($this->getLinkHeight())) {
                $imgHTML .= ' height = "' . $this->getLinkHeight() . '"';
            }

            $imgHTML .= ' > ';

        } else {

            $id = $this->getId();
            $imgHTML = "<span class=\"text-danger\">The image ($id) does not exist</span>";

        }
        return $imgHTML;
    }

    function isImage($text)
    {
        return preg_match(' / ' . self::INTERNAL_MEDIA_PATTERN . ' / msSi', $text);
    }

    private function getFsPath()
    {
        return mediaFN($this->id);
    }

    public function getImageWidth()
    {
        $this->analyzeImageIfNeeded();
        return $this->imageWidth;
    }

    public function getImageHeight()
    {
        $this->analyzeImageIfNeeded();
        return $this->imageWeight;
    }

    private function analyzeImageIfNeeded()
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
                    LogUtility::msg("The image ($this->id) could not be analyzed", LogUtility::LVL_MSG_ERROR, "image");
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
        $this->analyzeImageIfNeeded();
        return $this->analyzable;

    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string the wiki syntax
     */
    public function getMarkupSyntax()
    {
        $descriptionPart = $this->description != null ? "|$this->description" : "";
        return '{{' . $this->id . $descriptionPart . '}}';
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $title - the alt of the link
     */
    private function setTitle($title)
    {
        $this->alt = $title;
    }

    private function setAlt($title)
    {
        $this->alt = $title;
    }

    private function setImageWidth($width)
    {
        $this->imageWidth = $width;
    }

    private function setImageHeight($height)
    {
        $this->imageWeight = $height;
    }

    /**
     * Return the same array than with the {@link Image::parse()} method
     * that is used in the renderer
     */
    public function toAttributes()
    {
//        'type'=>$call,
//        'src'=>$src,
//        'title'=>$link[1],
//        'align'=>$align,
//        'width'=>$w,
//        'height'=>$h,
//        'cache'=>$cache,
//        'linking'=>$linking,
    }

    private function setClass($class)
    {
        $this->class = $class;
    }

    private function getClass()
    {
        return $this->class;
    }

    private function getCache()
    {
        return $this->cache;
    }

    private function getTitle()
    {
        return $this->getAlt();
    }

    private function setCache($cache)
    {
        $this->cache = $cache;
    }

    private function getRequestedHeight()
    {
        return $this->linkHeight;
    }

    private function getRequestedWidth()
    {
        return $this->linkWidth;
    }

    private function getLinkWidth()
    {
        $linkWidth = $this->getRequestedWidth();
        if (empty($linkWidth)) {
            $linkWidth = $this->getImageWidth();
        }
        return $linkWidth;
    }

    private function getLinkHeight()
    {
        $linkHeight = $this->getRequestedHeight();
        if (empty($linkHeight)) {
            $linkHeight = $this->getImageHeight();
        }
        return $linkHeight;
    }

    private function setLinking($linking)
    {
        $this->linking = $linking;
    }

    private function setAlign($align)
    {
        $this->align = $align;
    }


}
