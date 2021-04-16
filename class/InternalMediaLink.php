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

use dokuwiki\Extension\SyntaxPlugin;

require_once(__DIR__ . '/DokuPath.php');

/**
 * Class InternalMedia
 * Represent a media link
 * @package ComboStrap
 */
abstract class InternalMediaLink extends DokuPath
{

    /**
     * The dokuwiki mode name
     * for an internal media
     */
    const INTERNAL_MEDIA = "internalmedia";
    const INTERNAL_MEDIA_PATTERN = "\{\{(?:[^>\}]|(?:\}[^\}]))+\}\}";

    /**
     * Link value:
     *   * 'nolink'
     *   * 'direct': directly to the image
     *   * 'linkonly': show only a url
     *   * 'details': go to the details media viewer
     *
     * @var
     */
    const LINKING_KEY = 'linking';
    /**
     * @var string the alt attribute value (known as the title for dokuwiki)
     */
    const TITLE_KEY = 'title';
    const HEIGHT_KEY = 'height';
    const WIDTH_KEY = 'width';
    const CACHE_KEY = 'cache';
    const TYPE_KEY = "type";

    // Pattern to capture the link as first capture group
    const LINK_PATTERN = "{{\s*([a-z0-9A-Z:?=&.x\-_]*)\s*\|?.*}}";

    const CONF_IMAGE_ENABLE = "imageEnable";
    const CANONICAL = "image";

    /**
     * This URL encoding is mandatory for the {@link ml} function
     * when there is a width and use them not otherwise
     */
    const URL_ENCODED_AND = '&amp;';

    private $id;

    private $lazyLoad = null;


    private $description = null;

    /**
     * @var TagAttributes
     */
    protected $tagAttributes;


    /**
     * Image constructor.
     * @param $id
     * @param TagAttributes $tagAttributes
     *
     * Protected and not private
     * to allow cascading init
     * If private, the parent attributes are null
     */
    protected function __construct($id, $tagAttributes = null)
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

        parent::__construct($id, DokuPath::MEDIA_TYPE);

        if ($tagAttributes == null) {
            $this->tagAttributes = TagAttributes::createEmpty();
        } else {
            $this->tagAttributes = $tagAttributes;
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
        require_once(__DIR__ . '/../../../../inc/parser/handler.php');
        return Doku_Handler_Parse_Media($match);
    }

    /**
     * Create an image from dokuwiki internal call media attributes
     * @param array $callAttributes
     * @return InternalMediaLink
     */
    public static function createFromIndexAttributes(array $callAttributes)
    {
        $id = $callAttributes[0]; // path
        $title = $callAttributes[1];
        $align = $callAttributes[2];
        $width = $callAttributes[3];
        $height = $callAttributes[4];
        $cache = $callAttributes[5];
        $linking = $callAttributes[6];

        $tagAttributes = TagAttributes::createEmpty();
        $tagAttributes->addComponentAttributeValue(self::TITLE_KEY, $title);
        $tagAttributes->addComponentAttributeValue(TagAttributes::ALIGN_KEY, $align);
        $tagAttributes->addComponentAttributeValue(self::WIDTH_KEY, $width);
        $tagAttributes->addComponentAttributeValue(self::HEIGHT_KEY, $height);
        $tagAttributes->addComponentAttributeValue(self::CACHE_KEY, $cache);
        $tagAttributes->addComponentAttributeValue(self::LINKING_KEY, $linking);

        return self::createMediaPathFromId($id, $tagAttributes);

    }

    /**
     * A function to explicitly create an internal media from
     * a call stack array (ie key string and value) that we get in the {@link SyntaxPlugin::render()}
     * from the {@link InternalMediaLink::toCallStackArray()}
     *
     * @param $attributes - the attributes created by the function {@link InternalMediaLink::getParseAttributes()}
     * @return InternalMediaLink|RasterImageLink|SvgImageLink
     */
    public static function createFromCallStackArray(&$attributes)
    {

        $src = cleanID($attributes['src']);
        unset($attributes["src"]);

        /**
         * Type must be the type of the media link
         * but we don't use it actually
         * we delete it them if present
         *
         * All other are valid component attribute
         */
        if (key_exists(self::TYPE_KEY, $attributes)) {
            unset($attributes[self::TYPE_KEY]);
        }


        $tagAttributes = TagAttributes::createFromCallStackArray($attributes);

        return self::createMediaPathFromId($src, $tagAttributes);

    }

    /**
     * @param $match - the match of the renderer (just a shortcut)
     * @return InternalMediaLink|RasterImageLink|SvgImageLink|null
     */
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

        return self::createFromCallStackArray($attributes);
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
     * @param TagAttributes $tagAttributes
     * @return RasterImageLink|InternalMediaLink
     */
    public static function createMediaPathFromId($id, $tagAttributes = null)
    {
        $dokuPath = DokuPath::createMediaPathFromId($id);
        if ($dokuPath->getExtension() == "svg") {
            /**
             * The mime type is set when uploading, not when
             * viewing.
             * Because they are internal image, the svg was already uploaded
             * Therefore, no authorization scheme here
             */
            $mime = "image/svg+xml";
        } else {
            $mime = $dokuPath->getKnownMime();
        }
        if (substr($mime, 0, 5) == 'image') {
            if (substr($mime, 6) == "svg+xml") {
                // The require is here because Svg Image Link is child of Internal Media Link (extends)
                require_once(__DIR__ . '/SvgImageLink.php');
                $internalMedia = new SvgImageLink($id, $tagAttributes);
            } else {
                // The require is here because Raster Image Link is child of Internal Media Link (extends)
                require_once(__DIR__ . '/RasterImageLink.php');
                $internalMedia = new RasterImageLink($id, $tagAttributes);
            }
        } else {
            if ($mime == false) {
                LogUtility::msg("The mime type of the media ($id) is <a href=\"https://www.dokuwiki.org/mime\">unknown (not in the configuration file)</a>", LogUtility::LVL_MSG_ERROR, "support");
                $internalMedia = new RasterImageLink($id, $tagAttributes);
            } else {
                LogUtility::msg("The type ($mime) of media ($id) is not an image", LogUtility::LVL_MSG_ERROR, "image");
                $internalMedia = null;
            }
        }


        return $internalMedia;
    }


    /**
     * A function to set explicitly which array format
     * is used in the returned data of a {@link SyntaxPlugin::handle()}
     * (which ultimately is stored in the {@link CallStack)
     *
     * This is to make the difference with the {@link InternalMediaLink::createFromIndexAttributes()}
     * that is indexed by number (ie without property name)
     *
     *
     * Return the same array than with the {@link self::parse()} method
     * that is used in the {@link CallStack}
     *
     * @return array of key string and value
     */
    public function toCallStackArray()
    {
        /**
         * The only attributes that are not in the {@link InternalMediaLink::$tagAttributes}
         * component attributes are type and src
         */
        $array = array(
            'type' => null, // internal, external media, not used
            'src' => $this->getId()
        );
        // Add the extra attribute
        return array_merge($this->tagAttributes->toCallStackArray(), $array);

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


    public function getRequestedHeight()
    {
        return $this->tagAttributes->getValue(self::HEIGHT_KEY);
    }


    /**
     * The requested width
     */
    public function getRequestedWidth()
    {
        return $this->tagAttributes->getValue(self::WIDTH_KEY);
    }


    public function getCache()
    {
        return $this->tagAttributes->getValue(self::CACHE_KEY);
    }

    protected function getTitle()
    {
        return $this->tagAttributes->getValue(self::TITLE_KEY);
    }


    public function __toString()
    {
        return $this->getId();
    }

    private function getAlign()
    {
        return $this->getTagAttributes()->getComponentAttributeValue(TagAttributes::ALIGN_KEY, null);
    }

    private function getLinking()
    {
        return $this->getTagAttributes()->getComponentAttributeValue("linking", null);
    }


    public function getTagAttributes()
    {

        return $this->tagAttributes;
    }


    /**
     * @return string - the HTML
     */
    public abstract function renderMediaTag();

    public abstract function getAbsoluteUrl();


}
