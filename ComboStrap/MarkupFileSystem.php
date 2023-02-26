<?php

namespace ComboStrap;

use DateTime;

class MarkupFileSystem implements FileSystem
{

    const SCHEME = "markup";
    const CANONICAL = "markup-file-system";

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

    public function setContent(Path $path, string $content)
    {
        try {
            FileSystems::setContent($path->toLocalPath(), $content);
        } catch (ExceptionCast $e) {
            throw new ExceptionRuntimeInternal("The path could not be cast to a local path", self::CANONICAL, 1, $e);
        }
    }

    public function delete(Path $path)
    {

        try {
            FileSystems::delete($path->toLocalPath());
        } catch (ExceptionCast|ExceptionFileSystem $e) {
            throw new ExceptionRuntimeInternal("The path could not be deleted", self::CANONICAL, 1, $e);
        }

    }
}
