<?php


namespace ComboStrap\Web;

use ComboStrap\ArrayCaseInsensitive;
use ComboStrap\DataType;
use ComboStrap\DokuwikiId;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotEquals;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionRuntimeInternal;
use ComboStrap\FetcherRawLocalPath;
use ComboStrap\FetcherSystem;
use ComboStrap\LocalFileSystem;
use ComboStrap\LogUtility;
use ComboStrap\MediaMarkup;
use ComboStrap\Path;
use ComboStrap\PathAbs;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\WikiPath;
use dokuwiki\Input\Input;

/**
 * Class Url
 * @package ComboStrap
 * There is no URL class in php
 * Only function
 * https://www.php.net/manual/en/ref.url.php
 */
class Url extends PathAbs
{


    public const PATH_SEP = "/";
    /**
     * In HTML (not in css)
     *
     * Because ampersands are used to denote HTML entities,
     * if you want to use them as literal characters, you must escape them as entities,
     * e.g.  &amp;.
     *
     * In HTML, Browser will do the translation for you if you give an URL
     * not encoded but testing library may not and refuse them
     *
     * This URL encoding is mandatory for the {@link ml} function
     * when there is a width and use them not otherwise
     *
     * Thus, if you want to link to:
     * http://images.google.com/images?num=30&q=larry+bird
     * you need to encode (ie pass this parameter to the {@link ml} function:
     * http://images.google.com/images?num=30&amp;q=larry+bird
     *
     * https://daringfireball.net/projects/markdown/syntax#autoescape
     *
     */
    public const AMPERSAND_URL_ENCODED_FOR_HTML = '&amp;';
    /**
     * Used in dokuwiki syntax & in CSS attribute
     * (Css attribute value are then HTML encoded as value of the attribute)
     */
    public const AMPERSAND_CHARACTER = "&";

    const CANONICAL = "url";
    /**
     * The schemes that are relative (normallu only URL ? ie http, https)
     * This class is much more an URI
     */
    const RELATIVE_URL_SCHEMES = ["http", "https"];


    private ArrayCaseInsensitive $query;
    private ?string $path = null;
    private ?string $scheme = null;
    private ?string $host = null;
    private ?string $fragment = null;
    /**
     * @var string - original url string
     */
    private $url;
    private ?int $port = null;
    /**
     * @var bool - does the URL rewrite occurs
     */
    private bool $withRewrite = true;


    /**
     * UrlUtility constructor.
     * @throws ExceptionBadSyntax
     * @throws ExceptionBadArgument
     */
    public function __construct(string $url = null)
    {

        $this->url = $url;
        $this->query = new ArrayCaseInsensitive();
        if ($this->url !== null) {
            /**
             *
             * @var false
             *
             * Note: Url validation is hard with regexp
             * for instance:
             *  - http://example.lan/utility/a-combostrap-component-to-render-web-code-in-a-web-page-javascript-html-...-u8fe6ahw
             *  - does not pass return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
             * of preg_match('/^https?:\/\//',$url) ? from redirect plugin
             *
             * We try to create the object, the object use the {@link parse_url()}
             * method to validate or send an exception if it can be parsed
             */
            $urlComponents = parse_url($url);
            if ($urlComponents === false) {
                throw new ExceptionBadSyntax("The url ($url) is not valid");
            }
            $queryKeys = [];
            $queryString = $urlComponents['query'] ?? null;
            if ($queryString !== null) {
                parse_str($queryString, $queryKeys);
            }
            $this->query = new ArrayCaseInsensitive($queryKeys);
            $this->scheme = $urlComponents["scheme"] ?? null;
            $this->host = $urlComponents["host"] ?? null;
            $port = $urlComponents["port"] ?? null;
            try {
                if ($port !== null) {
                    $this->port = DataType::toInteger($port);
                }
            } catch (ExceptionBadArgument $e) {
                throw new ExceptionBadArgument("The port ($port) in ($url) is not an integer. Error: {$e->getMessage()}");
            }
            $pathUrlComponent = $urlComponents["path"] ?? null;
            if ($pathUrlComponent !== null) {
                $this->setPath($pathUrlComponent);
            }
            $this->fragment = $urlComponents["fragment"] ?? null;

            /**
             * Rewrite occurs only on Dokuwiki Request
             * Not on CDN
             * We use a negation because otherwise the router redirect for now if the value is false by default
             *
             * Rewrite is only allowed on
             *   * relative url
             *   * first party url
             */
            $requestHost = $_SERVER['HTTP_HOST'] ?? null;
            if (!(
                // relative url
                $this->host == null
                ||
                // first party url
                ($requestHost != null && $this->host == $requestHost))
            ) {
                $this->withRewrite = false;
            }

        }
    }


    const RESERVED_WORDS = [':', '!', '#', '$', '&', '\'', '(', ')', '*', '+', ',', '/', ';', '=', '?', '@', '[', ']'];

    /**
     * A text to an encoded url
     * @param $string -  a string
     * @param string $separator - the path separator in the string
     */
    public static function encodeToUrlPath($string, string $separator = WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT): string
    {
        $parts = explode($separator, $string);
        $encodedParts = array_map(function ($e) {
            return urlencode($e);
        }, $parts);
        return implode("/", $encodedParts);
    }

    public static function createEmpty(): Url
    {
        return new Url();
    }

    /**
     *
     */
    public static function createFromGetOrPostGlobalVariable(): Url
    {
        /**
         * May be Just ???
         * Url::createFromString($_SERVER['REQUEST_URI']);
         */
        /**
         * $_REQUEST is a merge between:
         *   * $_GET: the URL parameters (aka. query string)
         *   * $_POST: the array of variables when using a POST application/x-www-form-urlencoded or multipart/form-data
         * Shared check between post and get HTTP method
         * managed and encapsulated by {@link Input}.
         * They add users and other
         * {@link \TestRequest} is using it
         */
        $url = Url::createEmpty();
        foreach ($_REQUEST as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subkey => $subval) {
                    if (is_array($subval)) {
                        if ($key !== "config") {
                            // dokuwiki things
                            LogUtility::warning("The key ($key) is an array of an array and was not taken into account in the request url.");
                        }
                        continue;
                    }

                    if ($key == "do") {
                        // for whatever reason, dokuwiki puts the value in the key
                        $url->addQueryParameter($key, $subkey);
                        continue;
                    }
                    $url->addQueryParameter($key, $subval);

                }
            } else {
                /**
                 * Bad URL format test
                 * In the `src` attribute of `script`, the url should not be encoded
                 * with {@link Url::AMPERSAND_URL_ENCODED_FOR_HTML}
                 * otherwise we get `amp;` as prefix
                 * in Chrome
                 */
                if (strpos($key, "amp;") === 0) {
                    /**
                     * We don't advertise this error, it should not happen
                     * and there is nothing to do to get back on its feet
                     */
                    $message = "The url in src has a bad encoding (the attribute ($key) has a amp; prefix. Infinite cache will not work. Request: " . DataType::toString($_REQUEST);
                    if (PluginUtility::isDevOrTest()) {
                        throw new ExceptionRuntimeInternal($message);
                    } else {
                        LogUtility::warning($message, "url");
                    }
                }
                /**
                 * Added in {@link auth_setup}
                 * Used by dokuwiki
                 */
                if (in_array($key, ['u', 'p', 'http_credentials', 'r'])) {
                    continue;
                }
                $url->addQueryParameter($key, $value);
            }
        }
        return $url;
    }

    /**
     * Utility class to transform windows separator to url path separator
     * @param string $pathString
     * @return array|string|string[]
     */
    public static function toUrlSeparator(string $pathString)
    {
        return str_replace('\\', '/', $pathString);
    }


    function getQueryProperties(): array
    {
        return $this->query->getOriginalArray();
    }

    /**
     * @throws ExceptionNotFound
     */
    function getQueryPropertyValue($key)
    {
        $value = $this->query[$key];
        if ($value === null) {
            throw new ExceptionNotFound("The key ($key) was not found");
        }
        return $value;
    }

    /**
     * Extract the value of a property
     * @param $propertyName
     * @return string - the value of the property
     * @throws ExceptionNotFound
     */
    public function getPropertyValue($propertyName): string
    {
        if (!isset($this->query[$propertyName])) {
            throw new ExceptionNotFound("The property ($propertyName) was not found", self::CANONICAL);
        }
        return $this->query[$propertyName];
    }


    /**
     * @throws ExceptionBadSyntax|ExceptionBadArgument
     */
    public static function createFromString(string $url): Url
    {
        return new Url($url);
    }


    public function getScheme(): string
    {
        if ($this->scheme === null) {
            return "";
        }
        return $this->scheme;
    }

    /**
     * @param string $path
     * @return $this
     * in a https scheme: Not the path has a leading `/` that makes the path absolute
     * in a email scheme: the path is the email (without /) then
     */
    public function setPath(string $path): Url
    {

        /**
         * Normalization hack
         */
        if (strpos($path, "/./") === 0) {
            $path = substr($path, 2);
        }
        $this->path = $path;
        return $this;
    }

    /**
     * @return bool - true if http, https scheme
     */
    public function isHttpUrl(): bool
    {
        try {
            return in_array($this->getScheme(), ["http", "https"]);
        } catch (ExceptionNotFound $e) {
            return false;
        }
    }

    /**
     * Multiple parameter can be set to form an array
     *
     * Example: s=word1&s=word2
     *
     * https://stackoverflow.com/questions/24059773/correct-way-to-pass-multiple-values-for-same-parameter-name-in-get-request
     */
    public function addQueryParameter(string $key, ?string $value = null): Url
    {
        /**
         * Php Array syntax
         */
        if (substr($key, -2) === "[]") {
            $key = substr($key, 0, -2);
            $actualValue = $this->query[$key];
            if ($actualValue === null || is_array($actualValue)) {
                $this->query[$key] = [$value];
            } else {
                $actualValue[] = $value;
                $this->query[$key] = $actualValue;
            }
            return $this;
        }
        if (isset($this->query[$key])) {
            $actualValue = $this->query[$key];
            if (is_array($actualValue)) {
                $this->query[$key][] = $value;
            } else {
                $this->query[$key] = [$actualValue, $value];
            }
        } else {
            $this->query[$key] = $value;
        }
        return $this;
    }


    public function hasProperty(string $key): bool
    {
        if (isset($this->query[$key])) {
            return true;
        }
        return false;
    }

    /**
     * @return Url - add the scheme and the host based on the request if not present
     */
    public function toAbsoluteUrl(): Url
    {
        /**
         * Do we have a path information
         * If not, this is a local url (ie #id)
         * We don't make it absolute
         */
        if ($this->isLocal()) {
            return $this;
        }
        if ($this->getScheme() == "") {
            /**
             * See {@link getBaseURL()}
             */
            if (!is_ssl()) {
                $this->setScheme("http");
            } else {
                $this->setScheme("https");
            }
        }
        try {
            $this->getHost();
        } catch (ExceptionNotFound $e) {
            $remoteHost = Site::getServerHost();
            $this->setHost($remoteHost);

        }
        return $this;
    }

    /**
     * @return string - utility function that call {@link Url::toAbsoluteUrl()} absolute and {@link Url::toString()}
     */
    public function toAbsoluteUrlString(): string
    {
        $this->toAbsoluteUrl();
        return $this->toString();
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getHost(): string
    {
        if ($this->host === null) {
            throw new ExceptionNotFound("No host");
        }
        return $this->host;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getPath(): string
    {
        if ($this->path === null || $this->path === '/') {
            throw new ExceptionNotFound("The path was not found");
        }
        return $this->path;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getFragment(): string
    {
        if ($this->fragment === null) {
            throw new ExceptionNotFound("The fragment was not set");
        }
        return $this->fragment;
    }


    public function __toString()
    {
        return $this->toString();
    }

    public function getQueryPropertyValueOrDefault(string $key, string $defaultIfNull)
    {
        try {
            return $this->getQueryPropertyValue($key);
        } catch (ExceptionNotFound $e) {
            return $defaultIfNull;
        }
    }

    /**
     * Actual vs expected
     *
     * We use this vocabulary (actual/expected) and not (internal/external or left/right) because this function
     * is mostly used in a test framework.
     *
     * @throws ExceptionNotEquals
     */
    public function equals(Url $expectedUrl)
    {
        /**
         * Scheme
         */
        try {
            $actualScheme = $this->getScheme();
        } catch (ExceptionNotFound $e) {
            $actualScheme = "";
        }
        try {
            $expectedScheme = $expectedUrl->getScheme();
        } catch (ExceptionNotFound $e) {
            $expectedScheme = "";
        }
        if ($actualScheme !== $expectedScheme) {
            throw new ExceptionNotEquals("The scheme are not equals ($actualScheme vs $expectedScheme)");
        }
        /**
         * Host
         */
        try {
            $actualHost = $this->getHost();
        } catch (ExceptionNotFound $e) {
            $actualHost = "";
        }
        try {
            $expectedHost = $expectedUrl->getHost();
        } catch (ExceptionNotFound $e) {
            $expectedHost = "";
        }
        if ($actualHost !== $expectedHost) {
            throw new ExceptionNotEquals("The host are not equals ($actualHost vs $expectedHost)");
        }
        /**
         * Query
         */
        $actualQuery = $this->getQueryProperties();
        $expectedQuery = $expectedUrl->getQueryProperties();
        foreach ($actualQuery as $key => $value) {
            $expectedValue = $expectedQuery[$key];
            if ($expectedValue === null) {
                throw new ExceptionNotEquals("The expected url does not have the $key property");
            }
            if ($expectedValue !== $value) {
                throw new ExceptionNotEquals("The $key property does not have the same value ($value vs $expectedValue)");
            }
            unset($expectedQuery[$key]);
        }
        foreach ($expectedQuery as $key => $value) {
            throw new ExceptionNotEquals("The expected URL has an extra property ($key=$value)");
        }

        /**
         * Fragment
         */
        try {
            $actualFragment = $this->getFragment();
        } catch (ExceptionNotFound $e) {
            $actualFragment = "";
        }
        try {
            $expectedFragment = $expectedUrl->getFragment();
        } catch (ExceptionNotFound $e) {
            $expectedFragment = "";
        }
        if ($actualFragment !== $expectedFragment) {
            throw new ExceptionNotEquals("The fragment are not equals ($actualFragment vs $expectedFragment)");
        }

    }

    public function setScheme(string $scheme): Url
    {
        $this->scheme = $scheme;
        return $this;
    }

    public function setHost($host): Url
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @param string $fragment
     * @return $this
     * Example `#step:11:24728`, this fragment is valid!
     */
    public function setFragment(string $fragment): Url
    {
        $this->fragment = $fragment;
        return $this;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getQueryString($ampersand = Url::AMPERSAND_CHARACTER): string
    {
        if (sizeof($this->query) === 0) {
            throw new ExceptionNotFound("No Query string");
        }
        /**
         * To be able to diff them
         */
        $originalArray = $this->query->getOriginalArray();
        ksort($originalArray);

        /**
         * We don't use {@link http_build_query} because:
         *   * it does not the follow the array format (ie s[]=searchword1+seachword2)
         *   * it output 'key=' instead of `key` when the value is null
         */
        $queryString = null;
        foreach ($originalArray as $key => $value) {
            if ($queryString !== null) {
                /**
                 * HTML encoding (ie {@link self::AMPERSAND_URL_ENCODED_FOR_HTML}
                 * happens only when outputing to HTML
                 * The url may also be used elsewhere where &amp; is unknown or not wanted such as css ...
                 *
                 * In test, we may ask the url HTML encoded
                 */
                $queryString .= $ampersand;
            }
            if ($value === null) {
                $queryString .= urlencode($key);
            } else {
                if (is_array($value)) {
                    for ($i = 0; $i < sizeof($value); $i++) {
                        $val = $value[$i];
                        if ($i > 0) {
                            $queryString .= self::AMPERSAND_CHARACTER;
                        }
                        $queryString .= urlencode($key) . "[]=" . urlencode($val);
                    }
                } else {
                    $queryString .= urlencode($key) . "=" . urlencode($value);
                }
            }
        }
        return $queryString;


    }

    /**
     * @throws ExceptionNotFound
     */
    public function getQueryPropertyValueAndRemoveIfPresent(string $key)
    {
        $value = $this->getQueryPropertyValue($key);
        unset($this->query[$key]);
        return $value;
    }


    /**
     * @throws ExceptionNotFound
     */
    function getLastName(): string
    {
        $names = $this->getNames();
        $namesCount = count($names);
        if ($namesCount === 0) {
            throw new ExceptionNotFound("No last name");
        }
        return $names[$namesCount - 1];

    }

    /**
     * @return string
     * @throws ExceptionNotFound
     */
    public function getExtension(): string
    {
        if ($this->hasProperty(MediaMarkup::$MEDIA_QUERY_PARAMETER)) {

            try {
                return FetcherSystem::createPathFetcherFromUrl($this)->getMime()->getExtension();
            } catch (ExceptionCompile $e) {
                LogUtility::internalError("Build error from a Media Fetch URL. We were unable to get the mime. Error: {$e->getMessage()}");
            }

        }
        return parent::getExtension();
    }


    function getNames()
    {

        try {
            $names = explode(self::PATH_SEP, $this->getPath());
            return array_slice($names, 1);
        } catch (ExceptionNotFound $e) {
            return [];
        }

    }

    /**
     * @throws ExceptionNotFound
     */
    function getParent(): Url
    {
        $names = $this->getNames();
        $count = count($names);
        if ($count === 0) {
            throw new ExceptionNotFound("No Parent");
        }
        $parentPath = implode(self::PATH_SEP, array_splice($names, 0, $count - 1));
        return $this->setPath($parentPath);
    }

    function toAbsoluteId(): string
    {
        try {
            return $this->getPath();
        } catch (ExceptionNotFound $e) {
            return "";
        }
    }

    function toAbsolutePath(): Url
    {
        return $this->toAbsoluteUrl();
    }

    function resolve(string $name): Url
    {
        try {
            $path = $this->getPath();
            if ($this->path[strlen($path) - 1] === URL::PATH_SEP) {
                $this->path .= $name;
            } else {
                $this->path .= URL::PATH_SEP . $name;
            }
            return $this;
        } catch (ExceptionNotFound $e) {
            $this->setPath($name);
            return $this;
        }

    }

    /**
     * @param string $ampersand
     * @return string
     */
    public function toString(string $ampersand = Url::AMPERSAND_CHARACTER): string
    {

        try {
            $scheme = $this->getScheme();
        } catch (ExceptionNotFound $e) {
            $scheme = null;
        }


        switch ($scheme) {
            case LocalFileSystem::SCHEME:
                /**
                 * file://host/path
                 */
                $base = "$scheme://";
                try {
                    $base = "$base{$this->getHost()}";
                } catch (ExceptionNotFound $e) {
                    // no host
                }
                try {
                    $path = $this->getAbsolutePath();
                    // linux, network share (file://host/path)
                    $base = "$base{$path}";
                } catch (ExceptionNotFound $e) {
                    // no path
                }
                return $base;
            case "mailto":
            case "whatsapp":
            case "skype":
                /**
                 * Skype. Example: skype:echo123?call
                 * https://docs.microsoft.com/en-us/skype-sdk/skypeuris/skypeuris
                 * Mailto: Example: mailto:java-net@java.sun.com?subject=yolo
                 * https://datacadamia.com/marketing/email/mailto
                 */
                $base = "$scheme:";
                try {
                    $base = "$base{$this->getPath()}";
                } catch (ExceptionNotFound $e) {
                    // no path
                }
                try {
                    $base = "$base?{$this->getQueryString()}";
                } catch (ExceptionNotFound $e) {
                    // no query string
                }
                try {
                    $base = "$base#{$this->getFragment()}";
                } catch (ExceptionNotFound $e) {
                    // no fragment
                }
                return $base;
            case "http":
            case "https":
            case "ftp":
            default:
                /**
                 * Url Rewrite
                 * Absolute vs Relative, __media, ...
                 */
                if ($this->withRewrite) {
                    UrlRewrite::rewrite($this);
                }
                /**
                 * Rewrite may have set a default scheme
                 * We read it again
                 */
                try {
                    $scheme = $this->getScheme();
                } catch (ExceptionNotFound $e) {
                    $scheme = null;
                }
                try {
                    $host = $this->getHost();
                } catch (ExceptionNotFound $e) {
                    $host = null;
                }
                /**
                 * Absolute/Relative Uri
                 */
                $base = "";
                if ($host !== null) {
                    if ($scheme !== null) {
                        $base = "{$scheme}://";
                    }
                    $base = "$base{$host}";
                    try {
                        $base = "$base:{$this->getPort()}";
                    } catch (ExceptionNotFound $e) {
                        // no port
                    }
                } else {
                    if (!in_array($scheme, self::RELATIVE_URL_SCHEMES) && $scheme !== null) {
                        $base = "{$scheme}:";
                    }
                }

                try {
                    $base = "$base{$this->getAbsolutePath()}";
                } catch (ExceptionNotFound $e) {
                    // ok
                }

                try {
                    $base = "$base?{$this->getQueryString($ampersand)}";
                } catch (ExceptionNotFound $e) {
                    // ok
                }

                try {
                    $base = "$base#{$this->getFragment()}";
                } catch (ExceptionNotFound $e) {
                    // ok
                }
                return $base;
        }


    }

    /**
     * Query parameter can have several values
     * This function makes sure that there is only one value for one key
     * if the value are different, the value will be added
     * @param string $key
     * @param string $value
     * @return Url
     */
    public function addQueryParameterIfNotActualSameValue(string $key, string $value): Url
    {
        try {
            $actualValue = $this->getQueryPropertyValue($key);
            if ($actualValue !== $value) {
                $this->addQueryParameter($key, $value);
            }
        } catch (ExceptionNotFound $e) {
            $this->addQueryParameter($key, $value);
        }

        return $this;

    }

    function getUrl(): Url
    {
        return $this;
    }

    public function toHtmlString(): string
    {
        return $this->toString(Url::AMPERSAND_URL_ENCODED_FOR_HTML);
    }

    /**
     * @throws ExceptionNotFound
     */
    private function getPort(): int
    {
        if ($this->port === null) {
            throw new ExceptionNotFound("No port specified");
        }
        return $this->port;
    }

    public function addQueryParameterIfNotPresent(string $key, string $value)
    {
        if (!$this->hasProperty($key)) {
            $this->addQueryParameterIfNotActualSameValue($key, $value);
        }
    }

    /**
     * Set/replace a query parameter with the new value
     * @param string $key
     * @param string $value
     * @return Url
     */
    public function setQueryParameter(string $key, string $value): Url
    {
        $this->deleteQueryParameter($key);
        $this->addQueryParameter($key, $value);
        return $this;
    }

    public function deleteQueryParameter(string $key)
    {
        unset($this->query[$key]);
    }

    /**
     * @return string - An url in the DOM use the ampersand character
     * If you want to check the value of a DOM attribute, you need to check it with this value
     */
    public function toDomString(): string
    {
        // ampersand for dom string
        return $this->toString();
    }

    public function toCssString(): string
    {
        // ampersand for css
        return $this->toString();
    }

    /**
     * @return bool - if the url points to the same website than the host
     */
    public function isExternal(): bool
    {
        try {
            // We set the path, otherwise it's seen as a local url
            $localHost = Url::createEmpty()->setPath("/")->toAbsoluteUrl()->getHost();
            return $localHost !== $this->getHost();
        } catch (ExceptionNotFound $e) {
            // no host meaning that the url is relative and then local
            return false;
        }
    }

    /**
     * In a url, in a case, the path should be absolute
     * This function makes it absolute if not.
     * In case of messaging scheme (mailto, whatsapp, ...), this is not the case
     * @throws ExceptionNotFound
     */
    private function getAbsolutePath(): string
    {
        $pathString = $this->getPath();
        if ($pathString[0] !== "/") {
            return "/{$pathString}";
        }
        return $pathString;
    }


    /**
     * @throws ExceptionBadSyntax
     * @throws ExceptionBadArgument
     */
    public static function createFromUri(string $uri): Path
    {
        return new Url($uri);
    }

    public function deleteQueryProperties(): Url
    {
        $this->query = new ArrayCaseInsensitive();;
        return $this;
    }

    public function withoutRewrite(): Url
    {
        $this->withRewrite = false;
        return $this;
    }

    /**
     * Dokuwiki utility to check if the URL is local
     * (ie has not path, only a fragment such as #id)
     * @return bool
     */
    public function isLocal(): bool
    {
        if ($this->path !== null) {
            return false;
        }
        /**
         * The path paramater of Dokuwiki
         */
        if ($this->hasProperty(DokuwikiId::DOKUWIKI_ID_ATTRIBUTE)) {
            return false;
        }
        if ($this->hasProperty(MediaMarkup::$MEDIA_QUERY_PARAMETER)) {
            return false;
        }
        if ($this->hasProperty(FetcherRawLocalPath::SRC_QUERY_PARAMETER)) {
            return false;
        }
        return true;
    }


}
