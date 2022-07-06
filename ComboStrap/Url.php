<?php


namespace ComboStrap;

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
     * UrlUtility constructor.
     * @throws ExceptionBadSyntax
     * @throws ExceptionBadArgument
     */
    public function __construct($url = null)
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
            parse_str($urlComponents['query'], $queryKeys);
            $this->query = new ArrayCaseInsensitive($queryKeys);
            $this->scheme = $urlComponents["scheme"];
            $this->host = $urlComponents["host"];
            $port = $urlComponents["port"];
            try {
                if ($port !== null) {
                    $this->port = DataType::toInteger($port);
                }
            } catch (ExceptionBadArgument $e) {
                throw new ExceptionBadArgument("The port ($port) in ($url) is not an integer. Error: {$e->getMessage()}");
            }
            $pathUrlComponent = $urlComponents["path"];
            if ($pathUrlComponent !== null) {
                $this->setPath($pathUrlComponent);
            }
            $this->fragment = $urlComponents["fragment"];
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

    public static function createFromGetGlobalVariable(): Url
    {
        $url = Url::createEmpty();
        foreach ($_GET as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $val) {
                    $url->addQueryParameter($key, $val);
                }
            } else {
                $url->addQueryParameter($key, $value);
            }
        }
        return $url;
    }


    function getQuery(): array
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

    /**
     * @throws ExceptionNotFound
     */
    public function getScheme(): string
    {
        if ($this->scheme === null) {
            throw new ExceptionNotFound("The scheme was not found");
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
        try {
            $this->getScheme();
        } catch (ExceptionNotFound $e) {
            /**
             * See {@link getBaseURL()}
             */
            $https = $_SERVER['HTTPS'];
            if (empty($https)) {
                $this->setScheme("http");
            } else {
                $this->setScheme("https");
            }
        }
        try {
            $this->getHost();
        } catch (ExceptionNotFound $e) {
            /**
             * Based on {@link getBaseURL()}
             * to be dokuwiki compliant
             */
            $remoteHost = $_SERVER['HTTP_HOST'];
            if ($remoteHost !== null) {
                $this->setHost($remoteHost);
                return $this;
            }
            $remoteHost = $_SERVER['SERVER_NAME'];
            if ($remoteHost !== null) {
                $this->setHost($remoteHost);
                return $this;
            }
            $remoteHost = php_uname('n');
            $this->setHost($remoteHost);

        }
        return $this;
    }

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
        if ($this->path === null) {
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
        $actualQuery = $this->getQuery();
        $expectedQuery = $expectedUrl->getQuery();
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
        if ($this->hasProperty(FetcherLocalPath::$MEDIA_QUERY_PARAMETER)) {

            try {
                return FetcherLocalPath::createFetcherFromFetchUrl($this)->getMime()->getExtension();
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

    function toPathString(): string
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

    public function toString($ampersand = Url::AMPERSAND_CHARACTER): string
    {

        try {
            $scheme = $this->getScheme();
        } catch (ExceptionNotFound $e) {
            $scheme = null;
        }

        switch ($scheme) {
            case LocalPath::SCHEME:
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
                    $path = $this->getPath();
                    if ($path[0] !== "/") {
                        $base = "$base/{$path}";
                    } else {
                        // linux, network share (file://host/path)
                        $base = "$base{$path}";
                    }
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
                UrlRewrite::rewrite($this);
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
                    $base = "$base{$this->getPath()}";
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

    public function toHtmlString()
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
        $this->removeQueryParameter($key);
        $this->addQueryParameter($key, $value);
        return $this;
    }

    public function removeQueryParameter(string $key)
    {
        unset($this->query[$key]);
    }


}
