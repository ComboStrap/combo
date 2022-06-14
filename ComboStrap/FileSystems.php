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
            case LocalFs::SCHEME:
                return LocalFs::getOrCreate()->exists($path);
            case DokuFs::SCHEME:
                return DokuFs::getOrCreate()->exists($path);
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
            case LocalFs::SCHEME:
                return LocalFs::getOrCreate()->getContent($path);
            case DokuFs::SCHEME:
                return DokuFs::getOrCreate()->getContent($path);
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
            case LocalFs::SCHEME:
                return LocalFs::getOrCreate()->getModifiedTime($path);
            case DokuFs::SCHEME:
                return DokuFs::getOrCreate()->getModifiedTime($path);
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
            case LocalFs::SCHEME:
                return LocalFs::getOrCreate()->getCreationTime($path);
            case DokuFs::SCHEME:
                return DokuFs::getOrCreate()->getCreationTime($path);
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

    public static function delete(Path $path)
    {
        $scheme = $path->getScheme();
        switch ($scheme) {
            case LocalFs::SCHEME:
                LocalFs::getOrCreate()->delete($path);
                return;
            case DokuFs::SCHEME:
                DokuFs::getOrCreate()->delete($path);
                return;
            default:
                throw new ExceptionRuntime("File system ($scheme) unknown");
        }
    }

    public static function getSize(Path $path)
    {

        $scheme = $path->getScheme();
        switch ($scheme) {
            case LocalFs::SCHEME:
                return LocalFs::getOrCreate()->getSize($path);
            case DokuFs::SCHEME:
                return DokuFs::getOrCreate()->getSize($path);
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
            case LocalFs::SCHEME:
                return LocalFs::getOrCreate()->createDirectory($dirPath);
            case DokuFs::SCHEME:
                return DokuFs::getOrCreate()->createDirectory($dirPath);
            default:
                throw new ExceptionRuntime("File system ($scheme) unknown");
        }
    }

    public static function isDirectory(Path $path): bool
    {
        $scheme = $path->getScheme();
        switch ($scheme) {
            case LocalFs::SCHEME:
                return LocalFs::getOrCreate()->isDirectory($path);
            case DokuFs::SCHEME:
                return DokuFs::getOrCreate()->isDirectory($path);
            default:
                throw new ExceptionRuntime("File system ($scheme) unknown");
        }
    }


    /**
     * @throws ExceptionBadArgument
     */
    public static function getChildren(Path $path, string $type = null): array
    {
        $scheme = $path->getScheme();
        switch ($scheme) {
            case LocalFs::SCHEME:
                return LocalFs::getOrCreate()->getChildren($path, $type);
            case DokuFs::SCHEME:
                return DokuFs::getOrCreate()->getChildren($path, $type);
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
            case LocalFs::SCHEME:
                return LocalFs::getOrCreate()->closest($path, $name);
            case DokuFs::SCHEME:
                return DokuFs::getOrCreate()->closest($path, $name);
            default:
                throw new ExceptionRuntime("File system ($scheme) unknown");
        }
    }

    public static function createRegularFile(Path $path)
    {
        $scheme = $path->getScheme();
        switch ($scheme) {
            case LocalFs::SCHEME:
                LocalFs::getOrCreate()->createRegularFile($path);
                break;
            case DokuFs::SCHEME:
                DokuFs::getOrCreate()->createRegularFile($path);
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
            case LocalFs::SCHEME:
                LocalFs::getOrCreate()->setContent($path, $content);
                break;
            case DokuFs::SCHEME:
                DokuFs::getOrCreate()->setContent($path, $content);
                break;
            default:
                throw new ExceptionRuntime("File system ($scheme) unknown");
        }
    }

}
