<?php

namespace ComboStrap;

use syntax_plugin_combo_variable;

/**
 *
 * Basically, a class that parse a link/media markup reference and returns an URL.
 *
 * Detailed, the class parses the reference:
 *   * from a {@link MarkupRef::createMediaFromRef() media markup}
 *   * or {@link MarkupRef::createLinkFromRef() link markup}
 * and returns an {@link MarkupRef::getUrl() URL},
 *
 * You may determine the {@link MarkupRef::getSchemeType() type of reference}
 *
 * For a {@link MarkupRef::WIKI_URI}, the URL returned is:
 *   * a {@link UrlEndpoint::createFetchUrl() fetch url} for a media
 *   * a {@link UrlEndpoint::createDokuUrl() doku url} for a link (ie page)
 *
 * If this is a {@link MarkupRef::INTERWIKI_URI}, you may also get the {@link MarkupRef::getInterWiki() interwiki instance}
 * If this is a {@link MarkupRef::WIKI_URI}, you may also get the {@link MarkupRef::getPath() path}
 *
 *
 * Why ?
 * The parsing function {@link Doku_Handler_Parse_Media} has some flow / problem
 *    * It keeps the anchor only if there is no query string
 *    * It takes the first digit as the width (ie media.pdf?page=31 would have a width of 31)
 *    * `src` is not only the media path but may have a anchor
 *    * ...
 *
 */
class MarkupRef
{
    public const WINDOWS_SHARE_URI = 'windowsShare';
    public const LOCAL_URI = 'local';
    public const EMAIL_URI = 'email';
    public const WEB_URI = 'external';

    /**
     * Type of link
     */
    public const INTERWIKI_URI = 'interwiki';
    public const WIKI_URI = 'internal';
    public const VARIABLE_URI = 'internal_template';
    private static ?array $authorizedSchemes = null;

    /**
     * The type of markup ref (ie media or link)
     */
    private string $type;
    const MEDIA_TYPE = "media";
    const LINK_TYPE = "link";

    private string $refScheme;
    public const EXTERNAL_MEDIA_CALL_NAME = "external";


    private string $ref;
    private ?Url $url = null;

    private ?DokuPath $path = null;
    private ?InterWiki $interWiki = null;


    /**
     * @throws ExceptionBadSyntax
     * @throws ExceptionBadArgument
     * @throws ExceptionNotFound
     */
    public function __construct($ref, $type)
    {
        $this->ref = $ref;
        $this->type = $type;

        $this->url = Url::createEmpty();

        $ref = trim($ref);

        /**
         * Email validation pattern
         * E-Mail (pattern below is defined in inc/mail.php)
         *
         * Example:
         * [[support@combostrap.com?subject=hallo world]]
         * [[support@combostrap.com]]
         */
        $emailRfc2822 = "0-9a-zA-Z!#$%&'*+/=?^_`{|}~-";
        $emailPattern = '[' . $emailRfc2822 . ']+(?:\.[' . $emailRfc2822 . ']+)*@(?i:[0-9a-z][0-9a-z-]*\.)+(?i:[a-z]{2,63})';
        if (preg_match('<' . $emailPattern . '>', $ref)) {
            $this->refScheme = self::EMAIL_URI;
            $position = strpos($ref, "?");

            if ($position !== false) {
                $email = substr($ref, 0, $position);
                $queryStringAndFragment = substr($ref, $position + 1);
                $this->url = Url::createFromString("mailto:$email");
                $this->parseAndAddQueryStringAndFragment($queryStringAndFragment);
            } else {
                $this->url = Url::createFromString("mailto:$ref");
            }
            return;
        }

        /**
         * Case when the URL is just a full conform URL
         *
         * Example: `https://` or `ftp://`
         *
         * Other scheme are not yet recognized
         * because it can also be a wiki id
         * For instance, `mailto:` is also a valid page
         *
         * same as {@link media_isexternal()}  check only http / ftp scheme
         */
        if (preg_match('#^([a-z0-9\-.+]+?)://#i', $ref)) {
            try {
                $this->url = Url::createFromString($ref);
                $this->refScheme = self::WEB_URI;

                /**
                 * Authorized scheme only (to not inject code ?)
                 */
                $authorizedSchemes = self::loadAndGetAuthorizedSchemes();
                if (!in_array($this->url->getScheme(), $authorizedSchemes)) {
                    throw new ExceptionBadSyntax("The scheme ({$this->url->getScheme()}) of the URL ({$this->url}) is not authorized");
                }
                return;
            } catch (ExceptionBadSyntax $e) {
                throw new ExceptionBadSyntax("The url string was not validated as an URL ($ref). Error: {$e->getMessage()}");
            }
        }

        /**
         * Windows share link
         */
        if (preg_match('/^\\\\\\\\[^\\\\]+?\\\\/u', $ref)) {
            $this->refScheme = self::WINDOWS_SHARE_URI;
            $this->url = LocalPath::createFromPath($ref)->getUrl();
            return;
        }

        /**
         * Only Fragment (also known as local link)
         */
        if (preg_match('/^#.?/', $ref)) {
            $this->refScheme = self::LOCAL_URI;

            $fragment = substr($ref, 1);
            if ($fragment !== "") {
                $fragment = OutlineSection::textToHtmlSectionId($fragment);
            }
            $this->url = Url::createEmpty()->setFragment($fragment);
            $this->path = DokuPath::createPagePathFromRequestedPage();
            return;
        }

        /**
         * Interwiki ?
         */
        if (preg_match('/^[a-zA-Z0-9.]+>/u', $ref)) {

            $this->refScheme = MarkupRef::INTERWIKI_URI;
            switch ($type) {
                case self::MEDIA_TYPE:
                    $this->interWiki = InterWiki::createMediaInterWikiFromString($ref);
                    break;
                case self::LINK_TYPE:
                    $this->interWiki = InterWiki::createLinkInterWikiFromString($ref);
                    break;
                default:
                    LogUtility::internalError("The type ($type) is unknown, returning a interwiki link ref");
                    $this->interWiki = InterWiki::createLinkInterWikiFromString($ref);
                    break;
            }
            $this->url = $this->interWiki->toUrl();
            return;

        }


        /**
         * It can be a link with a ref template
         */
        if (syntax_plugin_combo_variable::isVariable($ref)) {
            $this->refScheme = MarkupRef::VARIABLE_URI;
            return;
        }

        /**
         * Doku Path
         * We parse it
         */
        $this->refScheme = MarkupRef::WIKI_URI;

        $questionMarkPosition = strpos($ref, "?");
        $wikiPath = $ref;
        $fragment = null;
        $queryStringAndAnchorOriginal = null;
        if ($questionMarkPosition !== false) {
            $wikiPath = substr($ref, 0, $questionMarkPosition);
            $queryStringAndAnchorOriginal = substr($ref, $questionMarkPosition + 1);
        } else {
            // We may have only an anchor
            $hashTagPosition = strpos($ref, "#");
            if ($hashTagPosition !== false) {
                $wikiPath = substr($ref, 0, $hashTagPosition);
                $fragment = substr($ref, $hashTagPosition + 1);
            }
        }

        /**
         *
         * Clean it
         */
        $wikiPath = $this->cleanPath($wikiPath);

        /**
         * The URL
         * The path is created at the end because it may have a revision
         */
        switch ($type) {
            case self::MEDIA_TYPE:
                $this->url = UrlEndpoint::createFetchUrl();
                break;
            case self::LINK_TYPE:
                $this->url = UrlEndpoint::createDokuUrl();
                break;
            default:
                throw new ExceptionBadArgument("The ref type ($type) is unknown");
        }


        /**
         * Parsing Query string if any
         */
        if ($queryStringAndAnchorOriginal !== null) {

            $this->parseAndAddQueryStringAndFragment($queryStringAndAnchorOriginal);

        }

        /**
         * The path
         */
        try {
            $rev = $this->url->getQueryPropertyValue(DokuPath::REV_ATTRIBUTE);
        } catch (ExceptionNotFound $e) {
            $rev = null;
        }
        /**
         * The wiki path may be relative
         */
        switch ($type) {
            case self::MEDIA_TYPE:
                $this->path = DokuPath::createMediaPathFromId($wikiPath, $rev);
                $this->url->addQueryParameter(FetchRaw::MEDIA_QUERY_PARAMETER, $wikiPath);
                $this->addRevToUrl($rev);
                break;
            case self::LINK_TYPE:

                /**
                 * The path may be an id if it exists
                 * otherwise it's a relative path
                 */
                $path = DokuPath::createPagePathFromPath($wikiPath, $rev);
                if (!FileSystems::exists($path)) {
                    $idPath = DokuPath::createPagePathFromId($wikiPath, $rev);
                    if (FileSystems::exists($idPath)) {
                        $path = $idPath;
                    }
                }

                /**
                 * The path may be a namespace, in the page system
                 * the path should then be the index page
                 */
                $this->path = Page::createPageFromPathObject($path)->getPath();
                $this->url->addQueryParameter(DokuwikiId::DOKUWIKI_ID_ATTRIBUTE, $this->path->getDokuwikiId());
                $this->addRevToUrl($rev);
                break;
            default:
                throw new ExceptionBadArgument("The ref type ($type) is unknown");
        }

    }

    /**
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotFound
     */
    public
    static function createMediaFromRef($refProcessing): MarkupRef
    {
        return new MarkupRef($refProcessing, self::MEDIA_TYPE);
    }

    /**
     * @throws ExceptionBadSyntax
     * @throws ExceptionBadArgument
     * @throws ExceptionNotFound
     */
    public
    static function createLinkFromRef($refProcessing): MarkupRef
    {
        return new MarkupRef($refProcessing, self::LINK_TYPE);
    }

    // https://www.dokuwiki.org/urlschemes
    private static function loadAndGetAuthorizedSchemes(): array
    {
        $requestedPage = PluginUtility::getRequestedWikiId();
        if (
            self::$authorizedSchemes === null
            || self::$authorizedSchemes[$requestedPage] === null
        ) {
            self::$authorizedSchemes = null;
            // scoped by request id to be able to work on test because it's a global variable
            self::$authorizedSchemes[$requestedPage] = getSchemes();
            self::$authorizedSchemes[$requestedPage][] = "whatsapp";
            self::$authorizedSchemes[$requestedPage][] = "mailto";
        }
        return self::$authorizedSchemes[$requestedPage];
    }

    /**
     * In case of manual entry, the function will clean the path
     * @param string $wikiPath - a path entered by a user
     * @return string
     */
    public function cleanPath(string $wikiPath): string
    {
        if ($wikiPath === "") {
            return $wikiPath;
        }
        $wikiPath = str_replace(DokuPath::NAMESPACE_SEPARATOR_SLASH, DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, $wikiPath);
        $isNamespacePath = false;
        if ($wikiPath[strlen($wikiPath) - 1] === DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT) {
            $isNamespacePath = true;
        }
        $pathType = "unknown";
        if ($wikiPath[0] === DokuPath::CURRENT_PATH_CHARACTER) {
            $pathType = "current";
            if (isset($wikiPath[1])) {
                if ($wikiPath[1] === DokuPath::CURRENT_PATH_CHARACTER) {
                    $pathType = "parent";
                }
            }
        }
        $cleanPath = cleanID($wikiPath);
        if ($isNamespacePath) {
            $cleanPath = "$cleanPath:";
        }
        switch ($pathType) {
            case "current":
                $cleanPath = DokuPath::CURRENT_PATH_CHARACTER . DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT . $cleanPath;
                break;
            case "parent":
                $cleanPath = DokuPath::CURRENT_PARENT_PATH_CHARACTER . DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT . $cleanPath;
                break;
        }
        return $cleanPath;
    }


    public
    function getUrl(): Url
    {
        return $this->url;
    }

    /**
     * @throws ExceptionNotFound
     */
    public
    function getPath(): DokuPath
    {
        if ($this->path === null) {
            throw new ExceptionNotFound("No path was found");
        }
        return $this->path;
    }

    public
    function getRef(): string
    {
        return $this->ref;
    }

    public function getSchemeType(): string
    {
        return $this->refScheme;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getInterWiki(): InterWiki
    {
        if ($this->interWiki === null) {
            throw new ExceptionNotFound("This ref ($this->ref) is not an interWiki.");
        }
        return $this->interWiki;
    }

    private function addRevToUrl($rev = null): void
    {
        if ($rev !== null) {
            $this->url->addQueryParameter(DokuPath::REV_ATTRIBUTE, $rev);
        }
    }


    public function getType(): string
    {
        return $this->type;
    }

    /**
     * A query parameters value may have a # for the definition of a color
     * This process takes it into account
     * @param string $queryStringAndFragment
     * @return void
     */
    private function parseAndAddQueryStringAndFragment(string $queryStringAndFragment)
    {
        /**
         * The value $queryStringAndAnchorOriginal
         * is kept to create the original queryString
         * at the end if we found an anchor
         *
         * We parse token by token because we allow a hashtag for a hex color
         */
        $queryStringAndAnchorProcessing = $queryStringAndFragment;
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
                $this->url->addQueryParameter(Dimension::WIDTH_KEY, $sizing[1]);
                if (isset($sizing[2])) {
                    $this->url->addQueryParameter(Dimension::HEIGHT_KEY, $sizing[2]);
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
                $this->url->addQueryParameter(MediaMarkup::LINKING_KEY, $linkingValue);
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
                $this->url->addQueryParameter(FetchAbs::CACHE_KEY, $noCacheValue);
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
                $this->url->setFragment(substr($token, 1));
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
                $this->url->setFragment(substr($key, $anchorPosition + 1));
                $key = substr($key, 0, $anchorPosition);
            }

            /**
             * Test Anchor on the value
             */
            if ($value != null) {
                if (($countHashTag = substr_count($value, "#")) >= 3) {
                    LogUtility::msg("The value ($value) of the key ($key) for the link ($this) has $countHashTag `#` characters and the maximum supported is 2.", LogUtility::LVL_MSG_ERROR);
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
                $this->url->setFragment(substr($value, $anchorPosition + 1));
                $value = substr($value, 0, $anchorPosition);
            }

            switch ($lowerCaseKey) {
                case Dimension::WIDTH_KEY_SHORT: // used in a link w=xxx
                    $this->url->addQueryParameter(Dimension::WIDTH_KEY, $value);
                    break;
                case Dimension::HEIGHT_KEY_SHORT: // used in a link h=xxxx
                    $this->url->addQueryParameter(Dimension::HEIGHT_KEY, $value);
                    break;
                default:
                    $this->url->addQueryParameter($key, $value);
                    break;
            }

        }

    }

    public function __toString()
    {
        return $this->getRef();
    }


}
