<?php


namespace ComboStrap;


use DateTime;

/**
 *
 * @package ComboStrap
 *
 * The wiki file system is based
 * on drive name (such as page and media)
 * that locates a directory on the local file system
 */
class WikiFileSystem implements FileSystem
{


    public const SCHEME = 'wiki';


    /**
     * @var WikiFileSystem
     */
    private static WikiFileSystem $wikiFileSystem;

    public static function getOrCreate(): WikiFileSystem
    {
        if (!isset(self::$wikiFileSystem)) {
            self::$wikiFileSystem = new WikiFileSystem();
        }
        return self::$wikiFileSystem;
    }

    /**
     * @param WikiPath $path
     */
    function exists(Path $path): bool
    {
        return FileSystems::exists($path->toLocalPath());
    }

    /**
     * @param WikiPath $path
     * @throws ExceptionNotFound
     */
    function getContent(Path $path): string
    {
        $localPath = $path->toLocalPath();
        return FileSystems::getContent($localPath);
    }

    /**
     * @param WikiPath $path
     * @throws ExceptionNotFound
     */
    function getModifiedTime(Path $path): DateTime
    {
        return FileSystems::getModifiedTime($path->toLocalPath());
    }

    /**
     * @param WikiPath $path
     * @return DateTime
     * @throws ExceptionNotFound
     */
    public function getCreationTime(Path $path): DateTime
    {
        return FileSystems::getCreationTime($path->toLocalPath());
    }

    /**
     * @param WikiPath $path
     */
    public function delete(Path $path)
    {
        FileSystems::delete($path->toLocalPath());
    }

    /**
     * @param WikiPath $path
     */
    public function getSize(Path $path)
    {
        return FileSystems::getSize($path->toLocalPath());
    }

    /**
     * @param WikiPath $dirPath
     * @return mixed
     * @throws ExceptionCompile
     */
    public function createDirectory(Path $dirPath)
    {
        return FileSystems::createDirectory($dirPath->toLocalPath());
    }

    /**
     * @param WikiPath $path
     * @return bool
     */
    public function isDirectory(Path $path): bool
    {
        return WikiPath::isNamespacePath($path->toAbsoluteId());
        // and not FileSystems::isDirectory($path->toLocalPath());
    }

    /**
     * @param WikiPath $path
     * @param string|null $type
     * @return WikiPath[]
     */
    public function getChildren(Path $path, string $type = null): array
    {

        $children = LocalFileSystem::getOrCreate()->getChildren($path->toLocalPath(), $type);
        $childrenWiki = [];
        foreach ($children as $child) {
            try {
                $childrenWiki[] = WikiPath::createFromPathObject($child);
            } catch (ExceptionCompile $e) {
                // Should not happen
                LogUtility::internalError("Unable to get back the wiki path from the local path. Error: {$e->getMessage()}");
            }
        }
        return $childrenWiki;

    }

    /**
     * @param WikiPath $path
     * @param string $lastFullName
     * @return Path
     * @throws ExceptionNotFound
     */
    public function closest(Path $path, string $lastFullName): Path
    {
        return FileSystems::closest($path->toLocalPath(), $lastFullName);
    }

    /**
     * @param WikiPath $path
     * @return void
     */
    public function createRegularFile(Path $path)
    {
        FileSystems::createRegularFile($path->toLocalPath());
    }

    /**
     * @param WikiPath $path
     * @param string $content
     * @return void
     */
    public function setContent(Path $path, string $content)
    {
        FileSystems::setContent($path->toLocalPath(), $content);
    }
}
