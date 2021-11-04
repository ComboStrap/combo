<?php


namespace ComboStrap;


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
     */
    public function __construct($url)
    {
        $this->urlComponents = parse_url($url);
        parse_str($this->urlComponents['query'], $queryKeys);
        $this->query = $queryKeys;
    }


    const RESERVED_WORDS = [':', '!', '#', '$', '&', '\'', '(', ')', '*', '+', ',', '/', ';', '=', '?', '@', '[', ']'];

    /**
     * A text to a slug
     * @param $string -  a string
     * @param string $separator - the path separator in the string
     * @return string - a slug that can go into a url
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
     * @param $URL
     * @param $propertyName
     * @return string - the value of the property
     */
    public static function getPropertyValue($URL, $propertyName)
    {
        $parsedQuery = parse_url($URL, PHP_URL_QUERY);
        $parsedQueryArray = [];
        parse_str($parsedQuery, $parsedQueryArray);
        return $parsedQueryArray[$propertyName];
    }

    /**
     * Validate URL
     * Allows for port, path and query string validations
     * @param string $url string containing url user input
     * @return   boolean     Returns TRUE/FALSE
     */
    public static function isValidURL($url)
    {
        // of preg_match('/^https?:\/\//',$url) ? from redirect plugin
        return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
    }

    public static function create(string $url)
    {
        return new Url($url);
    }


}
