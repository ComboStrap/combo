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
use syntax_plugin_combo_media;

require_once(__DIR__ . '/DokuPath.php');

/**
 * Class InternalMedia
 * Represent a media link
 * @package ComboStrap
 *
 * Wrapper around {@link Doku_Handler_Parse_Media}
 *
 * Not that for dokuwiki the `type` key of the attributes is the `call`
 * and therefore determine the function in an render
 * (ie {@link \Doku_Renderer::internalmedialink()} or {@link \Doku_Renderer::externalmedialink()}
 *
 */
abstract class MediaLink extends DokuPath
{


    /**
     * The dokuwiki type and mode name
     * (ie call)
     *  * ie {@link MediaLink::EXTERNAL_MEDIA}
     *  or {@link MediaLink::INTERNAL_MEDIA}
     *
     * The dokuwiki type (internalmedia/externalmedia)
     * is saved in a `type` key that clash with the
     * combostrap type. To avoid the clash, we renamed it
     */
    const MEDIA_DOKUWIKI_TYPE = 'dokuwiki_type';
    const INTERNAL_MEDIA = "internalmedia";
    const EXTERNAL_MEDIA = "externalmedia";

    const CONF_IMAGE_ENABLE = "imageEnable";

    const CANONICAL = "image";

    /**
     * This attributes does not apply
     * to a URL
     * They are only for the tag (img, svg, ...)
     * or internal
     */
    const NON_URL_ATTRIBUTES = [
        TagAttributes::ALIGN_KEY,
        TagAttributes::LINKING_KEY,
        TagAttributes::TITLE_KEY,
        Hover::ON_HOVER_ATTRIBUTE,
        Animation::ON_VIEW_ATTRIBUTE,
        MediaLink::MEDIA_DOKUWIKI_TYPE
    ];

    /**
     * This attribute applies
     * to a image url (img, svg, ...)
     */
    const URL_ATTRIBUTES = [
        TagAttributes::WIDTH_KEY,
        TagAttributes::HEIGHT_KEY,
        CacheMedia::CACHE_KEY,
    ];
    /**
     * The dokuwiki media property used in a link
     */
    const DOKUWIKI_QUERY_MEDIA_PROPERTY = ["w", "h"];
    /**
     * Default image linking value
     */
    const CONF_DEFAULT_LINKING = "defaultImageLinking";
    const LINKING_LINKONLY_VALUE = "linkonly";
    const LINKING_DETAILS_VALUE = 'details';
    const SRC_KEY = "src"; // called pathId in Combo
    const LINKING_NOLINK_VALUE = 'nolink';
    const LINK_PATTERN = "{{\s*([^|\s]*)\s*\|?.*}}";

    const LINKING_DIRECT_VALUE = 'direct';


    private $lazyLoad = null;


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


        parent::__construct($id, DokuPath::MEDIA_TYPE, $rev);

        if ($tagAttributes == null) {
            $this->tagAttributes = TagAttributes::createEmpty();
        } else {
            $this->tagAttributes = $tagAttributes;
        }

    }


    /**
     * Create an image from dokuwiki internal call media attributes
     * @param array $callAttributes
     * @return MediaLink
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
        $tagAttributes->addComponentAttributeValue(CacheMedia::CACHE_KEY, $cache);
        $tagAttributes->addComponentAttributeValue(TagAttributes::LINKING_KEY, $linking);

        return self::createMediaLinkFromPathId($id, $tagAttributes);

    }

    /**
     * A function to explicitly create an internal media from
     * a call stack array (ie key string and value) that we get in the {@link SyntaxPlugin::render()}
     * from the {@link MediaLink::toCallStackArray()}
     *
     * @param $attributes - the attributes created by the function {@link MediaLink::getParseAttributes()}
     * @param $rev - the mtime
     * @return MediaLink|RasterImageLink|SvgImageLink
     */
    public static function createFromCallStackArray($attributes, $rev = null)
    {

        if (!is_array($attributes)) {
            // Debug for the key_exist below because of the following message:
            // `PHP Warning:  key_exists() expects parameter 2 to be array, array given`
            LogUtility::msg("The `attributes` parameter is not an array. Value ($attributes)", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
        }

        /**
         * Media id are not cleaned
         * They are always absolute ?
         */
        $src = $attributes['src'];
        unset($attributes["src"]);


        $tagAttributes = TagAttributes::createFromCallStackArray($attributes);

        return self::createMediaLinkFromPathId($src, $rev, $tagAttributes);

    }

    /**
     * @param $match - the match of the renderer (just a shortcut)
     * @return MediaLink
     */
    public static function createFromRenderMatch($match)
    {
        /**
         * Even if deprecated because of dimension we use
         * it to handle the other attributes such as cache, an align
         */
        require_once(__DIR__ . '/../../../../inc/parser/handler.php');
        $attributes = Doku_Handler_Parse_Media($match);

        /**
         * To avoid clash with the combostrap type
         * ie this is also a ComboStrap attribute where we set the type of a SVG (icon, illustration, background)
         * we store the media type (ie external/internal) in another key
         */
        $type = $attributes["type"];
        $attributes[self::MEDIA_DOKUWIKI_TYPE] = $type;
        unset($attributes["type"]);
        if ($type == MediaLink::INTERNAL_MEDIA) {

            /**
             * The {@link MediaLink::createFromRenderMatch() previous function}
             * takes the first digit as the width
             * (bad pattern for the width and height parsing)
             * We set them to null and parse them below
             */
            $attributes[TagAttributes::WIDTH_KEY] = null;
            $attributes[TagAttributes::HEIGHT_KEY] = null;

            /**
             * The align attribute on an image parse
             * is a float right
             * ComboStrap does a difference between a block right and a float right
             */
            if ($attributes[TagAttributes::ALIGN_KEY] === "right") {
                unset($attributes[TagAttributes::ALIGN_KEY]);
                $attributes[FloatAttribute::FLOAT_KEY] = "right";
            }

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
                    $parameters = Url::queryParametersToArray($queryParameters);
                    foreach ($parameters as $key => $value) {
                        if ($value != null) {
                            if (!in_array($key, self::DOKUWIKI_QUERY_MEDIA_PROPERTY)) {
                                /**
                                 * exclude already parsed w=xxxx and h=wwww
                                 */
                                $attributes[$key] = $value;
                            }
                        } else {
                            /**
                             * Linking
                             */
                            if ($linkingAttributeFound == false
                                &&
                                preg_match('/(nolink|direct|linkonly|details)/i', $key)) {
                                $linkingAttributeFound = true;
                            }
                            /**
                             * Sizing (wxh)
                             */
                            $sizing = [];
                            if (preg_match('/([0-9]+)(x([0-9]+))?/', $key, $sizing)) {
                                $attributes[TagAttributes::WIDTH_KEY] = $sizing[1];
                                if (isset($sizing[3])) {
                                    $attributes[TagAttributes::HEIGHT_KEY] = $sizing[3];
                                }
                            }
                        }
                    }
                }
            }

            if (!$linkingAttributeFound) {
                $attributes[TagAttributes::LINKING_KEY] = PluginUtility::getConfValue(self::CONF_DEFAULT_LINKING, self::LINKING_DIRECT_VALUE);
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
     * @param $pathId
     * @param TagAttributes $tagAttributes
     * @param string $rev
     * @return MediaLink
     */
    public static function createMediaLinkFromPathId($pathId, $rev = null, $tagAttributes = null)
    {
        if (is_object($rev)) {
            LogUtility::msg("rev should not be an object", LogUtility::LVL_MSG_ERROR, "support");
        }
        if (!($tagAttributes instanceof TagAttributes) && $tagAttributes != null) {
            LogUtility::msg("TagAttributes is not an instance of Tag Attributes", LogUtility::LVL_MSG_ERROR, "support");
        }
        $dokuPath = DokuPath::createMediaPathFromPath($pathId, $rev);
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
                $internalMedia = new SvgImageLink($pathId, $tagAttributes, $rev);
            } else {
                // The require is here because Raster Image Link is child of Internal Media Link (extends)
                require_once(__DIR__ . '/RasterImageLink.php');
                $internalMedia = new RasterImageLink($pathId, $tagAttributes);
            }
        } else {
            if ($mime == false) {
                LogUtility::msg("The mime type of the media ($pathId) is <a href=\"https://www.dokuwiki.org/mime\">unknown (not in the configuration file)</a>", LogUtility::LVL_MSG_ERROR, "support");
                $internalMedia = new RasterImageLink($pathId, $tagAttributes);
            } else {
                LogUtility::msg("The type ($mime) of media ($pathId) is not an image", LogUtility::LVL_MSG_DEBUG, "image");
                $internalMedia = new ThirdMediaLink($pathId, $tagAttributes);
            }
        }


        return $internalMedia;
    }


    /**
     * A function to set explicitly which array format
     * is used in the returned data of a {@link SyntaxPlugin::handle()}
     * (which ultimately is stored in the {@link CallStack)
     *
     * This is to make the difference with the {@link MediaLink::createFromIndexAttributes()}
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
         * Trying to stay inline with the dokuwiki key
         * We use the 'src' attributes as id
         *
         * src is a path (not an id)
         */
        $array = array(
            'src' => $this->getAbsolutePath()
        );


        // Add the extra attribute
        return array_merge($this->tagAttributes->toCallStackArray(), $array);


    }


    /**
     * @return string the wiki syntax
     */
    public function getMarkupSyntax()
    {
        $descriptionPart = "";
        if ($this->tagAttributes->hasComponentAttribute(TagAttributes::TITLE_KEY)) {
            $descriptionPart = "|" . $this->tagAttributes->getValue(TagAttributes::TITLE_KEY);
        }
        return '{{:' . $this->getId() . $descriptionPart . '}}';
    }


    public static function isInternalMediaSyntax($text)
    {
        return preg_match(' / ' . syntax_plugin_combo_media::MEDIA_PATTERN . ' / msSi', $text);
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
        return $this->tagAttributes->getValue(CacheMedia::CACHE_KEY);
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
        $linking = $this->tagAttributes->getValue(TagAttributes::LINKING_KEY);
        switch ($linking) {
            case self::LINKING_LINKONLY_VALUE: // show only a url
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
            case self::LINKING_NOLINK_VALUE:
                return $this->renderMediaTag();
            default:
            case self::LINKING_DIRECT_VALUE:
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

            case self::LINKING_DETAILS_VALUE:
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
