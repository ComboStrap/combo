<?php


namespace ComboStrap;


class FileSystems
{

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
    public static function getModifiedTime(Path $path): ?\DateTime
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

    public static function getCreationTime(Path $path)
    {
        $scheme = $path->getScheme();
        switch ($scheme) {
            case LocalFs::SCHEME:
                return LocalFs::getOrCreate()->getCreationTime($path);
            case DokuFs::SCHEME:
                return DokuFs::getOrCreate()->getCreationTime($path);
            default:
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
}
