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
use dokuwiki\Parsing\ParserMode\Internallink;
use syntax_plugin_combo_media;


/**
 * Class InternalMedia
 * Represent a markup link
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



    const CANONICAL = "image";

    /**
     * This attributes does not apply
     * to a fetch (URL)
     * They are only for the tag (img, svg, ...)
     * or internal
     */
    const NON_URL_ATTRIBUTES = [
        Align::ALIGN_ATTRIBUTE,
        MarkupUrl::LINKING_KEY,
        TagAttributes::TITLE_KEY,
        Hover::ON_HOVER_ATTRIBUTE,
        Animation::ON_VIEW_ATTRIBUTE,
        MarkupUrl::DOKUWIKI_SRC
    ];


    /**
     * @deprecated 2021-06-12
     */
    const LINK_PATTERN = "{{\s*([^|\s]*)\s*\|?.*}}";

    /**
     * The method to lazy load resources (Ie media)
     */
    const LAZY_LOAD_METHOD = "lazy-method";
    const LAZY_LOAD_METHOD_HTML_VALUE = "html-attribute";
    const LAZY_LOAD_METHOD_LOZAD_VALUE = "lozad";


    /**
     * @var string
     */
    private $lazyLoadMethod;

    private $lazyLoad = null;


    private $path;
    private $linking;
    private $linkingClass;
    private TagAttributes $attributes;


    /**
     * Image constructor.
     * @param Path $path
     *
     * Protected and not private
     * to allow cascading init
     * If private, the parent attributes are null
     * @throws ExceptionBadArgument - if the path cannot be fetched
     */
    protected function __construct(Path $path, $tagAttributes)
    {
        $this->path = DokuPath::createFromPath($path);
        $this->attributes = $tagAttributes;
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
        $tagAttributes->addComponentAttributeValue(Align::ALIGN_ATTRIBUTE, $align);
        $tagAttributes->addComponentAttributeValue(Dimension::WIDTH_KEY, $width);
        $tagAttributes->addComponentAttributeValue(Dimension::HEIGHT_KEY, $height);
        $tagAttributes->addComponentAttributeValue(FetchAbs::CACHE_KEY, $cache);
        $tagAttributes->addComponentAttributeValue(MarkupUrl::LINKING_KEY, $linking);

        return self::createMediaLinkFromId($src, $tagAttributes);

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
     * @param Url $url
     * @param TagAttributes|null $tagAttributes
     * @return RasterImageLink|SvgImageLink|ThirdMediaLink|MediaLink
     * @throws ExceptionBadArgument
     */
    public static function createMediaLinkFromPath(Url $url, TagAttributes $tagAttributes = null)
    {

        if ($tagAttributes === null) {
            $tagAttributes = TagAttributes::createEmpty();
        }

        /**
         * Processing
         */
        try {
            $mime = FileSystems::getMime($path);
            switch ($mime->toString()) {
                case Mime::SVG:
                    $mediaLink = new SvgImageLink($path, $tagAttributes);
                    break;
                default:
                    if (!$mime->isImage()) {
                        LogUtility::msg("The type ($mime) of media ($path) is not an image", LogUtility::LVL_MSG_DEBUG, "image");
                        $mediaLink = new ThirdMediaLink($path, $tagAttributes);
                    } else {
                        $mediaLink = new RasterImageLink($path, $tagAttributes);
                    }
                    break;
            }
        } catch (ExceptionNotFound $e) {
            // no mime
            LogUtility::msg("The mime type of the media ($path) is <a href=\"https://www.dokuwiki.org/mime\">unknown (not in the configuration file)</a>", LogUtility::LVL_MSG_ERROR);
            $mediaLink = new RasterImageLink($path);
        }

        /**
         * Get and delete the attribute for the link
         * (The rest is for the image)
         */
        try {
            $lazyLoadMethod = $url->getQueryPropertyValueAndRemoveIfPresent(self::LAZY_LOAD_METHOD);
        } catch (ExceptionNotFound $e) {
            $lazyLoadMethod = self::LAZY_LOAD_METHOD_LOZAD_VALUE;
        }
        $linking = $tagAttributes->getValueAndRemoveIfPresent(MarkupUrl::LINKING_KEY);
        $linkingClass = $tagAttributes->getValueAndRemoveIfPresent(syntax_plugin_combo_media::LINK_CLASS_ATTRIBUTE);
        return $mediaLink
            ->setLazyLoadMethod($lazyLoadMethod)
            ->setLinking($linking)
            ->setLinkingClass($linkingClass);


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
            PagePath::PROPERTY_NAME => $this->getPath()->toPathString(),
            MarkupUrl::LINKING_KEY => $this->getLinking()
        );


        // Add the extra attribute
        return array_merge($this->getAttributes()->toCallStackArray(), $array);


    }


    public
    static function isInternalMediaSyntax($text)
    {
        return preg_match(' / ' . syntax_plugin_combo_media::MEDIA_PATTERN . ' / msSi', $text);
    }


    public
    function __toString()
    {

        return $this->path->toUriString();

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
     * @throws ExceptionNotFound
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
        $media = $this->getPath();
        $dokuPath = $media->getPath();
        if (!($dokuPath instanceof DokuPath)) {
            LogUtility::msg("Media Link are only supported on media from the internal library ($media)", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            return "";
        }
        $linking = $this->getLinking();
        switch ($linking) {
            case MarkupUrl::LINKING_LINKONLY_VALUE: // show only a url
                $src = ml(
                    $dokuPath->getDokuwikiId(),
                    array(
                        'id' => $dokuPath->getDokuwikiId(),
                        'cache' => $media->getRequestedCache(),
                        'rev' => $dokuPath->getRevision()
                    )
                );
                $mediaLink->addOutputAttributeValue("href", $src);
                $title = $media->getTitle();
                if (empty($title)) {
                    $title = $media->getType();
                }
                return $mediaLink->toHtmlEnterTag("a") . $title . "</a>";
            case MarkupUrl::LINKING_NOLINK_VALUE:
                return $this->renderMediaTag();
            default:
            case MarkupUrl::LINKING_DIRECT_VALUE:
                //directly to the image
                $src = ml(
                    $dokuPath->getDokuwikiId(),
                    array(
                        'id' => $dokuPath->getDokuwikiId(),
                        'cache' => $media->getRequestedCache(),
                        'rev' => $dokuPath->getRevision()
                    ),
                    true
                );
                $mediaLink->addOutputAttributeValue("href", $src);
                $snippetId = "lightbox";
                $mediaLink->addClassName(StyleUtility::getStylingClassForTag($snippetId));
                $linkingClass = $this->getLinkingClass();
                if ($linkingClass !== null) {
                    $mediaLink->addClassName($linkingClass);
                }
                $snippetManager = PluginUtility::getSnippetManager();
                $snippetManager->attachJavascriptComboLibrary();
                $snippetManager->attachInternalJavascriptForSlot($snippetId);
                $snippetManager->attachCssInternalStyleSheetForSlot($snippetId);
                return $mediaLink->toHtmlEnterTag("a") . $this->renderMediaTag() . "</a>";

            case MarkupUrl::LINKING_DETAILS_VALUE:
                //go to the details media viewer
                $src = ml(
                    $dokuPath->getDokuwikiId(),
                    array(
                        'id' => $dokuPath->getDokuwikiId(),
                        'cache' => $media->getRequestedCache(),
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
    public abstract function renderMediaTag(): string;


    /**
     * The file
     * @return Path
     */
    public function getPath(): Path
    {
        return $this->path;
    }

    protected function getLazyLoadMethod(): string
    {
        return $this->lazyLoadMethod;
    }

    public function getTitle()
    {
        return $this->attributes->getValue(TagAttributes::TITLE_KEY);
    }

    public function getFetch(): Fetch
    {
        try {
            $mime = FileSystems::getMime($this->path);
        } catch (ExceptionNotFound $e) {
            return FetchDoku::createFromPath($this->path);
        }
        if ($mime->toString() === Mime::PDF) {
            return (new FetchPdf())
                ->setDokuPath($this->path);
        }
        return FetchDoku::createFromPath($this->path);

    }


}
