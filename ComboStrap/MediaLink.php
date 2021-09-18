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

require_once(__DIR__ . '/PluginUtility.php');

/**
 * Class InternalMedia
 * Represent a media link
 *
 *
 * @package ComboStrap
 *
 * Wrapper around {@link Doku_Handler_Parse_Media}
 *
 * Not that for dokuwiki the `type` key of the attributes is the `call`
 * and therefore determine the function in an render
 * (ie {@link \Doku_Renderer::internalmedialink()} or {@link \Doku_Renderer::externalmedialink()}
 *
 * This is a link to a media (pdf, image, ...).
 * It's used to check the media type and to
 * take over if the media type is an image
 */
abstract class MediaLink
{


    /**
     * The dokuwiki type and mode name
     * (ie call)
     *  * ie {@link MediaLink::EXTERNAL_MEDIA_CALL_NAME}
     *  or {@link MediaLink::INTERNAL_MEDIA_CALL_NAME}
     *
     * The dokuwiki type (internalmedia/externalmedia)
     * is saved in a `type` key that clash with the
     * combostrap type. To avoid the clash, we renamed it
     */
    const MEDIA_DOKUWIKI_TYPE = 'dokuwiki_type';
    const INTERNAL_MEDIA_CALL_NAME = "internalmedia";
    const EXTERNAL_MEDIA_CALL_NAME = "externalmedia";

    const CANONICAL = "image";

    /**
     * This attributes does not apply
     * to a URL
     * They are only for the tag (img, svg, ...)
     * or internal
     */
    const NON_URL_ATTRIBUTES = [
        MediaLink::ALIGN_KEY,
        MediaLink::LINKING_KEY,
        TagAttributes::TITLE_KEY,
        Hover::ON_HOVER_ATTRIBUTE,
        Animation::ON_VIEW_ATTRIBUTE,
        MediaLink::MEDIA_DOKUWIKI_TYPE,
        MediaLink::DOKUWIKI_SRC
    ];

    /**
     * This attribute applies
     * to a image url (img, svg, ...)
     */
    const URL_ATTRIBUTES = [
        Dimension::WIDTH_KEY,
        Dimension::HEIGHT_KEY,
        CacheMedia::CACHE_KEY,
    ];

    /**
     * Default image linking value
     */
    const CONF_DEFAULT_LINKING = "defaultImageLinking";
    const LINKING_LINKONLY_VALUE = "linkonly";
    const LINKING_DETAILS_VALUE = 'details';
    const LINKING_NOLINK_VALUE = 'nolink';

    /**
     * @deprecated 2021-06-12
     */
    const LINK_PATTERN = "{{\s*([^|\s]*)\s*\|?.*}}";

    const LINKING_DIRECT_VALUE = 'direct';

    /**
     * Only used by Dokuwiki
     * Contains the path and eventually an anchor
     * never query parameters
     */
    const DOKUWIKI_SRC = "src";
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
    const ALIGN_KEY = 'align';


    private $lazyLoad = null;


    /**
     * @var TagAttributes
     */
    protected $tagAttributes;

    /**
     * The path of the media
     * @var DokuPath[]
     */
    private $dokuPath;


    /**
     * Image constructor.
     * @param Image $dokuPath
     * @param null $tagAttributes
     *
     * Protected and not private
     * to allow cascading init
     * If private, the parent attributes are null
     */
    protected function __construct(DokuPath $dokuPath, $tagAttributes = null)
    {

        $this->dokuPath = $dokuPath;

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
        $tagAttributes->addComponentAttributeValue(self::ALIGN_KEY, $align);
        $tagAttributes->addComponentAttributeValue(Dimension::WIDTH_KEY, $width);
        $tagAttributes->addComponentAttributeValue(Dimension::HEIGHT_KEY, $height);
        $tagAttributes->addComponentAttributeValue(CacheMedia::CACHE_KEY, $cache);
        $tagAttributes->addComponentAttributeValue(self::LINKING_KEY, $linking);

        return self::createMediaLinkFromNonQualifiedPath($id, $tagAttributes);

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
        if (!isset($attributes[DokuPath::PATH_ATTRIBUTE])) {
            $path = "notfound";
            LogUtility::msg("A path attribute is mandatory when creating a media link and was not found in the attributes " . print_r($attributes, true), LogUtility::LVL_MSG_ERROR, self::CANONICAL);
        } else {
            $path = $attributes[DokuPath::PATH_ATTRIBUTE];
            unset($attributes[DokuPath::PATH_ATTRIBUTE]);
        }


        $tagAttributes = TagAttributes::createFromCallStackArray($attributes);


        return self::createMediaLinkFromNonQualifiedPath($path, $rev, $tagAttributes);

    }

    /**
     * @param $match - the match of the renderer (just a shortcut)
     * @return MediaLink
     */
    public static function createFromRenderMatch($match)
    {

        /**
         * The parsing function {@link Doku_Handler_Parse_Media} has some flow / problem
         *    * It keeps the anchor only if there is no query string
         *    * It takes the first digit as the width (ie media.pdf?page=31 would have a width of 31)
         *    * `src` is not only the media path but may have a anchor
         * We parse it then
         */


        /**
         *   * Delete the opening and closing character
         *   * create the url and description
         */
        $match = preg_replace(array('/^\{\{/', '/\}\}$/u'), '', $match);
        $parts = explode('|', $match, 2);
        $description = null;
        $url = $parts[0];
        if (isset($parts[1])) {
            $description = $parts[1];
        }

        /**
         * Media Alignment
         */
        $rightAlign = (bool)preg_match('/^ /', $url);
        $leftAlign = (bool)preg_match('/ $/', $url);
        $url = trim($url);

        // Logic = what's that ;)...
        if ($leftAlign & $rightAlign) {
            $align = 'center';
        } else if ($rightAlign) {
            $align = 'right';
        } else if ($leftAlign) {
            $align = 'left';
        } else {
            $align = null;
        }

        /**
         * The combo attributes array
         */
        $parsedAttributes = DokuwikiUrl::createFromUrl($url)->toArray();
        $path = $parsedAttributes[DokuPath::PATH_ATTRIBUTE];
        if (!isset($parsedAttributes[MediaLink::LINKING_KEY])) {
            $parsedAttributes[MediaLink::LINKING_KEY] = PluginUtility::getConfValue(self::CONF_DEFAULT_LINKING, self::LINKING_DIRECT_VALUE);
        }

        /**
         * Media Type
         */
        if (media_isexternal($path) || link_isinterwiki($path)) {
            $mediaType = MediaLink::EXTERNAL_MEDIA_CALL_NAME;
        } else {
            $mediaType = MediaLink::INTERNAL_MEDIA_CALL_NAME;
        }


        /**
         * src in dokuwiki is the path and the anchor if any
         */
        $src = $path;
        if (isset($parsedAttributes[DokuwikiUrl::ANCHOR_ATTRIBUTES]) != null) {
            $src = $src . "#" . $parsedAttributes[DokuwikiUrl::ANCHOR_ATTRIBUTES];
        }

        /**
         * To avoid clash with the combostrap component type
         * ie this is also a ComboStrap attribute where we set the type of a SVG (icon, illustration, background)
         * we store the media type (ie external/internal) in another key
         *
         * There is no need to repeat the attributes as the arrays are merged
         * into on but this is also an informal code to show which attributes
         * are only Dokuwiki Native
         *
         */
        $dokuwikiAttributes = array(
            self::MEDIA_DOKUWIKI_TYPE => $mediaType,
            self::DOKUWIKI_SRC => $src,
            Dimension::WIDTH_KEY => $parsedAttributes[Dimension::WIDTH_KEY],
            Dimension::HEIGHT_KEY => $parsedAttributes[Dimension::HEIGHT_KEY],
            CacheMedia::CACHE_KEY => $parsedAttributes[CacheMedia::CACHE_KEY],
            'title' => $description,
            MediaLink::ALIGN_KEY => $align,
            MediaLink::LINKING_KEY => $parsedAttributes[MediaLink::LINKING_KEY],
        );

        /**
         * Merge standard dokuwiki attributes and
         * parsed attributes
         */
        $mergedAttributes = PluginUtility::mergeAttributes($dokuwikiAttributes, $parsedAttributes);

        /**
         * If this is an internal media,
         * we are using our implementation
         * and we have a change on attribute specification
         */
        if ($mediaType == MediaLink::INTERNAL_MEDIA_CALL_NAME) {

            /**
             * The align attribute on an image parse
             * is a float right
             * ComboStrap does a difference between a block right and a float right
             */
            if ($mergedAttributes[self::ALIGN_KEY] === "right") {
                unset($mergedAttributes[self::ALIGN_KEY]);
                $mergedAttributes[FloatAttribute::FLOAT_KEY] = "right";
            }


        }

        return self::createFromCallStackArray($mergedAttributes);

    }


    public
    function setLazyLoad($false)
    {
        $this->lazyLoad = $false;
    }

    public
    function getLazyLoad()
    {
        return $this->lazyLoad;
    }


    /**
     * Create a media link from a unknown type path (ie relative or absolute)
     *
     * This function transforms the path to absolute against the actual namespace of the requested page ID if the
     * path is relative.
     *
     * @param $nonQualifiedPath
     * @param TagAttributes $tagAttributes
     * @param string $rev
     * @return MediaLink
     */
    public
    static function createMediaLinkFromNonQualifiedPath($nonQualifiedPath, $rev = null, $tagAttributes = null)
    {
        if (is_object($rev)) {
            LogUtility::msg("rev should not be an object", LogUtility::LVL_MSG_ERROR, "support");
        }
        if ($tagAttributes == null) {
            $tagAttributes = TagAttributes::createEmpty();
        } else {
            if (!($tagAttributes instanceof TagAttributes)) {
                LogUtility::msg("TagAttributes is not an instance of Tag Attributes", LogUtility::LVL_MSG_ERROR, "support");
            }
        }

        /**
         * Resolution
         */
        $qualifiedPath = $nonQualifiedPath;
        if (!media_isexternal($qualifiedPath)) {
            global $ID;
            $qualifiedId = $nonQualifiedPath;
            resolve_mediaid(getNS($ID), $qualifiedId, $exists);
            $qualifiedPath = DokuPath::PATH_SEPARATOR . $qualifiedId;
        }


        return self::createMediaLinkFromAbsolutePath($qualifiedPath,$rev,$tagAttributes);
    }

    public static function createMediaLinkFromAbsolutePath($qualifiedPath, $rev = null, $tagAttributes = null)
    {
        /**
         * Processing
         */
        $dokuPath = DokuPath::createMediaPathFromAbsolutePath($qualifiedPath, $rev);
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
                $svgImage = new ImageSvg($qualifiedPath, $rev);
                $internalMedia = new SvgImageLink($svgImage, $tagAttributes);
            } else {
                // The require is here because Raster Image Link is child of Internal Media Link (extends)
                require_once(__DIR__ . '/RasterImageLink.php');
                $rasterImage = new ImageRaster($qualifiedPath, $rev);
                $internalMedia = new RasterImageLink($rasterImage, $tagAttributes);
            }
        } else {
            if ($mime == false) {
                LogUtility::msg("The mime type of the media ($qualifiedPath) is <a href=\"https://www.dokuwiki.org/mime\">unknown (not in the configuration file)</a>", LogUtility::LVL_MSG_ERROR);
                $media = new ImageRaster($qualifiedPath, $rev);
                $internalMedia = new RasterImageLink($media, $tagAttributes);
            } else {
                LogUtility::msg("The type ($mime) of media ($qualifiedPath) is not an image", LogUtility::LVL_MSG_DEBUG, "image");
                $media = DokuPath::createMediaPathFromAbsolutePath($qualifiedPath, $rev);
                $internalMedia = new ThirdMediaLink($media, $tagAttributes);
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
    public
    function toCallStackArray()
    {
        /**
         * Trying to stay inline with the dokuwiki key
         * We use the 'src' attributes as id
         *
         * src is a path (not an id)
         */
        $array = array(
            DokuPath::PATH_ATTRIBUTE => $this->getDokuPath()->getPath()
        );


        // Add the extra attribute
        return array_merge($this->tagAttributes->toCallStackArray(), $array);


    }


    /**
     * @return string the wiki syntax
     */
    public
    function getMarkupSyntax()
    {
        $descriptionPart = "";
        if ($this->tagAttributes->hasComponentAttribute(TagAttributes::TITLE_KEY)) {
            $descriptionPart = "|" . $this->tagAttributes->getValue(TagAttributes::TITLE_KEY);
        }
        return '{{:' . $this->getDokuPath()->getId() . $descriptionPart . '}}';
    }


    public
    static function isInternalMediaSyntax($text)
    {
        return preg_match(' / ' . syntax_plugin_combo_media::MEDIA_PATTERN . ' / msSi', $text);
    }


    public
    function getRequestedHeight()
    {
        return $this->tagAttributes->getValue(Dimension::HEIGHT_KEY);
    }


    /**
     * The requested width
     */
    public
    function getRequestedWidth()
    {
        return $this->tagAttributes->getValue(Dimension::WIDTH_KEY);
    }


    public
    function getCache()
    {
        return $this->tagAttributes->getValue(CacheMedia::CACHE_KEY);
    }

    protected
    function getTitle()
    {
        return $this->tagAttributes->getValue(TagAttributes::TITLE_KEY);
    }


    public
    function __toString()
    {
        return $this->getDokuPath()->getId();
    }

    private
    function getAlign()
    {
        return $this->getTagAttributes()->getComponentAttributeValue(self::ALIGN_KEY, null);
    }

    private
    function getLinking()
    {
        return $this->getTagAttributes()->getComponentAttributeValue(self::LINKING_KEY, null);
    }


    public
    function &getTagAttributes(): TagAttributes
    {
        return $this->tagAttributes;
    }

    /**
     * @return string - the HTML of the image inside a link if asked
     */
    public
    function renderMediaTagWithLink(): string
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
        $linking = $this->tagAttributes->getValue(self::LINKING_KEY);
        $image = $this->getDokuPath();
        switch ($linking) {
            case self::LINKING_LINKONLY_VALUE: // show only a url
                $src = ml(
                    $image->getId(),
                    array(
                        'id' => $image->getId(),
                        'cache' => $this->getCache(),
                        'rev' => $image->getRevision()
                    )
                );
                $imageLink->addHtmlAttributeValue("href", $src);
                $title = $this->getTitle();
                if (empty($title)) {
                    $title = $image->getBaseName();
                }
                return $imageLink->toHtmlEnterTag("a") . $title . "</a>";
            case self::LINKING_NOLINK_VALUE:
                return $this->renderMediaTag();
            default:
            case self::LINKING_DIRECT_VALUE:
                //directly to the image
                $src = ml(
                    $image->getId(),
                    array(
                        'id' => $image->getId(),
                        'cache' => $this->getCache(),
                        'rev' => $image->getRevision()
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
                    $image->getId(),
                    array(
                        'id' => $image->getId(),
                        'cache' => $this->getCache(),
                        'rev' => $image->getRevision()
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
    public abstract function renderMediaTag(): string;

    /**
     * The Url
     * @return mixed
     */
    public abstract function getAbsoluteUrl();

    /**
     * The default image in a
     * @return Image
     */
    public function getDokuPath(): DokuPath
    {
        return $this->dokuPath;
    }


}
