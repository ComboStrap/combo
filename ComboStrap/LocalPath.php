<?php


namespace ComboStrap;

/**
 * Class LocalPath
 * @package ComboStrap
 * A local file system path
 */
class LocalPath implements Path
{

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

    function getScheme(): string
    {
        // same as java
        return "file";
    }

    function getLastName()
    {
        return pathinfo($this->path, PATHINFO_FILENAME);
    }

    public function getExtension()
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    function getNames()
    {
        throw new ExceptionComboRuntime("Not implemented");
    }

    function getDokuwikiId()
    {
        throw new ExceptionComboRuntime("Not implemented");
    }

    function toLocalPath(): LocalPath
    {
        return $this;
    }

    function toString()
    {
        return $this->path;
    }

    public function getParent(): ?Path
    {
        $absolutePath = pathinfo($this->path, PATHINFO_DIRNAME);
        if(empty($absolutePath)){
            return null;
        }
        return new LocalPath($absolutePath);
    }

    function toAbsolutePath(): Path
    {
        return new LocalPath(realpath($this->path));
    }
}
