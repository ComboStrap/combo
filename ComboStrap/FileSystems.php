<?php


namespace ComboStrap;


use renderer_plugin_combo_analytics;

class FileSystems
{

    public const CONTAINER = "container";
    public const LEAF = "leaf";

    static function exists(Path $path): bool
    {
        $scheme = $path->getScheme();
        switch ($scheme) {
            case LocalFileSystem::SCHEME:
                return LocalFileSystem::getOrCreate()->exists($path);
            case WikiFileSystem::SCHEME:
                return WikiFileSystem::getOrCreate()->exists($path);
            case MarkupFileSystem::SCHEME:
                return MarkupFileSystem::getOrCreate()->exists($path);
            default:
                throw new ExceptionRuntime("File system ($scheme) unknown");
        }
    }

    /**
     * @throws ExceptionNotFound - if the file does not exists or the mime is unknown
     */
    public static function getContent(Path $path): string
    {
        $scheme = $path->getScheme();
        switch ($scheme) {
            case LocalFileSystem::SCHEME:
                return LocalFileSystem::getOrCreate()->getContent($path);
            case WikiFileSystem::SCHEME:
                return WikiFileSystem::getOrCreate()->getContent($path);
        }
        throw new ExceptionRuntime("File system ($scheme) unknown");
    }

    /**
     * @throws ExceptionNotFound - if the file does not exist
     */
    public static function getModifiedTime(Path $path): \DateTime
    {
        $scheme = $path->getScheme();
        switch ($scheme) {
            case LocalFileSystem::SCHEME:
                /** @noinspection PhpParamsInspection */
                return LocalFileSystem::getOrCreate()->getModifiedTime($path);
            case WikiFileSystem::SCHEME:
                return WikiFileSystem::getOrCreate()->getModifiedTime($path);
            default:
                throw new ExceptionRuntime("File system ($scheme) unknown");
        }

    }

    /**
     * @throws ExceptionNotFound
     */
    public static function getCreationTime(Path $path)
    {
        $scheme = $path->getScheme();
        switch ($scheme) {
            case LocalFileSystem::SCHEME:
                return LocalFileSystem::getOrCreate()->getCreationTime($path);
            case WikiFileSystem::SCHEME:
                return WikiFileSystem::getOrCreate()->getCreationTime($path);
            default:
                // Internal Error: should not happen
                throw new ExceptionRuntime("File system ($scheme) unknown");
        }
    }

    public static function deleteIfExists(Path $path)
    {
        if (FileSystems::exists($path)) {
            FileSystems::delete($path);
        }
    }

    /**
     * @throws ExceptionFileSystem
     */
    public static function delete(Path $path)
    {
        $scheme = $path->getScheme();
        switch ($scheme) {
            case LocalFileSystem::SCHEME:
                LocalFileSystem::getOrCreate()->delete($path);
                return;
            case WikiFileSystem::SCHEME:
                WikiFileSystem::getOrCreate()->delete($path);
                return;
            case MarkupFileSystem::SCHEME:
                MarkupFileSystem::getOrCreate()->delete($path);
                return;
            default:
                throw new ExceptionRuntime("File system ($scheme) unknown");
        }
    }

    public static function getSize(Path $path)
    {

        $scheme = $path->getScheme();
        switch ($scheme) {
            case LocalFileSystem::SCHEME:
                return LocalFileSystem::getOrCreate()->getSize($path);
            case WikiFileSystem::SCHEME:
                return WikiFileSystem::getOrCreate()->getSize($path);
            default:
                throw new ExceptionRuntime("File system ($scheme) unknown");
        }
    }


    /**
     * @throws ExceptionCompile
     */
    public static function createDirectory(Path $dirPath)
    {
        $scheme = $dirPath->getScheme();
        switch ($scheme) {
            case LocalFileSystem::SCHEME:
                return LocalFileSystem::getOrCreate()->createDirectory($dirPath);
            case WikiFileSystem::SCHEME:
                return WikiFileSystem::getOrCreate()->createDirectory($dirPath);
            default:
                throw new ExceptionRuntime("File system ($scheme) unknown");
        }
    }

    public static function isDirectory(Path $path): bool
    {
        $scheme = $path->getScheme();
        switch ($scheme) {
            case LocalFileSystem::SCHEME:
                return LocalFileSystem::getOrCreate()->isDirectory($path);
            case WikiFileSystem::SCHEME:
                return WikiFileSystem::getOrCreate()->isDirectory($path);
            default:
                throw new ExceptionRuntime("File system ($scheme) unknown");
        }
    }


    /**
     * @return Path[]
     */
    public static function getChildren(Path $path, string $type = null): array
    {
        $scheme = $path->getScheme();
        switch ($scheme) {
            case LocalFileSystem::SCHEME:
                return LocalFileSystem::getOrCreate()->getChildren($path, $type);
            case WikiFileSystem::SCHEME:
                return WikiFileSystem::getOrCreate()->getChildren($path, $type);
            case MarkupFileSystem::SCHEME:
                return MarkupFileSystem::getOrCreate()->getChildren($path, $type);
            default:
                throw new ExceptionRuntime("File system ($scheme) unknown");
        }
    }

    /**
     * @param Path $namespacePath
     * @return Path[]
     */
    public static function getChildrenContainer(Path $namespacePath): array
    {
        try {
            return self::getChildren($namespacePath, FileSystems::CONTAINER);
        } catch (ExceptionBadArgument $e) {
            // as we path the type, it should not happen
            throw new ExceptionRuntime("Error getting the children. Error: {$e->getMessage()}");
        }
    }

    /**
     * @param Path $namespacePath
     * @return Path[]
     */
    public static function getChildrenLeaf(Path $namespacePath): array
    {
        try {
            return self::getChildren($namespacePath, FileSystems::LEAF);
        } catch (ExceptionBadArgument $e) {
            // as we path the type, it should not happen
            throw new ExceptionRuntime("Error getting the children. Error: {$e->getMessage()}");
        }
    }

    /**
     * Return a cache buster
     * @throws ExceptionNotFound
     * Utility function to calculate a buster based on a path for the implementation of {@link FetchAbstract::getBuster()}
     */
    public static function getCacheBuster(Path $path): string
    {
        $time = FileSystems::getModifiedTime($path);
        return strval($time->getTimestamp());
    }

    /**
     * @throws ExceptionNotFound
     */
    public static function closest(Path $path, string $name): Path
    {
        $scheme = $path->getScheme();
        switch ($scheme) {
            case LocalFileSystem::SCHEME:
                return LocalFileSystem::getOrCreate()->closest($path, $name);
            case WikiFileSystem::SCHEME:
                return WikiFileSystem::getOrCreate()->closest($path, $name);
            default:
                throw new ExceptionRuntime("File system ($scheme) unknown");
        }
    }

    public static function createRegularFile(Path $path)
    {
        $scheme = $path->getScheme();
        switch ($scheme) {
            case LocalFileSystem::SCHEME:
                LocalFileSystem::getOrCreate()->createRegularFile($path);
                break;
            case WikiFileSystem::SCHEME:
                WikiFileSystem::getOrCreate()->createRegularFile($path);
                break;
            default:
                throw new ExceptionRuntime("File system ($scheme) unknown");
        }
    }

    /**
     * @throws ExceptionNotFound - if the mime is unknown and was not found
     */
    public static function getMime(Path $path): Mime
    {
        $extension = $path->getExtension();
        try {
            return Mime::createFromExtension($extension);
        } catch (ExceptionNotFound $e) {
            $mime = mimetype($path->getLastName(), true)[1];
            if ($mime === null || $mime === false) {
                throw new ExceptionNotFound("No mime found for path ($path). The mime type of the media is <a href=\"https://www.dokuwiki.org/mime\">unknown (not in the configuration file)</a>");
            }
            return new Mime($mime);
        }
    }

    public static function setContent(Path $path, string $content)
    {
        $scheme = $path->getScheme();
        switch ($scheme) {
            case LocalFileSystem::SCHEME:
                LocalFileSystem::getOrCreate()->setContent($path, $content);
                break;
            case WikiFileSystem::SCHEME:
                WikiFileSystem::getOrCreate()->setContent($path, $content);
                break;
            case MarkupFileSystem::SCHEME:
                MarkupFileSystem::getOrCreate()->setContent($path, $content);
                break;
            default:
                throw new ExceptionRuntime("File system ($scheme) unknown");
        }
    }

    /**
     * @param Path[] $paths
     * @return Path
     * @throws ExceptionNotFound
     */
    public static function getFirstExistingPath(array $paths): Path
    {

        foreach ($paths as $path) {
            if (FileSystems::exists($path)) {
                return $path;
            }
        }
        throw new ExceptionNotFound("Existing file could be found");

    }

    public static function getTree(Path $path): PathTreeNode
    {
        return PathTreeNode::buildTreeViaFileSystemChildren($path);
    }

    /**
     * @throws ExceptionBadArgument - if they are not local path
     */
    public static function copy(Path $source, Path $destination)
    {
        $sourceLocal = LocalPath::createFromPathObject($source);
        $destinationLocal = LocalPath::createFromPathObject($destination);
        copy($sourceLocal->toQualifiedPath(), $destinationLocal->toQualifiedPath());

        // D:\dokuwiki\lib\plugins\combo\_test\resources\bootstrapLocal.json
    }

    /**
     * Debug
     * @param Path $mediaFile
     * @return void
     * Unfortunately, due to php, the time may be not use
     * at the second
     * as for the same file, we may end up in the same
     * process to two differents modified time.
     * ie
     * Fri, 10 Feb 2023 18:22:09 +0000
     * Fri, 10 Feb 2023 18:22:30 +0000
     */
    public static function printModificationTimeToConsole(Path $mediaFile, string $log)
    {
        fputs(STDOUT, "ModificationTime of {$mediaFile->toQualifiedPath()}".PHP_EOL);
        $filename = $mediaFile->toAbsolutePath()->toQualifiedPath();
        fputs(STDOUT, "ModificationTime of $filename at $log ");
        $timestamp = filemtime($filename);
        fputs(STDOUT, " $timestamp" . PHP_EOL);
    }

    public static function clearStatCache(Path $path)
    {
        try {
            $pathString = $path->toLocalPath()->toCanonicalPath()->toQualifiedPath();
            clearstatcache(true,$pathString);
        } catch (ExceptionCast $e) {
            throw new ExceptionRuntimeInternal("The cache can be clear only for local path");
        }

    }
}
