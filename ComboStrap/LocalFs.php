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
        if (self::$localFs === null) {
            self::$localFs = new LocalFs();
        }
        return self::$localFs;
    }

    function exists(Path $path): bool
    {
        return file_exists($path->toAbsolutePath()->toString());
    }

    /**
     * @param $path
     * @return string - textual content
     * @throws ExceptionNotFound - if the file does not exist
     */
    public function getContent($path): string
    {
        /**
         * Mime check
         */
        $mime = $path->getMime();
        if ($mime === null) {
            LogUtility::msg("The mime is unknown for the path ($path)");
        } else {
            if (!$mime->isTextBased()) {
                LogUtility::msg("This mime content ($mime) is not text base (for the path $path)", LogUtility::LVL_MSG_ERROR);
            }
        }

        $content = @file_get_contents($path->toAbsolutePath()->toString());
        if ($content === false) {
            // file does not exists
            throw new ExceptionNotFound("The file ($path) does not exists");
        }
        return $content;
    }

    /**
     * @throws ExceptionNotFound - if the file does not exist
     */
    public function getModifiedTime($path): DateTime
    {
        if (!self::exists($path)) {
            throw new ExceptionNotFound("Local File System Modified Time: The file ($path) does not exist");
        }
        return Iso8601Date::createFromTimestamp(filemtime($path->toAbsolutePath()->toString()))->getDateTime();
    }

    public function getCreationTime(Path $path)
    {
        if (!$this->exists($path)) {
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
     * @throws ExceptionCompile
     */
    public function createDirectory(Path $dirPath)
    {
        $result = mkdir($dirPath->toAbsolutePath()->toString(), $mode = 0770, $recursive = true);
        if ($result === false) {
            throw new ExceptionCompile("Unable to create the directory path ($dirPath)");
        }
    }

}
