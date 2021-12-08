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
                throw new ExceptionComboRuntime("File system ($scheme) unknown");
        }
    }

    public static function getContent(Path $path)
    {
        $scheme = $path->getScheme();
        if ($scheme === LocalFs::SCHEME) {
            return LocalFs::getOrCreate()->getContent($path);
        }
        throw new ExceptionComboRuntime("File system ($scheme) unknown");
    }

    public static function getModifiedTime(Path $path)
    {
        $scheme = $path->getScheme();
        switch ($scheme) {
            case LocalFs::SCHEME:
                return LocalFs::getOrCreate()->getModifiedTime($path);
            case DokuFs::SCHEME:
                return DokuFs::getOrCreate()->getModifiedTime($path);
            default:
                throw new ExceptionComboRuntime("File system ($scheme) unknown");
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
                throw new ExceptionComboRuntime("File system ($scheme) unknown");
        }
    }

    public static function deleteIfExists(Path $path)
    {
        if(FileSystems::exists($path)){
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
                throw new ExceptionComboRuntime("File system ($scheme) unknown");
        }
    }

}
