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

    // Pattern to capture the link as first capture group
    const LINK_PATTERN = "{{\s*([^|\s]*)\s*\|?.*}}";

    const CONF_IMAGE_ENABLE = "imageEnable";
    const CANONICAL = "image";

    /**
     * This URL encoding is mandatory for the {@link ml} function
     * when there is a width and use them not otherwise
     */
    const URL_ENCODED_AND = '&amp;';

    /**
     * Default image linking value
     */
    const CONF_DEFAULT_LINKING = "defaultImageLinking";
    const CONF_LINKING_DIRECT_VALUE = 'direct';
    const CONF_LINKING_NOLINK_VALUE = 'nolink';
    const CONF_LINKING_DETAILS_VALUE = 'details';
    const CONF_LINKING_LINKONLY_VALUE = "linkonly";

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
     * @param string $rev - mtime
     *
     * Protected and not private
     * to allow cascading init
     * If private, the parent attributes are null
     *
     */
    protected function __construct($id, $tagAttributes = null, $rev = null)
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

        parent::__construct($id, DokuPath::MEDIA_TYPE, $rev);

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
        $tagAttributes->addComponentAttributeValue(TagAttributes::TITLE_KEY, $title);
        $tagAttributes->addComponentAttributeValue(TagAttributes::ALIGN_KEY, $align);
        $tagAttributes->addComponentAttributeValue(TagAttributes::WIDTH_KEY, $width);
        $tagAttributes->addComponentAttributeValue(TagAttributes::HEIGHT_KEY, $height);
        $tagAttributes->addComponentAttributeValue(TagAttributes::CACHE_KEY, $cache);
        $tagAttributes->addComponentAttributeValue(TagAttributes::LINKING_KEY, $linking);

        return self::createMediaPathFromId($id, $tagAttributes);

    }

    /**
     * A function to explicitly create an internal media from
     * a call stack array (ie key string and value) that we get in the {@link SyntaxPlugin::render()}
     * from the {@link InternalMediaLink::toCallStackArray()}
     *
     * @param $attributes - the attributes created by the function {@link InternalMediaLink::getParseAttributes()}
     * @param $rev - the mtime
     * @return InternalMediaLink|RasterImageLink|SvgImageLink
     */
    public static function createFromCallStackArray(&$attributes, $rev = null)
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
        if (key_exists(TagAttributes::TYPE_KEY, $attributes)) {
            unset($attributes[TagAttributes::TYPE_KEY]);
        }


        $tagAttributes = TagAttributes::createFromCallStackArray($attributes);

        return self::createMediaPathFromId($src, $rev, $tagAttributes);

    }

    /**
     * @param $match - the match of the renderer (just a shortcut)
     * @return InternalMediaLink|RasterImageLink|SvgImageLink|null
     */
    public static function createFromRenderMatch($match)
    {
        $attributes = self::getParseAttributes($match);

        /**
         * Do we have a linking attribute
         */
        $linkingAttributeFound = false;

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
                    } else {
                        if ($linkingAttributeFound == false
                            &&
                            preg_match('/(nolink|direct|linkonly|details)/i', $parameter)) {
                            $linkingAttributeFound = true;
                        }
                    }
                }
            }
        }

        if (!$linkingAttributeFound) {
            $attributes[TagAttributes::LINKING_KEY] = PluginUtility::getConfValue(self::CONF_DEFAULT_LINKING, self::CONF_LINKING_DIRECT_VALUE);
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
     * @param string $rev
     * @return RasterImageLink|InternalMediaLink
     */
    public static function createMediaPathFromId($id, $rev = null, $tagAttributes = null)
    {
        if (is_object($rev)) {
            LogUtility::msg("rev should not be an object", LogUtility::LVL_MSG_ERROR, "support");
        }
        if (!($tagAttributes instanceof TagAttributes) && $tagAttributes != null) {
            LogUtility::msg("TagAttributes is not an instance of Tag Attributes", LogUtility::LVL_MSG_ERROR, "support");
        }
        $dokuPath = DokuPath::createMediaPathFromId($id, $rev);
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
                $internalMedia = new SvgImageLink($id, $tagAttributes, $rev);
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
        return $this->tagAttributes->getValue(TagAttributes::HEIGHT_KEY);
    }


    /**
     * The requested width
     */
    public function getRequestedWidth()
    {
        return $this->tagAttributes->getValue(TagAttributes::WIDTH_KEY);
    }


    public function getCache()
    {
        return $this->tagAttributes->getValue(TagAttributes::CACHE_KEY);
    }

    protected function getTitle()
    {
        return $this->tagAttributes->getValue(TagAttributes::TITLE_KEY);
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
        return $this->getTagAttributes()->getComponentAttributeValue(TagAttributes::LINKING_KEY, null);
    }


    public function &getTagAttributes()
    {
        return $this->tagAttributes;
    }

    /**
     * @return string - the HTML of the image inside a link if asked
     */
    public function renderMediaTagWithLink()
    {

        /**
         * Link to the media
         *
         */
        $imageLink = TagAttributes::createEmpty();
        // https://www.dokuwiki.org/config:target
        global $conf;
        $target = $conf['target']['media'];
        $imageLink->addHtmlAttributeValueIfNotEmpty("target", $target);
        if (!empty($target)) {
            $imageLink->addHtmlAttributeValue("rel", 'noopener');
        }

        /**
         * Do we add a link to the image ?
         */
        $linking = $this->tagAttributes->getValueAndRemove(TagAttributes::LINKING_KEY);
        switch ($linking) {
            case self::CONF_LINKING_LINKONLY_VALUE: // show only a url
                $src = ml(
                    $this->getId(),
                    array(
                        'id' => $this->getId(),
                        'cache' => $this->getCache(),
                        'rev' => $this->getRevision()
                    )
                );
                $imageLink->addHtmlAttributeValue("href", $src);
                $title = $this->getTitle();
                if (empty($title)) {
                    $title = $this->getBaseName();
                }
                return $imageLink->toHtmlEnterTag("a") . $title . "</a>";
            case self::CONF_LINKING_NOLINK_VALUE:
                return $this->renderMediaTag();
            default:
            case self::CONF_LINKING_DIRECT_VALUE:
                //directly to the image
                $src = ml(
                    $this->getId(),
                    array(
                        'id' => $this->getId(),
                        'cache' => $this->getCache(),
                        'rev' => $this->getRevision()
                    ),
                    true
                );
                $imageLink->addHtmlAttributeValue("href", $src);
                return $imageLink->toHtmlEnterTag("a") .
                    $this->renderMediaTag() .
                    "</a>";

            case self::CONF_LINKING_DETAILS_VALUE:
                //go to the details media viewer
                $src = ml(
                    $this->getId(),
                    array(
                        'id' => $this->getId(),
                        'cache' => $this->getCache(),
                        'rev' => $this->getRevision()
                    ),
                    false
                );
                $imageLink->addHtmlAttributeValue("href", $src);
                return $imageLink->toHtmlEnterTag("a") .
                    $this->renderMediaTag() .
                    "</a>";

        }


    }

    /**
     * @return string - the HTML of the image
     */
    public abstract function renderMediaTag();

    public abstract function getAbsoluteUrl();


}
