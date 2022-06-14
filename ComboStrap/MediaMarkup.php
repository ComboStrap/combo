<?php


namespace ComboStrap;

use syntax_plugin_combo_media;

/**
 * Represents a media markup
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
    public const LAZY_LOAD_METHOD = "lazy-method";
    public const LAZY_LOAD_METHOD_HTML_VALUE = "html-attribute";
    public const LAZY_LOAD_METHOD_LOZAD_VALUE = "lozad";

    /**
     * This attributes does not apply
     * to a fetch (URL)
     * They are only for the tag (img, svg, ...)
     * or internal
     */
    public const NON_URL_ATTRIBUTES = [
        TagAttributes::TITLE_KEY,
        Hover::ON_HOVER_ATTRIBUTE,
        Animation::ON_VIEW_ATTRIBUTE,
        MediaMarkup::DOKUWIKI_SRC
    ];

    /**
     * An attribute to set the class of the link if any
     */
    public const LINK_CLASS_ATTRIBUTE = "link-class";


    private Url $fetchUrl;

    private string $externalOrInternalMedia;
    private ?string $align;
    private ?string $label;
    private string $ref;
    private ?string $linking;
    private ?string $lazyLoad;
    private TagAttributes $tagAttributes;
    private ?string $linkingClass;

    /**
     * Parse a media wiki ref that you can found in the first part of a media markup
     *
     * The parsing function {@link Doku_Handler_Parse_Media} has some flow / problem
     *    * It keeps the anchor only if there is no query string
     *    * It takes the first digit as the width (ie media.pdf?page=31 would have a width of 31)
     *    * `src` is not only the media path but may have a anchor
     * We parse it then
     *
     */
    public function setRef(string $ref): MediaMarkup
    {

        $this->ref = $ref;
        $this->fetchUrl = Url::createEmpty();

        $ref = trim($ref);

        /**
         * Easy case when the URL is just a conform URL
         */
        if (media_isexternal($ref)) {
            try {
                $this->fetchUrl = Url::createFromString($ref);
                $this->externalOrInternalMedia = self::EXTERNAL_MEDIA_CALL_NAME;
                return $this;
            } catch (ExceptionBadSyntax $e) {
                LogUtility::internalError("The url string is not valid URL ($ref)");
            }
        }


        /**
         * Path
         */
        $questionMarkPosition = strpos($ref, "?");
        $httpHostOrPath = $ref;
        $queryStringAndAnchorOriginal = null;
        if ($questionMarkPosition !== false) {
            $httpHostOrPath = substr($ref, 0, $questionMarkPosition);
            $queryStringAndAnchorOriginal = substr($ref, $questionMarkPosition + 1);
        } else {
            // We may have only an anchor
            $hashTagPosition = strpos($ref, "#");
            if ($hashTagPosition !== false) {
                $httpHostOrPath = substr($ref, 0, $hashTagPosition);
                $this->fetchUrl->setFragment(substr($ref, $hashTagPosition + 1));
            }
        }

        /**
         * Scheme
         */
        if (link_isinterwiki($httpHostOrPath)) {
            $this->externalOrInternalMedia = InterWikiPath::scheme;
            $this->fetchUrl->setPath($httpHostOrPath);
        } else {
            /**
             * We transform it as if it was a fetch URL
             */
            $this->externalOrInternalMedia = DokuFs::SCHEME;
            $this->fetchUrl->addQueryParameter(DokuPath::MEDIA_DRIVE, $httpHostOrPath);
        }


        /**
         * Parsing Query string if any
         */
        if ($queryStringAndAnchorOriginal !== null) {

            /**
             * The value $queryStringAndAnchorOriginal
             * is kept to create the original queryString
             * at the end if we found an anchor
             *
             * We parse token by token because we allow a hashtag for a hex color
             */
            $queryStringAndAnchorProcessing = $queryStringAndAnchorOriginal;
            while (strlen($queryStringAndAnchorProcessing) > 0) {

                /**
                 * Capture the token
                 * and reduce the text
                 */
                $questionMarkPos = strpos($queryStringAndAnchorProcessing, "&");
                if ($questionMarkPos !== false) {
                    $token = substr($queryStringAndAnchorProcessing, 0, $questionMarkPos);
                    $queryStringAndAnchorProcessing = substr($queryStringAndAnchorProcessing, $questionMarkPos + 1);
                } else {
                    $token = $queryStringAndAnchorProcessing;
                    $queryStringAndAnchorProcessing = "";
                }


                /**
                 * Sizing (wxh)
                 */
                $sizing = [];
                if (preg_match('/^([0-9]+)(?:x([0-9]+))?/', $token, $sizing)) {
                    $this->fetchUrl->addQueryParameter(Dimension::WIDTH_KEY, $sizing[1]);
                    if (isset($sizing[2])) {
                        $this->fetchUrl->addQueryParameter(Dimension::HEIGHT_KEY, $sizing[2]);
                    }
                    $token = substr($token, strlen($sizing[0]));
                    if ($token === "") {
                        // no anchor behind we continue
                        continue;
                    }
                }

                /**
                 * Linking
                 */
                $found = preg_match('/^(nolink|direct|linkonly|details)/i', $token, $matches);
                if ($found) {
                    $linkingValue = $matches[1];
                    $this->fetchUrl->addQueryParameter(self::LINKING_KEY, $linkingValue);
                    $token = substr($token, strlen($linkingValue));
                    if ($token == "") {
                        // no anchor behind we continue
                        continue;
                    }
                }

                /**
                 * Cache
                 */
                $noCacheValue = FetchAbs::NOCACHE_VALUE;
                $found = preg_match('/^(' . $noCacheValue . ')/i', $token, $matches);
                if ($found) {
                    $this->fetchUrl->addQueryParameter(FetchAbs::CACHE_KEY, $noCacheValue);
                    $token = substr($token, strlen($noCacheValue));
                    if ($token == "") {
                        // no anchor behind we continue
                        continue;
                    }
                }

                /**
                 * Anchor value after a single token case
                 */
                if (strpos($token, '#') === 0) {
                    $this->fetchUrl->setFragment(substr($token, 1));
                    continue;
                }

                /**
                 * Key, value
                 * explode to the first `=`
                 * in the anchor value, we can have one
                 *
                 * Ex with media.pdf#page=31
                 */
                list($key, $value) = explode("=", $token, 2);

                /**
                 * Case of an anchor after a boolean attribute (ie without =)
                 * at the end
                 */
                $anchorPosition = strpos($key, '#');
                if ($anchorPosition !== false) {
                    $this->fetchUrl->setFragment(substr($key, $anchorPosition + 1));
                    $key = substr($key, 0, $anchorPosition);
                }

                /**
                 * Test Anchor on the value
                 */
                if ($value != null) {
                    if (($countHashTag = substr_count($value, "#")) >= 3) {
                        LogUtility::msg("The value ($value) of the key ($key) for the link ($httpHostOrPath) has $countHashTag `#` characters and the maximum supported is 2.", LogUtility::LVL_MSG_ERROR);
                        continue;
                    }
                } else {
                    /**
                     * Boolean attribute
                     * (null does not make it)
                     */
                    $value = null;
                }

                $anchorPosition = false;
                $lowerCaseKey = strtolower($key);
                if ($lowerCaseKey === TextColor::CSS_ATTRIBUTE) {
                    /**
                     * Special case when color has one color value as hexadecimal #
                     * and the hashtag
                     */
                    if (strpos($value, '#') == 0) {
                        if (substr_count($value, "#") >= 2) {

                            /**
                             * The last one
                             */
                            $anchorPosition = strrpos($value, '#');
                        }
                        // no anchor then
                    } else {
                        // a color that is not hexadecimal can have an anchor
                        $anchorPosition = strpos($value, "#");
                    }
                } else {
                    // general case
                    $anchorPosition = strpos($value, "#");
                }
                if ($anchorPosition !== false) {
                    $this->fetchUrl->setFragment(substr($value, $anchorPosition + 1));
                    $value = substr($value, 0, $anchorPosition);
                }

                switch ($lowerCaseKey) {
                    case "w": // used in a link w=xxx
                        $this->fetchUrl->addQueryParameter(Dimension::WIDTH_KEY, $value);
                        break;
                    case "h": // used in a link h=xxxx
                        $this->fetchUrl->addQueryParameter(Dimension::HEIGHT_KEY, $value);
                        break;
                    default:
                        $this->fetchUrl->addQueryParameter($key, $value);
                        break;
                }

            }

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
            $this->lazyLoad = $this->fetchUrl->getQueryPropertyValueAndRemoveIfPresent(self::LAZY_LOAD_METHOD);
        } catch (ExceptionNotFound $e) {
            // ok
        }
        try {
            $this->linkingClass = $this->fetchUrl->getQueryPropertyValueAndRemoveIfPresent(self::LINK_CLASS_ATTRIBUTE);
        } catch (ExceptionNotFound $e) {
            // ok
        }

        foreach (self::NON_URL_ATTRIBUTES as $nonUrlAttribute) {
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
     * @throws ExceptionBadArgument
     */
    public static function createFromCallStackArray($callStackArray): MediaMarkup
    {
        $mediaMarkup = new MediaMarkup();

        $ref = $callStackArray[self::REF_ATTRIBUTE];
        if ($ref === null) {
            throw new ExceptionBadArgument("The media referece was not found in the callstack array", self::CANONICAL);
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

    /**
     * Compliance: src in dokuwiki is the path and the anchor if any
     */
    public function getSrc(): string
    {
        try {
            $src = $this->fetchUrl->getPath();
        } catch (ExceptionNotFound $e) {
            $src = "";
        }
        try {
            $src = "$src#{$this->fetchUrl->getFragment()}";
        } catch (ExceptionNotFound $e) {
            // ok
        }
        return $src;
    }

    /**
     * Media Type
     */
    public function getInternalExternalType(): string
    {
        return $this->externalOrInternalMedia;
    }


    public static function createFromRef(string $markupRef): MediaMarkup
    {
        return (new MediaMarkup())->setRef($markupRef);
    }


    /**
     * @return Url - an url that has query property as a fetch url
     * It permits to select the fetch class
     */
    public function toFetchUrl(): Url
    {
        return $this->fetchUrl;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getMime(): Mime
    {
        switch ($this->getInternalExternalType()) {
            case self::INTERNAL_MEDIA_CALL_NAME:
                $id = $this->fetchUrl->getQueryPropertyValue(FetchDoku::MEDIA_QUERY_PARAMETER);
                $path = DokuPath::createMediaPathFromId($id);
                break;
            default:
                $path = $this->fetchUrl;
                break;
        }
        return FileSystems::getMime($path);

    }

    /**
     * @param string $match - the match of the renderer
     * @throws ExceptionBadSyntax - if no ref was found
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
        $mediaMarkup->setRef($ref);
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
        $attributes[MediaMarkup::REF_ATTRIBUTE] = $this->ref;
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
    public function getLabel()
    {
        if ($this->label === null) {
            throw new ExceptionNotFound("No label specified");
        }
        return $this->label;
    }

    public
    function setLazyLoad($false): MediaMarkup
    {
        $this->lazyLoad = $false;
        return $this;
    }

    public
    function getLazyLoad()
    {

        if ($this->lazyLoad !== null) {
            return $this->lazyLoad;
        }

        return self::LAZY_LOAD_METHOD_LOZAD_VALUE;


    }

    public
    static function isInternalMediaSyntax($text)
    {
        return preg_match(' / ' . syntax_plugin_combo_media::MEDIA_PATTERN . ' / msSi', $text);
    }

    /**
     * Get and delete the attribute for the link
     * (The rest is for the image)
     */
    private
    function getLinkingClass()
    {
        return $this->linkingClass;
    }

    private
    function setLinkingClass($value): MediaMarkup
    {
        $this->linkingClass = $value;
        return $this;
    }




}
