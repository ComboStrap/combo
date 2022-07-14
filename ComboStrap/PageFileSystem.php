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

    /**
     * @param PageFragment $path
     * @param string|null $type
     * @return PageFragment[]
     */
    public function getChildren(Path $path, string $type = null): array
    {
        /**
         * A page is never a directory
         * We get the parent directory, iterate over it and returns the children page
         */
        $pathObject = $path->getPathObject();
        try {
            $parent = $pathObject->getParent();
        } catch (ExceptionNotFound $e) {
            return [];
        }
        $childrenPage = [];
        $childrenPath = FileSystems::getChildren($parent, $type);
        foreach ($childrenPath as $child) {
            if ($child->toUriString() === $pathObject->toUriString()) {
                continue;
            }
            $childrenPage[] = PageFragment::createPageFromPathObject($child);
        }
        return $childrenPage;
    }
}
