<?php


namespace ComboStrap;

/**
 * Class Url
 * @package ComboStrap
 * There is no URL class in php
 * Only function
 * https://www.php.net/manual/en/ref.url.php
 */
class Url
{

    /**
     * @var array $query
     */
    private array $query = [];
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
            $this->query = $queryKeys;
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

        return $this->query;
    }

    function getQueryPropertyValue($key)
    {
        return $this->query[$key];
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
        if (strpos($path,"/./") === 0) {
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

    public function addQueryParameter(string $key, string $value): Url
    {
        $this->query[$key] = $value;
        return $this;
    }

    public function addQueryCacheBuster(string $busterValue): Url
    {
        $this->addQueryParameter(Fetch::CACHE_BUSTER_KEY, $busterValue);
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
        try {
            $base = "{$this->getScheme()}://{$this->getHost()}";
        } catch (ExceptionNotFound $e) {
            // should not
            LogUtility::internalError("Absolute Url was called, scheme and host should be set");
            $base = "";
        }
        try {
            $base = "$base/{$this->getPath()}";
        } catch (ExceptionNotFound $e) {
            // ok
        }
        if (count($this->query) > 0) {
            /**
             * To be able to diff them
             */
            ksort($this->query);
            /**
             * HTML encoding (ie {@link DokuwikiUrl::AMPERSAND_URL_ENCODED_FOR_HTML}
             * happens only when outputing to HTML
             * The url may also be used elsewhere where &amp; is unknown or not wanted such as css ...
             */
            $queryStringEncoded = http_build_query($this->query, "", DokuwikiUrl::AMPERSAND_CHARACTER);
            $base = "$base?$queryStringEncoded";
        }
        return $base;
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
        $value = $this->getQueryPropertyValue($key);
        if ($value !== null) {
            return $value;
        }
        return $defaultIfNull;
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


}
