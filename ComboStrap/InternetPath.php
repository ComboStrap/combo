<?php


namespace ComboStrap;

/**
 * Class InternetPath
 * @package ComboStrap
 * A class that takes over the notion of external path
 * url (ie https or ftp scheme)
 */
class InternetPath extends PathAbs
{


    public const scheme = "internet";
    const PATH_SEP = "/";

    private $url;
    /**
     * @var array|false|int|string|null
     */
    private $component;
    private $path;

    /**
     * InterWikiPath constructor.
     */
    public function __construct($url)
    {
        if (!media_isexternal($url)) {
            LogUtility::msg("The path ($url) is not an internet path");
        }
        $this->url = $url;

        $this->component = parse_url($url);
        $this->path = $this->component["path"];

    }


    public static function create(string $uri): InternetPath
    {
        return new InternetPath($uri);
    }

    function getScheme(): string
    {
        return $this->component['scheme'];
    }

    /**
     * @throws ExceptionNotExists
     */
    function getLastName()
    {

        $names = $this->getNames();
        $size = sizeof($names);
        if ($size === 0) {
            throw new ExceptionNotExists("The url has no last name");
        }
        return $names[$size - 1];

    }


    function getNames()
    {

        $names = explode("/", $this->path);
        return array_slice($names, 1);

    }

    function getParent(): ?Path
    {
        throw new ExceptionRuntime("Not yet implemented");
    }


    function toPathString(): string
    {
        return $this->path;
    }

    public function toUriString(): string
    {
        return $this->url;
    }


    function toAbsolutePath(): Path
    {
        return new InternetPath($this->url);
    }


    function resolve(string $name): InternetPath
    {
        /**
         * Not really good if there is any query string or fragment but yeah
         */
        return self::create($this->url . self::PATH_SEP . $name);
    }

    function getFetchUrl(array $queryParameters = [])
    {
        return $this->url;
    }

    public function getHost(): string
    {
        return $this->component["host"];
    }
}
