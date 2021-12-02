<?php


namespace ComboStrap;


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

}
