<?php


namespace ComboStrap;


use syntax_plugin_combo_media;

/**
 * This class represents a media markup:
 *   - with a {@link MediaMarkup::getFetcher() fetcher}
 *   - and {@link MediaMarkup::getExtraMediaTagAttributes() tag/styling attributes}
 *
 * You can create it:
 *   * via a {@link MediaMarkup::createFromRef() Markup Ref} (The string ref in the document)
 *   * via a {@link MediaMarkup::createFromFetcher() Fetcher}
 *   * via a {@link MediaMarkup::createFromFetchUrl() Fetch Url}
 *   * via a {@link MediaMarkup::createFromCallStackArray() callstack array} of {@link syntax_plugin_combo_media::render()}
 *   * via a {@link MediaMarkup::createFromMarkup() string match} of {@link syntax_plugin_combo_media::handle()}
 *
 *
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
        Shadow::SHADOW_ATT,
        Opacity::OPACITY_ATTRIBUTE
    ];

    /**
     * An attribute to set the class of the link if any
     */
    public const LINK_CLASS_ATTRIBUTE = "link-class";


    private ?string $align = null;
    private ?string $label = null;
    private ?MarkupRef $markupRef = null;
    private ?string $linking = null;
    private ?string $lazyLoadMethod = null;
    private TagAttributes $extraMediaTagAttributes;
    private ?string $linkingClass = null;
    private IFetcher $fetcher;
    private Url $fetchUrl;

    private function __construct()
    {
        $this->extraMediaTagAttributes = TagAttributes::createEmpty();
    }


    /**
     * Private method use {@link MediaMarkup::createFromRef()} to create a media markup via a ref
     *
     * Set and parse a media wiki ref that you can found in the first part of a media markup
     *
     * @param string $markupRef
     * @return MediaMarkup
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotFound
     */
    private function setMarkupRef(string $markupRef): MediaMarkup
    {

        $markupRef = trim($markupRef);
        $this->markupRef = MarkupRef::createMediaFromRef($markupRef);

        $refUrl = $this->markupRef->getUrl();
        $this->setUrl($refUrl);

        return $this;
    }

    /**
     * @param $callStackArray
     * @return MediaMarkup
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotFound
     * @throws ExceptionNotExists
     */
    public static function createFromCallStackArray($callStackArray): MediaMarkup
    {

        $tagAttributes = TagAttributes::createFromCallStackArray($callStackArray);
        $ref = $tagAttributes->getValueAndRemoveIfPresent(MarkupRef::REF_ATTRIBUTE);
        if ($ref === null) {
            $ref = $tagAttributes->getValueAndRemoveIfPresent(MediaMarkup::DOKUWIKI_SRC);
            if ($ref === null) {
                throw new ExceptionBadArgument("The media reference was not found in the callstack array", self::CANONICAL);
            }
        }
        return self::createFromRef($ref)
            ->buildFromTagAttributes($tagAttributes);


    }

    /**
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     * @throws ExceptionNotFound
     * @throws ExceptionInternal
     */
    public static function createFromFetchUrl(Url $fetchUrl): MediaMarkup
    {
        return (new MediaMarkup())->setUrl($fetchUrl);
    }

    public static function createFromFetcher(IFetcher $fetcher): MediaMarkup
    {
        return (new MediaMarkup())
            ->setFetcher($fetcher);
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
                $src = $this->getPath()->getWikiId();
                try {
                    $src = "$src#{$this->markupRef->getUrl()->getFragment()}";
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
            $this->markupRef->getPath();
            return self::INTERNAL_MEDIA_CALL_NAME;
        } catch (ExceptionNotFound $e) {
            return self::EXTERNAL_MEDIA_CALL_NAME;
        }

    }


    /**
     * @throws ExceptionBadSyntax
     * @throws ExceptionBadArgument
     * @throws ExceptionNotFound
     */
    public static function createFromRef(string $markupRef): MediaMarkup
    {
        return (new MediaMarkup())->setMarkupRef($markupRef);
    }


    /**
     * @return Url - an url that has query property as a fetch url
     * It permits to select the fetch class
     * @deprecated use {@link MediaMarkup::getFetcher()}->getUrl instead
     */
    public function getFetchUrl(): Url
    {
        return $this->getFetcher()->getFetchUrl();
    }


    /**
     * @param string $match - the match of the renderer
     * @throws ExceptionBadSyntax - if no ref was found
     * @throws ExceptionBadArgument
     * @throws ExceptionNotFound|ExceptionNotExists
     * @throws ExceptionInternal
     */
    public static function createFromMarkup(string $match): MediaMarkup
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
        $mediaMarkup->setMarkupRef($ref);
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
            ->setMarkupRef($ref)
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
        $attributes[MediaMarkup::LINKING_KEY] = $this->linking;
        $attributes[MarkupRef::REF_ATTRIBUTE] = $this->markupRef->getRef();
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
    public function getLinking(): string
    {
        /**
         * Linking
         */
        $linking = $this->linking;
        if ($linking !== null) {
            return $linking;
        }
        throw new ExceptionNotFound("No linking set");


    }

    /**
     * Align on the url has precedence
     * if present
     * @throws ExceptionNotFound
     */
    public function getAlign(): string
    {

        if ($this->align !== null) {
            return $this->align;
        }
        throw new ExceptionNotFound("No align was specified");
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
                $this->extraMediaTagAttributes->addComponentAttributeValue(FloatAttribute::FLOAT_KEY, "right");
            } else {
                $this->extraMediaTagAttributes->addComponentAttributeValue(Align::ALIGN_ATTRIBUTE, $align);
            }
        } catch (ExceptionNotFound $e) {
            // ok
        }

        return $this->extraMediaTagAttributes;

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

    public function getExtraMediaTagAttributes(): TagAttributes
    {
        try {
            $this->extraMediaTagAttributes->addComponentAttributeValue(Align::ALIGN_ATTRIBUTE, $this->getAlign());
        } catch (ExceptionNotFound $e) {
            // ok
        }
        return $this->extraMediaTagAttributes;
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
        if ($this->markupRef === null) {
            throw new ExceptionNotFound("No markup, this media markup was not created from a markup");
        }
        return $this->markupRef;
    }


    /**
     * @param TagAttributes $tagAttributes - the attributes in a tag format
     * @return $this
     */
    public function buildFromTagAttributes(TagAttributes $tagAttributes): MediaMarkup
    {

        $linking = $tagAttributes->getValueAndRemoveIfPresent(self::LINKING_KEY);
        if ($linking !== null) {
            $this->setLinking($linking);
        }
        $label = $tagAttributes->getValueAndRemoveIfPresent(TagAttributes::TITLE_KEY);
        if ($label !== null) {
            $this->setLabel($label);
        }
        $align = $tagAttributes->getValueAndRemoveIfPresent(Align::ALIGN_ATTRIBUTE);
        if ($align !== null) {
            $this->setAlign($align);
        }
        $lazy = $tagAttributes->getValueAndRemoveIfPresent(self::LAZY_LOAD_METHOD);
        if ($lazy !== null) {
            $this->setLazyLoadMethod($lazy);
        }

        /**
         * dokuwiki attribute
         */
        if (isset($this->fetchUrl)) {
            $width = $tagAttributes->getValueAndRemoveIfPresent(Dimension::WIDTH_KEY);
            if ($width !== null) {
                $this->fetchUrl->addQueryParameterIfNotPresent(Dimension::WIDTH_KEY, $width);
            }
            $height = $tagAttributes->getValueAndRemoveIfPresent(Dimension::HEIGHT_KEY);
            if ($height !== null) {
                $this->fetchUrl->addQueryParameterIfNotPresent(Dimension::HEIGHT_KEY, $height);
            }
            $ratio = $tagAttributes->getValueAndRemoveIfPresent(Dimension::RATIO_ATTRIBUTE);
            if ($ratio !== null) {
                $this->fetchUrl->addQueryParameterIfNotPresent(Dimension::RATIO_ATTRIBUTE, $ratio);
            }
        }

        foreach ($tagAttributes->getComponentAttributes() as $key => $value) {
            $this->extraMediaTagAttributes->addComponentAttributeValue($key, $value);
        }

        foreach ($tagAttributes->getStyleDeclarations() as $key => $value){
            $this->extraMediaTagAttributes->addStyleDeclarationIfNotSet($key, $value);
        }

        return $this;
    }

    /**
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     * @throws ExceptionNotFound
     */
    public function toHtml(): string
    {
        return MediaLink::createFromMediaMarkup($this)
            ->renderMediaTag();
    }

    /**
     * @throws ExceptionBadSyntax
     * @throws ExceptionBadArgument
     * @throws ExceptionNotExists
     * @throws ExceptionNotFound
     */
    public function getMediaLink()
    {
        return MediaLink::createFromMediaMarkup($this);
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

    /**
     *
     * Private method use {@link MediaMarkup::createFromFetchUrl()} to create a media markup via a Url
     *
     * @throws ExceptionNotFound
     */
    private function setUrl(Url $fetchUrl): MediaMarkup
    {

        /**
         * Tag Attributes
         */
        try {
            $this->align = $fetchUrl->getQueryPropertyValueAndRemoveIfPresent(Align::ALIGN_ATTRIBUTE);
        } catch (ExceptionNotFound $e) {
            // ok
        }
        try {
            $this->linking = $fetchUrl->getQueryPropertyValueAndRemoveIfPresent(self::LINKING_KEY);
        } catch (ExceptionNotFound $e) {
            // ok
        }
        try {
            $this->lazyLoadMethod = $fetchUrl->getQueryPropertyValueAndRemoveIfPresent(self::LAZY_LOAD_METHOD);
        } catch (ExceptionNotFound $e) {
            // ok
        }
        try {
            $this->linkingClass = $fetchUrl->getQueryPropertyValueAndRemoveIfPresent(self::LINK_CLASS_ATTRIBUTE);
        } catch (ExceptionNotFound $e) {
            // ok
        }

        foreach (self::STYLE_ATTRIBUTES as $nonUrlAttribute) {
            try {
                $value = $fetchUrl->getQueryPropertyValueAndRemoveIfPresent($nonUrlAttribute);
                $this->extraMediaTagAttributes->addComponentAttributeValue($nonUrlAttribute, $value);
            } catch (ExceptionNotFound $e) {
                // ok
            }
        }

        $this->fetchUrl = $fetchUrl;
        return $this;
    }

    /**
     * @throws ExceptionNotFound
     */
    private function getRef(): string
    {
        if ($this->markupRef === null) {
            throw new ExceptionNotFound("No ref was specified");
        }
        return $this->markupRef->getRef();
    }

    /**
     * @throws ExceptionNotFound - if this markup does not have a path origin
     * @deprecated A media may be generated (ie {@link FetcherVignette}
     * therefore the path may be not present
     */
    public function getPath(): WikiPath
    {
        try {

            return $this->getMarkupRef()->getPath();

        } catch (ExceptionNotFound $e) {

            if ($this->fetcher instanceof IFetcherSource) {
                return $this->fetcher->getSourcePath();
            }
            throw $e;

        }
    }

    /**
     * Private method use {@link MediaMarkup::createFromFetcher()} to create a media markup via a Fetcher
     * @param IFetcher $fetcher
     * @return MediaMarkup
     */
    private function setFetcher(IFetcher $fetcher): MediaMarkup
    {
        $this->fetcher = $fetcher;
        return $this;
    }


    /**
     * @return IFetcher
     * @throws ExceptionBadArgument
     * @throws ExceptionInternal
     * @throws ExceptionNotFound
     */
    public function getFetcher(): IFetcher
    {
        if (!isset($this->fetcher)) {
            if (!isset($this->fetchUrl)) {
                throw new ExceptionRuntimeInternal("No fetcher or url was set");
            }
            /**
             * Fetcher is build later
             * because for a raster image
             * actually, we can't built it
             * if the file does not exists.
             * It will throw an error immediatly and we may want not.
             * For resources, we want to build the url even if the image does not exists.
             */
            try {
                $this->fetcher = FetcherSystem::createPathFetcherFromUrl($this->fetchUrl);
            } catch (ExceptionBadArgument|ExceptionInternal|ExceptionNotFound $e) {
                // we don't support http fetch
                if (!($this->getMarkupRef()->getSchemeType() === MarkupRef::WEB_URI)) {
                    throw $e;
                }
            }
        }
        return $this->fetcher;
    }


}
