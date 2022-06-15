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

    /**
     * An array of array because one name may have several value
     * @var array[array] $query
     */
    private ArrayCaseInsensitive $query;
    private ?string $path = null;
    private ?string $scheme = null;
    private ?string $host = null;
    private ?string $fragment = null;


    /**
     * UrlUtility constructor.
     * @throws ExceptionBadSyntax
     */
    public function __construct($url = null)
    {

        $this->query = new ArrayCaseInsensitive();
        if ($url !== null) {
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
    public static function encodeToUrlPath($string, string $separator = DokuPath::PATH_SEPARATOR): string
    {
        $parts = explode($separator, $string);
        $encodedParts = array_map(function ($e) {
            return urlencode($e);
        }, $parts);
        return implode("/", $encodedParts);
    }

    public static function createFetchUrl(): Url
    {

        if (Site::hasUrlRewrite()) {
            $path = '_media';
        } else {
            $path = 'lib/exe/fetch.php';
        }
        try {
            $urlPathBaseDir = Site::getUrlPathBaseDir();
            $path = "$urlPathBaseDir/$path";
        } catch (ExceptionNotFound $e) {
            // ok
        }

        return Url::createEmpty()
            ->setPath($path);


    }

    public static function createDetailUrl(): Url
    {

        if (Site::hasUrlRewrite()) {
            $path = '_detail';
        } else {
            $path = 'lib/exe/detail.php';
        }
        return Url::createEmpty()
            ->setPath($path);
    }

    public static function createEmpty(): Url
    {
        return new Url();
    }

    public static function createFromGetGlobalVariable(): Url
    {
        $url = Url::createEmpty();
        foreach ($_GET as $key => $value) {
            $url->addQueryParameter($key, $value);
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
     */
    public function getPropertyValue($propertyName): string
    {
        return $this->query[$propertyName];
    }


    /**
     * @throws ExceptionBadSyntax
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

    public function setPath(string $path): Url
    {
        if (strpos($path, "/./") === 0) {
            $path = substr($path, 3);
        }
        if ($path[0] === "/") {
            $path = substr($path, 1);
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
    public function addQueryParameter(string $key, ?string $value): Url
    {
        /**
         * Php Array syntax
         */
        if (substr($key, -2) === "[]") {
            $key = substr($key, 0, -2);
            $actualValue = $this->query[$key];
            if ($actualValue === null || is_array($actualValue)) {
                $this->query[$key][] = $value;
            } else {
                $this->query[$key] = [$actualValue, $value];
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
        return $this->toAbsoluteUrlString();
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
     * @throws ExceptionNotEquals
     */
    public function equals(Url $url)
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
            $externalScheme = $url->getScheme();
        } catch (ExceptionNotFound $e) {
            $externalScheme = "";
        }
        if ($actualScheme !== $externalScheme) {
            throw new ExceptionNotEquals("The scheme are not equals ($actualScheme vs $externalScheme)");
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
            $externalHost = $url->getHost();
        } catch (ExceptionNotFound $e) {
            $externalHost = "";
        }
        if ($actualHost !== $externalHost) {
            throw new ExceptionNotEquals("The host are not equals ($actualHost vs $externalHost)");
        }
        /**
         * Query
         */
        $actualQuery = $this->getQuery();
        $externalQuery = $url->getQuery();
        foreach ($actualQuery as $key => $value) {
            $externalValue = $externalQuery[$key];
            if ($externalValue === null) {
                throw new ExceptionNotEquals("The external url does not have the $key property");
            }
            if ($externalValue !== $value) {
                throw new ExceptionNotEquals("The $key property does not have the same value ($value vs $externalValue)");
            }
            unset($externalQuery[$key]);
        }
        foreach ($externalQuery as $key => $value) {
            throw new ExceptionNotEquals("The external URL has an extra property ($key=$value)");
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
            $externalFragment = $url->getFragment();
        } catch (ExceptionNotFound $e) {
            $externalFragment = "";
        }
        if ($actualFragment !== $externalFragment) {
            throw new ExceptionNotEquals("The fragment are not equals ($actualHost vs $externalHost)");
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
    public function getQueryString(): string
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
                 */
                $queryString .= self::AMPERSAND_CHARACTER;
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
    function getLastName()
    {
        $names = $this->getNames();
        if (count($names) >= 1) {
            return $names[0];
        }
        throw new ExceptionNotFound("No last name");
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

    public function toString(): string
    {
        try {
            $base = "{$this->getScheme()}";
        } catch (ExceptionNotFound $e) {
            $base = "";
        }

        try {
            $base = "$base://{$this->getHost()}";
        } catch (ExceptionNotFound $e) {
            // ok
            $base = "$base://";
        }

        try {
            $base = "$base/{$this->getPath()}";
        } catch (ExceptionNotFound $e) {
            // ok
        }

        try {
            $base = "$base?{$this->getQueryString()}";
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
}
