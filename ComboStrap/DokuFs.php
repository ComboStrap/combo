<?php


namespace ComboStrap;


use DateTime;

class DokuFs implements FileSystem
{
    public const SCHEME = 'doku';


    /**
     * @var DokuFs
     */
    private static $dokuFS;

    public static function getOrCreate(): DokuFs
    {
        if (self::$dokuFS ===null){
            self::$dokuFS = new DokuFs();
        }
        return self::$dokuFS;
    }

    /**
     * @param DokuPath $path
     */
    function exists(Path $path): bool
    {
        $localAbsolutePath = $path->toLocalPath()->toAbsolutePath()->toString();
        return file_exists($localAbsolutePath);
    }

    /**
     * @param DokuPath $path
     */
    function getContent(Path $path)
    {
        return FileSystems::getContent($path->toLocalPath());
    }

    /**
     * @param DokuPath $path
     */
    function getModifiedTime(Path $path): ?DateTime
    {
        return FileSystems::getModifiedTime($path->toLocalPath());
    }

    /**
     * @param DokuPath $path
     * @return DateTime|false|mixed|null
     */
    public function getCreationTime(Path $path)
    {
        return FileSystems::getCreationTime($path->toLocalPath());
    }

    /**
     * @param DokuPath $path
     */
    public function delete(Path $path)
    {
        FileSystems::delete($path->toLocalPath());
    }

    /**
     * @param DokuPath $path
     */
    public function getSize(Path $path)
    {
        FileSystems::getSize($path->toLocalPath());
    }
}
