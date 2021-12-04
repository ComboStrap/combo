<?php


namespace ComboStrap;


use DateTime;

class LocalFs implements FileSystem
{

    // same as java
    public const SCHEME = "file";

    /**
     * @var LocalFs
     */
    private static $localFs;

    public static function getOrCreate(): LocalFs
    {
        if(self::$localFs === null){
            self::$localFs = new LocalFs();
        }
        return self::$localFs;
    }

    function exists(Path $path): bool
    {
        return file_exists($path->toAbsolutePath()->toString());
    }

    /**
     * @return false|string
     */
    public function getContent($path)
    {
        $mime = $path->getMime();
        if ($mime->isTextBased()) {
            return file_get_contents($path->toAbsolutePath()->toString());
        }
        throw new ExceptionComboRuntime("This mime content ($mime) can not yet be retrieved");
    }

    public function getModifiedTime($path): ?DateTime
    {
        if(!self::exists($path)){
            return null;
        }
        return Iso8601Date::createFromTimestamp(filemtime($path->toAbsolutePath()->toString()))->getDateTime();
    }

    public function getCreationTime(Path $path)
    {
        return Iso8601Date::createFromTimestamp(filectime($path->toAbsolutePath()->toString()))->getDateTime();
    }


}
