<?php


namespace ComboStrap;


use DateTime;

/**
 * Class DokuFs
 * @package ComboStrap
 *
 * The file system of Dokuwiki is based
 * on drive name (such as page and media)
 * that locates a directory on the local file system
 */
class DokuFs implements FileSystem
{


    public const SCHEME = 'doku';


    /**
     * @var DokuFs
     */
    private static $dokuFS;

    public static function getOrCreate(): DokuFs
    {
        if (self::$dokuFS === null) {
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
     * @throws ExceptionNotFound
     */
    function getContent(Path $path): string
    {
        return FileSystems::getContent($path->toLocalPath());
    }

    /**
     * @param DokuPath $path
     * @throws ExceptionNotFound
     */
    function getModifiedTime(Path $path): DateTime
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
        return FileSystems::getSize($path->toLocalPath());
    }

    /**
     * @param DokuPath $dirPath
     * @return mixed
     * @throws ExceptionCompile
     */
    public function createDirectory(Path $dirPath)
    {
        return FileSystems::createDirectory($dirPath->toLocalPath());
    }

    /**
     * @param DokuPath $path
     * @return bool
     */
    public function isDirectory(Path $path): bool
    {
        return FileSystems::isDirectory($path->toLocalPath());
    }

    /**
     * @param DokuPath $path
     * @return DokuPath[]
     */
    public function getChildren(Path $path): array
    {

        $children = FileSystems::getChildren($path->toLocalPath());
        $childrenWiki = [];
        foreach ($children as $child) {
            try {
                $childrenWiki[] = $child->toDokuPath();
            } catch (ExceptionCompile $e) {
                // Should not happen
                LogUtility::error("Unable to get back the wiki path from the local path. Error: {$e->getMessage()}");
            }
        }
        return $childrenWiki;

    }
}
