<?php


namespace ComboStrap;

/**
 * Class Url
 * @package ComboStrap
 * There is no URL in php
 * Only function
 * https://www.php.net/manual/en/ref.url.php
 */
class Url
{
    /**
     * @var array|false|int|string|null
     */
    private $urlComponents;
    /**
     * @var void
     */
    private $query;


    /**
     * UrlUtility constructor.
     * @throws ExceptionCombo
     */
    public function __construct($url)
    {
        $this->urlComponents = parse_url($url);
        if ($this->urlComponents === false) {
            throw new ExceptionCombo("The url ($url) is not valid");
        }
        parse_str($this->urlComponents['query'], $queryKeys);
        $this->query = $queryKeys;
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

    function getQuery()
    {

        return $this->query;
    }

    function getQueryPropertyValue($prop)
    {
        return $this->query[$prop];
    }

    /**
     * Extract the value of a property
     * @param $propertyName
     * @return string - the value of the property
     */
    public function getPropertyValue($propertyName): string
    {
        $parsedQuery = $this->urlComponents["query"];
        $parsedQueryArray = [];
        parse_str($parsedQuery, $parsedQueryArray);
        return $parsedQueryArray[$propertyName];
    }

    /**
     * Validate URL
     * @return   boolean     Returns TRUE/FALSE
     */
    public static function isValid($url): bool
    {
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
        $urlObject = null;
        try {
            $urlObject = Url::create($url);
        } catch (ExceptionCombo $e) {
            return false;
        }

        $scheme = $urlObject->getScheme();
        if (!in_array($scheme, ["http", "https"])) {
            return false;
        }
        return true;

    }

    /**
     * @throws ExceptionCombo
     */
    public static function create(string $url): Url
    {
        return new Url($url);
    }

    private function getScheme()
    {
        return $this->urlComponents["scheme"];
    }


}
