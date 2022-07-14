<?php

namespace ComboStrap;

use DateTime;

class PageFileSystem implements FileSystem
{

    const SCHEME = "page";
    private static PageFileSystem $pageFileSystem;

    public static function getOrCreate(): PageFileSystem
    {
        if (!isset(self::$pageFileSystem)) {
            self::$pageFileSystem = new PageFileSystem();
        }
        return self::$pageFileSystem;
    }


    /**
     * @param PageFragment $path
     * @return bool
     */
    function exists(Path $path): bool
    {
        return FileSystems::exists($path->getPathObject());
    }

    /**
     * @param PageFragment $path
     * @throws ExceptionNotFound
     */
    function getContent(Path $path): string
    {
        return FileSystems::getContent($path->getPathObject());
    }

    /**
     * @param PageFragment $path
     * @throws ExceptionNotFound
     */
    function getModifiedTime(Path $path): DateTime
    {
        return FileSystems::getModifiedTime($path->getPathObject());
    }

}
