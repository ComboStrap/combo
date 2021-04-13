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
class InternalMediaLink extends DokuPath
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
    const TYPE_KEY = "type";

    // Pattern to capture the link as first capture group
    const LINK_PATTERN = "{{\s*([a-z0-9A-Z:?=&.x\-_]*)\s*\|?.*}}";

    private $id;

    private $lazyLoad = null;



    /**
     * Caching of external image
     * https://www.dokuwiki.org/images#caching
     */
    private $cache = true;


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


    private $description = null;
    /**
     * @var TagAttributes
     */
    private $attributes;

    /**
     * @var string the alt attribute value (known as the title for dokuwiki)
     */
    private $title;
    private $requestHeight;
    private $requestedWidth;



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

        parent::__construct($id,DokuPath::MEDIA_TYPE);


        $this->attributes = TagAttributes::createEmpty();
    }


    /**
     * Parse the img dokuwiki syntax
     * The output can be used to create an image object with the {@link self::createFromRenderAttributes()} function
     * @param $match
     * @return array
     */
    public static function getParseAttributes($match)
    {
        require_once(__DIR__ . '/../../../../inc/parser/handler.php');
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
        unset($attributes['src']);
        $media = self::createFromId($src);

        /**
         * Type must be the type of the media link
         * but we don't use it actually
         * we delete it them if present
         */
        if (key_exists(self::TYPE_KEY, $attributes)) {
            unset($attributes[self::TYPE_KEY]);
        }
        if (key_exists(self::WIDTH_KEY, $attributes)) {
            $width = $attributes[self::WIDTH_KEY];
            if (!empty($width)) {
                $media->setRequestedWidth($width);
            }
            unset($attributes[self::WIDTH_KEY]);
        }
        if (key_exists(self::HEIGHT_KEY, $attributes)) {
            $height = $attributes[self::HEIGHT_KEY];
            if (!empty($height)) {
                $media->setRequestedHeight($height);
            }
            unset($attributes[self::HEIGHT_KEY]);
        }
        if (key_exists(self::TITLE_KEY, $attributes)) {
            $title = $attributes[self::TITLE_KEY];
            if (!empty($title)) {
                $media->setTitle($title);
            }
            unset($attributes[self::TITLE_KEY]);
        }
        if (key_exists(self::LINKING_KEY, $attributes)) {
            $linking = $attributes[self::LINKING_KEY];
            if (!empty($linking)) {
                $media->setLinking($linking);
            }
            unset($attributes[self::LINKING_KEY]);
        }
        if (key_exists(self::CACHE_KEY, $attributes)) {
            $nocache = $attributes[self::CACHE_KEY];
            if (!empty($nocache)) {
                $media->setNoCache($nocache);
            }
            unset($attributes[self::CACHE_KEY]);
        }
        if (key_exists(TagAttributes::ALIGN_KEY,$attributes)) {
            $align = $attributes[TagAttributes::ALIGN_KEY];
            if (!empty($align)) {
                $media->setAlign($align);
            }
            unset($attributes[TagAttributes::ALIGN_KEY]);
        }

        foreach ($attributes as $key => $value) {
            $media->setAttribute($key, $value);
        }
        return $media;

    }

    public static function createFromRenderMatch($match)
    {
        $attributes = self::getParseAttributes($match);

        // Add the non-standard attribute in the form name=value
        $matches = array();
        $pattern = self::LINK_PATTERN;
        $found = preg_match("/$pattern/", $match, $matches);
        if ($found) {
            $link = $matches[1];
            $positionQueryCharacter = strpos($link, "?");
            if ($positionQueryCharacter !== false) {
                $queryParameters = substr($link, $positionQueryCharacter + 1);
                $parameters = StringUtility::explodeAndTrim($queryParameters, "&");
                foreach ($parameters as $parameter) {
                    $equalCharacterPosition = strpos($parameter, "=");
                    if ($equalCharacterPosition !== false) {
                        $parameterProp = explode("=", $parameter);
                        $attributes[$parameterProp[0]] = $parameterProp[1];
                    }
                }
            }
        }

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


    /**
     * @param $id
     * @param string $type - optional, we already know that this is a media, the linter is not happy otherwise
     * @return RasterImageLink|InternalMediaLink
     */
    public static function createFromId($id, $type = DokuPath::MEDIA_TYPE)
    {
        $dokuPath = DokuPath::createFromId($id,$type);
        $mime = $dokuPath->getKnownMime();
        if (substr($mime, 0, 5) == 'image') {
            if (substr($mime, 6) == "svg+xml") {
                $internalMedia = new SvgImageLink($id);
            } else {
                $internalMedia = new RasterImageLink($id);
            }
        } else {
            if ($mime == false) {
                LogUtility::msg("The mime type of the media ($id) is <a href=\"https://www.dokuwiki.org/mime\">unknown (not in the configuration file)</a>", LogUtility::LVL_MSG_ERROR, "support");
            } else {
                LogUtility::msg("Internal error: The type ($mime) of media ($id) with the typ is not yet implemented", LogUtility::LVL_MSG_ERROR, "support");
            }
            $internalMedia = new InternalMediaLink($id);
        }
        return $internalMedia;
    }

    /**
     * Return the same array than with the {@link self::parse()} method
     * that is used in the {@link CallStack}
     */
    public function toCallStackArray()
    {
        $array = array(
            'type' => null, // ??? internal, external media
            'src' => $this->getId(),
            self::TITLE_KEY => $this->getTitle(),
            TagAttributes::ALIGN_KEY => $this->getAlign(),
            self::WIDTH_KEY => $this->getRequestedWidth(),
            self::HEIGHT_KEY => $this->getRequestedHeight(),
            self::CACHE_KEY => $this->getCache(),
            self::LINKING_KEY => $this->getLinking()
        );
        // Add the extra attribute
        $array = array_merge($this->getAttributes()->toCallStackArray(),$array);
        return $array;
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
        $this->cache = $cache;
    }

    public function getRequestedHeight()
    {
        return $this->requestHeight;
    }

    /**
     * The requested height
     * @param $height
     */
    public function setRequestedHeight($height)
    {
        $this->requestHeight = $height;
    }

    /**
     * The requested width
     */
    public function getRequestedWidth()
    {
        return $this->requestedWidth;
    }

    /**
     * The requested width
     * @param $width
     */
    public function setRequestedWidth($width)
    {
        $this->requestedWidth = $width;
    }

    public function setLinking($linking)
    {
        $this->linking = $linking;
    }

    /**
     * Render attribute
     *   * 'center'
     *   * 'right'
     *   * 'left'
     * @param $align
     */
    protected function setAlign($align)
    {
        /**
         * Align is already a component attribute
         */
        $this->getAttributes()->addComponentAttributeValue(TagAttributes::ALIGN_KEY, $align);
    }


    public function getCache()
    {
        return $this->cache;
    }

    protected function getTitle()
    {
        return $this->title;
    }

    /**
     * @param $title - the alt of the link
     */
    protected function setTitle($title)
    {
        $this->title = $title;
    }

    private function setAlt($title)
    {
        $this->setTitle($title);
    }

    public function setDescription($description)
    {
        $this->setTitle($description);
    }

    public function getDescription()
    {
        return $this->getTitle();
    }

    public function getAlt()
    {
        return $this->getTitle();
    }

    public function __toString()
    {
        return $this->getId();
    }

    private function getAlign()
    {
        return $this->getAttributes()->getComponentAttributeValue(TagAttributes::ALIGN_KEY, null);
    }

    private function getLinking()
    {
        return $this->getAttributes()->getComponentAttributeValue("linking", null);
    }

    /**
     * @param TagAttributes $attributes
     */
    public function renderMediaTag(&$attributes = null)
    {
        if ($attributes == null) {
            $attributes = $this->getAttributes();
        }

    }


    public function getFile()
    {
        return new File(mediaFN($this->getId()));
    }

    public function getAttributes()
    {

        return $this->attributes;
    }

    public function setAttribute($key, $value)
    {
        $this->getAttributes()->addComponentAttributeValue($key, $value);
    }

    public function getAttribute($key)
    {
        return $this->getAttributes()->getComponentAttributeValue($key, null);
    }




}
