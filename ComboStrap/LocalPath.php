<?php


namespace ComboStrap;

/**
 * Class LocalPath
 * @package ComboStrap
 * A local file system path
 */
class LocalPath extends PathAbs
{

    private const FILE_SYSTEM_DIRECTORY_SEPARATOR = DIRECTORY_SEPARATOR;

    /**
     * The characters that cannot be in the path for windows
     * @var string[]
     */
    public const RESERVED_WINDOWS_CHARACTERS = ["\\", "/", ":", "*", "?", "\"", "<", ">", "|"];

    private $path;

    /**
     * LocalPath constructor.
     * @param $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }


    public static function create(string $filePath): LocalPath
    {
        return new LocalPath($filePath);
    }

    public static function createFromPath(string $string): LocalPath
    {
        return new LocalPath($string);
    }

    function getScheme(): string
    {
        return LocalFs::SCHEME;
    }

    function getLastName()
    {
        $names = $this->getNames();
        $sizeof = sizeof($names);
        if ($sizeof === 0) {
            return null;
        }
        return $names[$sizeof - 1];

    }

    public function getExtension()
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    function getNames()
    {
        $directorySeparator = $this->getDirectorySeparator();
        return explode($directorySeparator, $this->path);
    }

    function getDokuwikiId()
    {
        throw new ExceptionComboRuntime("Not implemented");
    }


    function toString()
    {
        return $this->path;
    }

    public function getParent(): ?Path
    {
        $absolutePath = pathinfo($this->path, PATHINFO_DIRNAME);
        if (empty($absolutePath)) {
            return null;
        }
        return new LocalPath($absolutePath);
    }

    function toAbsolutePath(): Path
    {
        $path = realpath($this->path);
        if ($path !== false) {
            // Path return false when the file does not exist
            return new LocalPath($path);
        }
        return $this;

    }

    /**
     * @return string
     */
    private function getDirectorySeparator(): string
    {
        $directorySeparator = self::FILE_SYSTEM_DIRECTORY_SEPARATOR;
        if (
            $directorySeparator === '\\'
            &&
            strpos($this->path, "/") !== false
        ) {
            $directorySeparator = "/";
        }
        return $directorySeparator;
    }


}
