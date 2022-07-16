<?php

namespace ComboStrap;

use DateTime;

class MarkupFileSystem implements FileSystem
{

    const SCHEME = "markup";

    private static MarkupFileSystem $pageFileSystem;

    public static function getOrCreate(): MarkupFileSystem
    {
        if (!isset(self::$pageFileSystem)) {
            self::$pageFileSystem = new MarkupFileSystem();
        }
        return self::$pageFileSystem;
    }


    /**
     * @param MarkupPath $path
     * @return bool
     */
    function exists(Path $path): bool
    {
        return FileSystems::exists($path->getPathObject());
    }

    /**
     * @param MarkupPath $path
     * @throws ExceptionNotFound
     */
    function getContent(Path $path): string
    {
        return FileSystems::getContent($path->getPathObject());
    }

    /**
     * @param MarkupPath $path
     * @throws ExceptionNotFound
     */
    function getModifiedTime(Path $path): DateTime
    {
        return FileSystems::getModifiedTime($path->getPathObject());
    }

    /**
     * @param MarkupPath $path
     * @param string|null $type
     * @return MarkupPath[]
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
            $childrenPage[] = MarkupPath::createPageFromPathObject($child);
        }
        return $childrenPage;
    }
}
