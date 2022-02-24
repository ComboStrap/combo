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

use dokuwiki\Action\Plugin;
use dokuwiki\Extension\SyntaxPlugin;
use dokuwiki\Parsing\ParserMode\Internallink;
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

    /**
     * The method to lazy load resources (Ie media)
     */
    const LAZY_LOAD_METHOD = "lazy-method";
    const LAZY_LOAD_METHOD_HTML_VALUE = "html-attribute";
    const LAZY_LOAD_METHOD_LOZAD_VALUE = "lozad";
    const UNKNOWN_MIME = "unknwon";
    /**
     * @var string
     */
    private $lazyLoadMethod;

    private $lazyLoad = null;


    /**
     * The path of the media
     * @var Media[]
     */
    private $media;
    private $linking;
    private $linkingClass;


    /**
     * Image constructor.
     * @param Image $media
     *
     * Protected and not private
     * to allow cascading init
     * If private, the parent attributes are null
     */
    protected function __construct(Media $media)
    {
        $this->media = $media;
    }


    /**
     * Create an image from dokuwiki {@link Internallink internal call media attributes}
     *
     * Dokuwiki extracts already the width, height and align property
     * @param array $callAttributes
     * @return MediaLink
     */
    public static function createFromIndexAttributes(array $callAttributes)
    {
        $src = $callAttributes[0];
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

        return self::createMediaLinkFromId($src, $tagAttributes);

    }

    /**
     * A function to explicitly create an internal media from
     * a call stack array (ie key string and value) that we get in the {@link SyntaxPlugin::render()}
     * from the {@link MediaLink::toCallStackArray()}
     *
     * @param $attributes - the attributes created by the function {@link MediaLink::getParseAttributes()}
     * @param $rev - the mtime
     * @return null|MediaLink
     */
    public static function createFromCallStackArray($attributes, $rev = null): ?MediaLink
    {

        if (!is_array($attributes)) {
            // Debug for the key_exist below because of the following message:
            // `PHP Warning:  key_exists() expects parameter 2 to be array, array given`
            LogUtility::msg("The `attributes` parameter is not an array. Value ($attributes)", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
        }

        $tagAttributes = TagAttributes::createFromCallStackArray($attributes);

        $src = $attributes[self::DOKUWIKI_SRC];
        if ($src === null) {
            /**
             * Dokuwiki parse already the src and create the path and the attributes
             * The new model will not, we check if we are in the old mode
             */
            $src = $attributes[PagePath::PROPERTY_NAME];
            if ($src === null) {
                LogUtility::msg("src is mandatory for an image link and was not passed");
                return null;
            }
        }
        $dokuUrl = DokuwikiUrl::createFromUrl($src);
        $scheme = $dokuUrl->getScheme();
        switch ($scheme) {
            case DokuFs::SCHEME:
                $id = $dokuUrl->getPath();
                // the id is always absolute, except in a link
                // It may be relative, transform it as absolute
                global $ID;
                resolve_mediaid(getNS($ID), $id, $exists);
                $path = DokuPath::createMediaPathFromId($id, $rev);
                return self::createMediaLinkFromPath($path, $tagAttributes);
            case InterWikiPath::scheme:
                $path = InterWikiPath::create($dokuUrl->getPath());
                return self::createMediaLinkFromPath($path, $tagAttributes);
            case InternetPath::scheme:
                $path = InternetPath::create($dokuUrl->getPath());
                return self::createMediaLinkFromPath($path, $tagAttributes);
            default:
                LogUtility::msg("The media with the scheme ($scheme) are not yet supported. Media Source: $src");
                return null;

        }


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
        $dokuwikiUrl = DokuwikiUrl::createFromUrl($url);
        $parsedAttributes = $dokuwikiUrl->toArray();
        $path = $dokuwikiUrl->getPath();
        $linkingKey = $dokuwikiUrl->getQueryParameter(MediaLink::LINKING_KEY);
        if ($linkingKey === null) {
            $linkingKey = PluginUtility::getConfValue(self::CONF_DEFAULT_LINKING, self::LINKING_DIRECT_VALUE);
        }
        $parsedAttributes[MediaLink::LINKING_KEY] = $linkingKey;

        /**
         * Media Type
         */
        $scheme = $dokuwikiUrl->getScheme();
        if ($scheme === DokuFs::SCHEME) {
            $mediaType = MediaLink::INTERNAL_MEDIA_CALL_NAME;
        } else {
            $mediaType = MediaLink::EXTERNAL_MEDIA_CALL_NAME;
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
            TagAttributes::TITLE_KEY => $description,
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
    function setLazyLoad($false): MediaLink
    {
        $this->lazyLoad = $false;
        return $this;
    }

    public
    function getLazyLoad()
    {
        return $this->lazyLoad;
    }


    /**
     * Create a media link from a wiki id
     *
     *
     * @param $wikiId - dokuwiki id
     * @param TagAttributes|null $tagAttributes
     * @param string|null $rev
     * @return MediaLink
     */
    public
    static function createMediaLinkFromId($wikiId, ?string $rev = '', TagAttributes $tagAttributes = null)
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

        $dokuPath = DokuPath::createMediaPathFromId($wikiId, $rev);
        return self::createMediaLinkFromPath($dokuPath, $tagAttributes);

    }

    /**
     * @param Path $path
     * @param TagAttributes|null $tagAttributes
     * @return RasterImageLink|SvgImageLink|ThirdMediaLink
     */
    public static function createMediaLinkFromPath(Path $path, TagAttributes $tagAttributes = null)
    {

        if ($tagAttributes === null) {
            $tagAttributes = TagAttributes::createEmpty();
        }

        /**
         * Get and delete the attribute for the link
         * (The rest is for the image)
         */
        $lazyLoadMethod = $tagAttributes->getValueAndRemoveIfPresent(self::LAZY_LOAD_METHOD, self::LAZY_LOAD_METHOD_LOZAD_VALUE);
        $linking = $tagAttributes->getValueAndRemoveIfPresent(self::LINKING_KEY);
        $linkingClass = $tagAttributes->getValueAndRemoveIfPresent(syntax_plugin_combo_media::LINK_CLASS_ATTRIBUTE);

        /**
         * Processing
         */
        $mime = $path->getMime();
        if ($path->getExtension() === "svg") {
            /**
             * The mime type is set when uploading, not when
             * viewing.
             * Because they are internal image, the svg was already uploaded
             * Therefore, no authorization scheme here
             */
            $mime = Mime::create(Mime::SVG);
        }

        if ($mime === null) {
            $stringMime = self::UNKNOWN_MIME;
        } else {
            $stringMime = $mime->toString();
        }

        switch ($stringMime) {
            case self::UNKNOWN_MIME:
                LogUtility::msg("The mime type of the media ($path) is <a href=\"https://www.dokuwiki.org/mime\">unknown (not in the configuration file)</a>", LogUtility::LVL_MSG_ERROR);
                $media = new ImageRaster($path, $tagAttributes);
                $mediaLink = new RasterImageLink($media);
                break;
            case Mime::SVG:
                $media = new ImageSvg($path, $tagAttributes);
                $mediaLink = new SvgImageLink($media);
                break;
            default:
                if (!$mime->isImage()) {
                    LogUtility::msg("The type ($mime) of media ($path) is not an image", LogUtility::LVL_MSG_DEBUG, "image");
                    $media = new ThirdMedia($path, $tagAttributes);
                    $mediaLink = new ThirdMediaLink($media);
                } else {
                    $media = new ImageRaster($path, $tagAttributes);
                    $mediaLink = new RasterImageLink($media);
                }
                break;
        }

        $mediaLink
            ->setLazyLoadMethod($lazyLoadMethod)
            ->setLinking($linking)
            ->setLinkingClass($linkingClass);
        return $mediaLink;

    }

    public function setLazyLoadMethod(string $lazyLoadMethod): MediaLink
    {
        $this->lazyLoadMethod = $lazyLoadMethod;
        return $this;
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
    function toCallStackArray(): array
    {
        /**
         * Trying to stay inline with the dokuwiki key
         * We use the 'src' attributes as id
         *
         * src is a path (not an id)
         */
        $array = array(
            PagePath::PROPERTY_NAME => $this->getMedia()->getPath()->toString(),
            self::LINKING_KEY => $this->getLinking()
        );


        // Add the extra attribute
        return array_merge($this->getMedia()->getAttributes()->toCallStackArray(), $array);


    }


    public
    static function isInternalMediaSyntax($text)
    {
        return preg_match(' / ' . syntax_plugin_combo_media::MEDIA_PATTERN . ' / msSi', $text);
    }


    public
    function __toString()
    {
        $media = $this->getMedia();
        $dokuPath = $media->getPath();
        if ($dokuPath !== null) {
            return $dokuPath->getDokuwikiId();
        } else {
            return $media->__toString();
        }
    }


    private
    function getLinking()
    {
        return $this->linking;
    }

    private
    function setLinking($value): MediaLink
    {
        $this->linking = $value;
        return $this;
    }

    private
    function getLinkingClass()
    {
        return $this->linkingClass;
    }

    private
    function setLinkingClass($value): MediaLink
    {
        $this->linkingClass = $value;
        return $this;
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
        $mediaLink = TagAttributes::createEmpty();
        // https://www.dokuwiki.org/config:target
        global $conf;
        $target = $conf['target']['media'];
        $mediaLink->addOutputAttributeValueIfNotEmpty("target", $target);
        if (!empty($target)) {
            $mediaLink->addOutputAttributeValue("rel", 'noopener');
        }

        /**
         * Do we add a link to the image ?
         */
        $media = $this->getMedia();
        $dokuPath = $media->getPath();
        if (!($dokuPath instanceof DokuPath)) {
            LogUtility::msg("Media Link are only supported on media from the internal library ($media)", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            return "";
        }
        $linking = $this->getLinking();
        switch ($linking) {
            case self::LINKING_LINKONLY_VALUE: // show only a url
                $src = ml(
                    $dokuPath->getDokuwikiId(),
                    array(
                        'id' => $dokuPath->getDokuwikiId(),
                        'cache' => $media->getCache(),
                        'rev' => $dokuPath->getRevision()
                    )
                );
                $mediaLink->addOutputAttributeValue("href", $src);
                $title = $media->getTitle();
                if (empty($title)) {
                    $title = $media->getType();
                }
                return $mediaLink->toHtmlEnterTag("a") . $title . "</a>";
            case self::LINKING_NOLINK_VALUE:
                return $this->renderMediaTag();
            default:
            case self::LINKING_DIRECT_VALUE:
                //directly to the image
                $src = ml(
                    $dokuPath->getDokuwikiId(),
                    array(
                        'id' => $dokuPath->getDokuwikiId(),
                        'cache' => $media->getCache(),
                        'rev' => $dokuPath->getRevision()
                    ),
                    true
                );
                $mediaLink->addOutputAttributeValue("href", $src);
                $snippetId = "lightbox";
                $mediaLink->addClassName("{$snippetId}-combo");
                $linkingClass = $this->getLinkingClass();
                if ($linkingClass !== null) {
                    $mediaLink->addClassName($linkingClass);
                }
                $snippetManager = PluginUtility::getSnippetManager();
                $snippetManager->attachJavascriptComboLibrary();
                $snippetManager->attachInternalJavascriptForSlot("lightbox");
                $snippetManager->attachCssInternalStyleSheetForSlot("lightbox");
                return $mediaLink->toHtmlEnterTag("a") . $this->renderMediaTag() . "</a>";

            case self::LINKING_DETAILS_VALUE:
                //go to the details media viewer
                $src = ml(
                    $dokuPath->getDokuwikiId(),
                    array(
                        'id' => $dokuPath->getDokuwikiId(),
                        'cache' => $media->getCache(),
                        'rev' => $dokuPath->getRevision()
                    ),
                    false
                );
                $mediaLink->addOutputAttributeValue("href", $src);
                return $mediaLink->toHtmlEnterTag("a") .
                    $this->renderMediaTag() .
                    "</a>";

        }


    }


    /**
     * @return string - the HTML of the image
     */
    public

    abstract function renderMediaTag(): string;


    /**
     * The file
     * @return Media
     */
    public function getMedia(): Media
    {
        return $this->media;
    }

    protected function getLazyLoadMethod(): string
    {
        return $this->lazyLoadMethod;
    }


}
