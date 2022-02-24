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
     * @return string
     */
    public function getContent($path): ?string
    {
        $mime = $path->getMime();
        if($mime === null){
            throw new ExceptionComboRuntime("The mime is unknown for the path ($path)");
        }
        if ($mime->isTextBased()) {
            $content = @file_get_contents($path->toAbsolutePath()->toString());
            if($content===false){
                // file does not exists
                return null;
            }
            return $content;
        }
        throw new ExceptionComboRuntime("This mime content ($mime) can not yet be retrieved for the path ($path)");
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
        if(!$this->exists($path)){
            return null;
        }
        $filePath = $path->toAbsolutePath()->toString();
        $timestamp = filectime($filePath);
        return Iso8601Date::createFromTimestamp($timestamp)->getDateTime();
    }

    public function delete(Path $path)
    {
        unlink($path->toAbsolutePath()->toString());
    }

    /**
     * @return false|int
     */
    public function getSize($path)
    {
        return filesize($path->toAbsolutePath()->toString());
    }

    /**
     * @throws ExceptionCombo
     */
    public function createDirectory(Path $dirPath)
    {
        $result = mkdir($dirPath->toAbsolutePath()->toString(), $mode = 0770, $recursive = true);
        if($result===false){
            throw new ExceptionCombo("Unable to create the directory path ($dirPath)");
        }
    }

}
