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
    private string $path = "";
    private $scheme = "";
    /**
     * @var string
     */
    private string $host = "";
    private string $fragment = "";


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
            $this->path = $urlComponents["path"];
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
        $urlPath = implode("/", $encodedParts);
        return $urlPath;
    }

    public static function createFetchUrl(): Url
    {
        global $conf;
        if ($conf['userewrite'] == 1) {
            $path = '_media';
        } else {
            $path = 'lib/exe/fetch.php';
        }
        return Url::createEmpty()
            ->setPath($path);

    }

    public static function createDetailUrl(): Url
    {
        global $conf;
        if ($conf['userewrite'] == 1) {
            $path = '_detail';
        } else {
            $path = 'lib/exe/detail.php';
        }
        return Url::createEmpty()
            ->setPath($path);
    }

    private static function createEmpty(): Url
    {
        return new Url();
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

    public function getScheme()
    {
        return $this->scheme;
    }

    public function setPath(string $path): Url
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return bool - true if http, https scheme
     */
    public function isHttpUrl(): bool
    {
        return in_array($this->getScheme(), ["http", "https"]);
    }

    public function addQueryParameter(string $key, string $value): Url
    {
        $this->query[$key] = $value;
        return $this;
    }

    public function addQueryCacheBuster(string $busterValue): Url
    {
        $this->addQueryParameter(CacheMedia::CACHE_BUSTER_KEY, $busterValue);
        return $this;
    }

    public function hasProperty(string $key): bool
    {
        if (isset($this->query[$key])) {
            return true;
        }
        return false;
    }

    public function toAbsoluteUrlString(): string
    {

        return "{$this->getScheme()}://{$this->getHost()}";
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * Utility function to add the media query parameter
     * @param string $id
     * @return $this
     */
    public function addQueryMediaParameter(string $id): Url
    {
        $this->addQueryParameter("media", $id);
        return $this;
    }


}
