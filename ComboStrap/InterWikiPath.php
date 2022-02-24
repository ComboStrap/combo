<?php


namespace ComboStrap;


class InterWikiPath extends PathAbs
{


    public const scheme = 'interwiki';

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


    public static function create(string $path): InterWikiPath
    {
        return new InterWikiPath($path);
    }

    function getScheme(): string
    {
        return self::scheme;
    }

    function getLastName()
    {
        return $this->getNames()[0];
    }

    function getNames(): array
    {
        $sepPosition = strpos(">", $this->path);
        return [substr($this->path, $sepPosition)];
    }

    function getParent(): ?Path
    {
        return null;
    }


    function toString(): string
    {
        return $this->path;
    }

    function toAbsolutePath(): Path
    {
        return new InterWikiPath($this->path);
    }

    /**
     *
     */
    function resolve(string $name): InterWikiPath
    {
        return self::create($this->path . "/" . $name);
    }
}
