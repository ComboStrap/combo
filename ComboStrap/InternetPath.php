<?php


namespace ComboStrap;

/**
 * Class InternetPath
 * @package ComboStrap
 * A class that takes over the notion of external path
 * (ie https or ftp scheme)
 * This class does not make the difference
 */
class InternetPath extends PathAbs
{


    public const scheme = "internet";
    const PATH_SEP = "/";

    private $path;

    /**
     * InterWikiPath constructor.
     */
    public function __construct($path)
    {
        if (!media_isexternal($path)) {
            LogUtility::msg("The path ($path) is not an internet path");
        }
        $this->path = $path;
    }


    public static function create(string $path): InternetPath
    {
        return new InternetPath($path);
    }

    function getScheme(): string
    {
        return self::scheme;
    }

    function getLastName()
    {

        $names = $this->getNames();
        $size = sizeof($names);
        if ($size === 0) {
            return null;
        }
        return $names[$size - 1];

    }

    function getNames()
    {

        $names = explode("/", $this->path);
        // with the scheme and the hostname, the names start at the third position
        $size = sizeof($names);
        if ($size <= 3) {
            return [];
        }
        return array_slice($names, 3);

    }

    function getParent(): ?Path
    {
        throw new ExceptionComboRuntime("Not yet implemented");
    }


    function toString(): string
    {
        return $this->path;
    }

    function toAbsolutePath(): Path
    {
        return new InternetPath($this->path);
    }


    function resolve(string $name): InternetPath
    {
        return self::create($this->path . self::PATH_SEP . $name);
    }
}
