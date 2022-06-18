<?php


namespace ComboStrap;

use syntax_plugin_combo_media;

/**
 * Represents a media markup
 *
 * Wrapper around {@link Doku_Handler_Parse_Media}
 *
 *
 *
 * Not that for dokuwiki the `type` key of the attributes is the `call`
 * and therefore determine the function in an render
 * (ie {@link \Doku_Renderer::internalmedialink()} or {@link \Doku_Renderer::externalmedialink()}
 */
class MediaMarkup
{

    /**
     * The dokuwiki type and mode name
     * (ie call)
     *  * ie {@link MediaMarkup::EXTERNAL_MEDIA_CALL_NAME}
     *  or {@link MediaMarkup::INTERNAL_MEDIA_CALL_NAME}
     *
     * The dokuwiki type (internalmedia/externalmedia)
     *
     */
    public const MEDIA_DOKUWIKI_TYPE = 'dokuwiki_media_type';
    public const EXTERNAL_MEDIA_CALL_NAME = "externalmedia";
    public const INTERNAL_MEDIA_CALL_NAME = "internalmedia";

    /**
     * Link value:
     *   * 'nolink'
     *   * 'direct': directly to the image
     *   * 'linkonly': show only a url
     *   * 'details': go to the details media viewer
     *
     * @var
     */
    public const LINKING_KEY = 'linking';
    public const LINKING_DETAILS_VALUE = 'details';
    public const LINKING_DIRECT_VALUE = 'direct';
    /**
     * Only used by Dokuwiki
     * Contains the path and eventually an anchor
     * never query parameters
     */
    public const DOKUWIKI_SRC = "src";
    public const LINKING_LINKONLY_VALUE = "linkonly";
    public const LINKING_NOLINK_VALUE = 'nolink';
    /**
     * Default image linking value
     */
    public const CONF_DEFAULT_LINKING = "defaultImageLinking";

    const REF_ATTRIBUTE = "ref";
    const CANONICAL = "media";

    /**
     * The method on how to lazy load resources (Ie media)
     */
    public const LAZY_LOAD_METHOD = "lazy";
    public const LAZY_LOAD_METHOD_HTML_VALUE = "html-attribute";
    public const LAZY_LOAD_METHOD_LOZAD_VALUE = "lozad";
    public const LAZY_LOAD_METHOD_NONE_VALUE = "none";
    const LAZY_LOAD_METHOD_DEFAULT = self::LAZY_LOAD_METHOD_LOZAD_VALUE;

    /**
     * This attributes does not apply
     * to a fetch (URL)
     * They are only for the tag (img, svg, ...)
     * or internal
     */
    public const STYLE_ATTRIBUTES = [
        TagAttributes::TITLE_KEY,
        Hover::ON_HOVER_ATTRIBUTE,
        Animation::ON_VIEW_ATTRIBUTE,
        Shadow::SHADOW_ATT
    ];

    /**
     * An attribute to set the class of the link if any
     */
    public const LINK_CLASS_ATTRIBUTE = "link-class";


    private Url $fetchUrl;


    private ?string $align = null;
    private ?string $label = null;
    private ?MarkupRef $ref;
    private ?string $linking = null;
    private ?string $lazyLoadMethod = null;
    private TagAttributes $tagAttributes;
    private ?string $linkingClass = null;

    /**
     * Parse a media wiki ref that you can found in the first part of a media markup
     *
     * The parsing function {@link Doku_Handler_Parse_Media} has some flow / problem
     *    * It keeps the anchor only if there is no query string
     *    * It takes the first digit as the width (ie media.pdf?page=31 would have a width of 31)
     *    * `src` is not only the media path but may have a anchor
     * We parse it then
     *
     * @param string $ref
     * @return MediaMarkup
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotFound
     */
    public function setRef(string $ref): MediaMarkup
    {

        $this->ref = MarkupRef::createMediaFromRef($ref);

        $this->fetchUrl = $this->ref->getUrl();

        try {
            $this->fetchUrl->addQueryParameterIfNotActualSameValue(FetchRaw::MEDIA_QUERY_PARAMETER, $this->ref->getPath()->getDokuwikiId());
        } catch (ExceptionNotFound $e) {
            // no path
        }

        /**
         * Tag Attributes
         */
        $this->tagAttributes = TagAttributes::createEmpty();
        try {
            $this->align = $this->fetchUrl->getQueryPropertyValueAndRemoveIfPresent(Align::ALIGN_ATTRIBUTE);
        } catch (ExceptionNotFound $e) {
            // ok
        }
        try {
            $this->linking = $this->fetchUrl->getQueryPropertyValueAndRemoveIfPresent(self::LINKING_KEY);
        } catch (ExceptionNotFound $e) {
            // ok
        }
        try {
            $this->lazyLoadMethod = $this->fetchUrl->getQueryPropertyValueAndRemoveIfPresent(self::LAZY_LOAD_METHOD);
        } catch (ExceptionNotFound $e) {
            // ok
        }
        try {
            $this->linkingClass = $this->fetchUrl->getQueryPropertyValueAndRemoveIfPresent(self::LINK_CLASS_ATTRIBUTE);
        } catch (ExceptionNotFound $e) {
            // ok
        }

        foreach (self::STYLE_ATTRIBUTES as $nonUrlAttribute) {
            try {
                $value = $this->fetchUrl->getQueryPropertyValueAndRemoveIfPresent($nonUrlAttribute);
                $this->tagAttributes->addComponentAttributeValue($nonUrlAttribute, $value);
            } catch (ExceptionNotFound $e) {
                // ok
            }
        }

        return $this;
    }

    /**
     * @param $callStackArray
     * @return MediaMarkup
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotFound
     */
    public static function createFromCallStackArray($callStackArray): MediaMarkup
    {
        $mediaMarkup = new MediaMarkup();

        $ref = $callStackArray[self::REF_ATTRIBUTE];
        if ($ref === null) {
            $ref = $callStackArray[MediaMarkup::DOKUWIKI_SRC];
            if ($ref === null) {
                throw new ExceptionBadArgument("The media reference was not found in the callstack array", self::CANONICAL);
            }
        }
        $mediaMarkup->setRef($ref);

        $linking = $callStackArray[self::LINKING_KEY];
        if ($linking !== null) {
            $mediaMarkup->setLinking($linking);
        }
        $label = $callStackArray[TagAttributes::TITLE_KEY];
        if ($label !== null) {
            $mediaMarkup->setLabel($label);
        }
        $align = $callStackArray[Align::ALIGN_ATTRIBUTE];
        if ($align !== null) {
            $mediaMarkup->setAlign($align);
        }

        return $mediaMarkup;


    }

    public static function createFromUrl(Url $getFetchUrl)
    {
        return (new MediaMarkup())->setUrl($getFetchUrl);
    }

    /**
     * Compliance: src in dokuwiki is the id and the anchor if any
     * Dokuwiki does not understand other property and the reference metadata
     * may not work if we send back the `ref`
     * @throws ExceptionNotFound
     */
    public function getSrc(): string
    {
        $internalExternalType = $this->getInternalExternalType();
        switch ($internalExternalType) {
            case MediaMarkup::INTERNAL_MEDIA_CALL_NAME:
                $src = $this->getPath()->getDokuWikiId();
                try {
                    $src = "$src#{$this->fetchUrl->getFragment()}";
                } catch (ExceptionNotFound $e) {
                    // ok
                }
                return $src;
            case MediaMarkup::EXTERNAL_MEDIA_CALL_NAME:
                return $this->getMarkupRef()->getRef();
            default:
                LogUtility::internalError("The internal/external type value ($internalExternalType) is unknown");
                return $this->getMarkupRef()->getRef();
        }

    }

    /**
     * Media Type Needed by Dokuwiki
     */
    public function getInternalExternalType(): string
    {
        try {
            // if there is a path, this is internal
            // if interwiki this, wiki id, ...
            $this->ref->getPath();
            return self::INTERNAL_MEDIA_CALL_NAME;
        } catch (ExceptionNotFound $e) {
            return self::EXTERNAL_MEDIA_CALL_NAME;
        }

    }


    public static function createFromRef(string $markupRef): MediaMarkup
    {
        return (new MediaMarkup())->setRef($markupRef);
    }


    /**
     * @return Url - an url that has query property as a fetch url
     * It permits to select the fetch class
     */
    public function getFetchUrl(): Url
    {
        return $this->fetchUrl;
    }


    /**
     * @param string $match - the match of the renderer
     * @throws ExceptionBadSyntax - if no ref was found
     * @throws ExceptionBadArgument
     * @throws ExceptionNotFound
     */
    public static function createFromMatch(string $match): MediaMarkup
    {

        $mediaMarkup = new MediaMarkup();

        /**
         *   * Delete the opening and closing character
         *   * create the url and description
         */
        $match = preg_replace(array('/^{{/', '/}}$/u'), '', $match);
        $parts = explode('|', $match, 2);

        $ref = $parts[0];
        if ($ref === null) {
            throw new ExceptionBadSyntax("No ref was found");
        }
        $mediaMarkup->setRef(trim($ref));
        if (isset($parts[1])) {
            $mediaMarkup->setLabel($parts[1]);
        }


        /**
         * Media Alignment
         */

        $rightAlign = (bool)preg_match('/^ /', $ref);
        $leftAlign = (bool)preg_match('/ $/', $ref);
        $align = null;
        // Logic = what's that ;)...
        if ($leftAlign & $rightAlign) {
            $align = 'center';
        } else if ($rightAlign) {
            $align = 'right';
        } else if ($leftAlign) {
            $align = 'left';
        }
        if ($align !== null) {
            $mediaMarkup->setAlign($align);
        }

        return $mediaMarkup;


    }

    public function setAlign(string $align): MediaMarkup
    {
        $this->align = $align;
        return $this;
    }

    public function setLabel(string $label): MediaMarkup
    {
        $this->label = $label;
        return $this;
    }

    /**
     * just FYI, not used
     *
     * Create an image from dokuwiki {@link Internallink internal call media attributes}
     *
     * Dokuwiki extracts already the width, height and align property
     * @param array $callAttributes
     * @return MediaMarkup
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

        $ref = "$src?{$width}x$height&$cache";
        return (new MediaMarkup())
            ->setRef($ref)
            ->setAlign($align)
            ->setLabel($title)
            ->setLinking($linking);

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
     * Return the array that is used in the {@link CallStack}
     *
     * @return array of key string and value
     */
    public function toCallStackArray(): array
    {
        /**
         * We store linking as attribute (to make it possible to change the linking by other plugin)
         * (ie no linking in heading , ...)
         */
        $attributes[MediaMarkup::LINKING_KEY] = null;
        $attributes[MediaMarkup::REF_ATTRIBUTE] = $this->ref->getRef();
        $attributes[Align::ALIGN_ATTRIBUTE] = $this->align;
        $attributes[TagAttributes::TITLE_KEY] = $this->label;
        return $attributes;

    }

    public function setLinking(string $linking): MediaMarkup
    {
        $this->linking = $linking;
        return $this;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getLinking()
    {
        /**
         * Linking
         */
        $linking = $this->linking;
        if ($linking !== null) {
            return $linking;
        }

        return $this->fetchUrl->getQueryPropertyValueAndRemoveIfPresent(MediaMarkup::LINKING_KEY);


    }

    /**
     * Align on the url has precedence
     * if present
     * @throws ExceptionNotFound
     */
    public function getAlign()
    {

        try {
            return $this->fetchUrl->getQueryPropertyValueAndRemoveIfPresent(Align::ALIGN_ATTRIBUTE);
        } catch (ExceptionNotFound $e) {
            if ($this->align !== null) {
                return $this->align;
            }
            throw new ExceptionNotFound("No align was specified");
        }
    }


    public function toTagAttributes()
    {


        /**
         * The align attribute on an image parse
         * is a float right
         * ComboStrap does a difference between a block right and a float right
         */
        try {
            $align = $this->getAlign();
            if ($align === "right") {
                $this->tagAttributes->addComponentAttributeValue(FloatAttribute::FLOAT_KEY, "right");
            } else {
                $this->tagAttributes->addComponentAttributeValue(Align::ALIGN_ATTRIBUTE, $align);
            }
        } catch (ExceptionNotFound $e) {
            // ok
        }

        return $this->tagAttributes;

    }

    /**
     * @throws ExceptionNotFound
     */
    public function getLabel(): string
    {
        if ($this->label === null) {
            throw new ExceptionNotFound("No label specified");
        }
        return $this->label;
    }

    public
    function setLazyLoadMethod($false): MediaMarkup
    {
        $this->lazyLoadMethod = $false;
        return $this;
    }

    /**
     * @throws ExceptionNotFound
     */
    public
    function getLazyLoadMethod(): string
    {

        if ($this->lazyLoadMethod !== null) {
            return $this->lazyLoadMethod;
        }
        throw new ExceptionNotFound("Lazy method is not specified");

    }

    public
    function getLazyLoadMethodOrDefault(): string
    {
        try {
            return $this->getLazyLoadMethod();
        } catch (ExceptionNotFound $e) {
            return self::LAZY_LOAD_METHOD_LOZAD_VALUE;
        }

    }


    public
    static function isInternalMediaSyntax($text)
    {
        return preg_match(' / ' . syntax_plugin_combo_media::MEDIA_PATTERN . ' / msSi', $text);
    }

    /**
     * @throws ExceptionNotFound
     */
    public function isLazy(): bool
    {

        return $this->getLazyLoadMethod() !== self::LAZY_LOAD_METHOD_NONE_VALUE;

    }

    public function getAttributes(): TagAttributes
    {
        try {
            $this->tagAttributes->addComponentAttributeValue(Align::ALIGN_ATTRIBUTE, $this->getAlign());
        } catch (ExceptionNotFound $e) {
            // ok
        }
        return $this->tagAttributes;
    }


    public function __toString()
    {
        return $this->toMarkupSyntax();
    }

    public function setLazyLoad(bool $true): MediaMarkup
    {
        if ($true) {
            $this->lazyLoadMethod = self::LAZY_LOAD_METHOD_DEFAULT;
        } else {
            $this->lazyLoadMethod = self::LAZY_LOAD_METHOD_NONE_VALUE;
        }
        return $this;
    }

    /**
     * Get and delete the attribute for the link
     * (The rest is for the image)
     */
    public
    function getLinkingClass()
    {
        return $this->linkingClass;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getMarkupRef(): MarkupRef
    {
        if ($this->ref === null) {
            throw new ExceptionNotFound("No markup, this media markup was not created from a markup");
        }
        return $this->ref;
    }

    private
    function setLinkingClass($value): MediaMarkup
    {
        $this->linkingClass = $value;
        return $this;
    }

    /**
     * @return string the wiki syntax
     */
    public function toMarkupSyntax(): string
    {
        $descriptionPart = "";
        try {
            $descriptionPart = "|" . $this->getLabel();
        } catch (ExceptionNotFound $e) {
            // ok
        }
        try {
            $ref = $this->getRef();
        } catch (ExceptionNotFound $e) {
            $ref = $this->getFetchUrl()->toString();
        }
        return '{{' . $ref . $descriptionPart . '}}';
    }

    private function setUrl(Url $fetchUrl): MediaMarkup
    {
        $this->fetchUrl = $fetchUrl;
        return $this;
    }

    /**
     * @throws ExceptionNotFound
     */
    private function getRef(): string
    {
        if ($this->ref === null) {
            throw new ExceptionNotFound("No ref was specified");
        }
        return $this->ref->getRef();
    }

    /**
     * @throws ExceptionNotFound - if this is an external image
     */
    public function getPath(): DokuPath
    {
        try {
            return $this->getMarkupRef()->getPath();
        } catch (ExceptionNotFound $e) {

            try {
                return FetchRaw::createEmpty()
                    ->buildFromUrl($this->getFetchUrl())
                    ->getOriginalPath();
            } catch (ExceptionBadArgument $e) {
                throw new ExceptionNotFound("No path in the markup or in the url were found");
            }


        }
    }


}
