<?php
/**
 * Copyright (c) 2021. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;

require_once(__DIR__ . '/SvgImageLink.php');

/**
 * Class InternalMedia
 * Represent a media link
 * @package ComboStrap
 */
class InternalMediaLink
{

    /**
     * The dokuwiki mode name
     * for an internal media
     */
    const INTERNAL_MEDIA = "internalmedia";
    const INTERNAL_MEDIA_PATTERN = "\{\{(?:[^>\}]|(?:\}[^\}]))+\}\}";
    const LINKING_KEY = 'linking';
    const TITLE_KEY = 'title';
    const HEIGHT_KEY = 'height';
    const WIDTH_KEY = 'width';
    const CACHE_KEY = 'cache';

    private $id;

    private $lazyLoad = null;

    /**
     * @var string the alt attribute value (known as the title for dokuwiki)
     */
    private $alt;

    /**
     * Caching of external image
     * https://www.dokuwiki.org/images#caching
     */
    private $cache = true;

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

    private $description = null;


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
            LogUtility::msg("Internal error: The media id value ($id) is not conform and should be ($this->id)", LogUtility::LVL_MSG_ERROR, "support");
        }
    }


    /**
     * Parse the img dokuwiki syntax
     * The output can be used to create an image object with the {@link self::createFromRenderAttributes()} function
     * @param $match
     * @return array
     */
    public static function getParseAttributes($match)
    {
        require_once(__DIR__. '/../../../../inc/parser/handler.php');
        return Doku_Handler_Parse_Media($match);
    }

    /**
     * Create an image from internal call media attributes
     * @param array $callAttributes
     * @return InternalMediaLink
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

        $internalMedia = self::createFromId($id);
        $internalMedia->setTitle($title);
        $internalMedia->setRequestedWidth($width);
        $internalMedia->setRequestedHeight($height);
        $internalMedia->setNoCache($cache);
        $internalMedia->setLinking($linking);
        $internalMedia->setAlign($align);
        return $internalMedia;

    }

    /**
     * @param $attributes - the attributes created by the function {@link InternalMediaLink::getParseAttributes()}
     * @return RasterImageLink
     * The known attributes are also deleted
     */
    public static function createFromRenderAttributes(&$attributes)
    {
        $src = cleanID($attributes['src']);
        $media = self::createFromId($src);

        if(isset($attributes[self::WIDTH_KEY])) {
            $width = $attributes[self::WIDTH_KEY];
            $media->setRequestedWidth($width);
            unset($attributes[self::WIDTH_KEY]);
        }
        if (isset($attributes[self::HEIGHT_KEY])) {
            $height = $attributes[self::HEIGHT_KEY];
            $media->setRequestedHeight($height);
            unset($attributes[self::HEIGHT_KEY]);
        }
        if (isset($attributes[self::TITLE_KEY])) {
            $title = $attributes[self::TITLE_KEY];
            $media->setTitle($title);
            unset($attributes[self::TITLE_KEY]);
        }
        if ($attributes[self::LINKING_KEY]) {
            $linking = $attributes[self::LINKING_KEY];
            $media->setLinking($linking);
            unset($attributes[self::LINKING_KEY]);
        }
        if (isset($attributes[self::CACHE_KEY])) {
            $nocache = $attributes[self::CACHE_KEY];
            $media->setNoCache($nocache);
            unset($attributes[self::CACHE_KEY]);
        }
        return $media;

    }

    public static function createFromRenderMatch($match)
    {
        $attributes = self::getParseAttributes($match);
        return self::createFromRenderAttributes($attributes);
    }

    public function setLazyLoad($false)
    {
        $this->lazyLoad = $false;
    }

    public function getLazyLoad()
    {
        return $this->lazyLoad;
    }

    public function getMime()
    {

        return mimetype($this->getId())[1];

    }


    /**
     * @param $id
     * @return RasterImageLink|InternalMediaLink
     */
    public static function createFromId($id)
    {
        $mime = mimetype($id)[1];
        if (substr($mime, 0, 5) == 'image') {
            if (substr($mime,6)=="svg+xml"){
                $internalMedia = new SvgImageLink($id);
            } else {
                $internalMedia = new RasterImageLink($id);
            }
        } else {
            LogUtility::msg("Internal error: The media ($id) is not yet implemented", LogUtility::LVL_MSG_ERROR, "support");
            $internalMedia = new InternalMediaLink($id);
        }
        return $internalMedia;
    }

    /**
     * Return the same array than with the {@link self::parse()} method
     * that is used in the {@link CallStack}
     */
    public function getHandleAttributes()
    {
        return array(
            'type' => null, // ??? internal, external
            'src' => $this->getId(),
            self::TITLE_KEY => $this->getTitle(),
            'align' => $this->getAlign(),
            self::WIDTH_KEY => $this->getRequestedWidth(),
            self::HEIGHT_KEY => $this->getRequestedHeight(),
            self::CACHE_KEY => $this->getCache(),
            self::LINKING_KEY => $this->getLinking()
        );
    }

    /**
     * @return string the wiki syntax
     */
    public function getMarkupSyntax()
    {
        $descriptionPart = $this->description != null ? "|$this->description" : "";
        return '{{' . $this->id . $descriptionPart . '}}';
    }

    public function getId()
    {
        return $this->id;
    }

    public static function isInternalMediaSyntax($text)
    {
        return preg_match(' / ' . InternalMediaLink::INTERNAL_MEDIA_PATTERN . ' / msSi', $text);
    }

    public function setNoCache($cache)
    {
        if($cache=="nocache") {
            $this->cache = false;
        }
    }

    public function getRequestedHeight()
    {
        return $this->linkHeight;
    }

    public function setRequestedHeight($height)
    {
        $this->linkHeight = $height;
    }

    public function getRequestedWidth()
    {
        if ($this->linkWidth==0){
            return null; // empty
        } else {
            return $this->linkWidth;
        }
    }

    public function setRequestedWidth($width)
    {
        $this->linkWidth = $width;
    }

    public function setLinking($linking)
    {
        $this->linking = $linking;
    }

    protected function setAlign($align)
    {
        $this->align = $align;
    }



    public function getCache()
    {
        return $this->cache;
    }

    protected function getTitle()
    {
        return $this->getAlt();
    }

    /**
     * @param $title - the alt of the link
     */
    protected function setTitle($title)
    {
        $this->alt = $title;
    }

    private function setAlt($title)
    {
        $this->alt = $title;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getAlt()
    {
        return $this->alt;
    }

    public function __toString()
    {
        return $this->getId();
    }

    private function getAlign()
    {
        return $this->align;
    }

    private function getLinking()
    {
        return $this->linking;
    }

    public function renderMediaTag($attributes)
    {
        // hover
        Animation::processOnHover($attributes);
        // Position
        Position::processPosition($attributes);


    }

    public function isImage()
    {
        return substr($this->getMime(), 0, 5) == 'image';
    }

    public function getExtension()
    {
        return mimetype($this->getId())[0];

    }

    public function getFile()
    {
        return new File(mediaFN($this->getId()));
    }


}
