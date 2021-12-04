<?php


namespace ComboStrap;


class FileSystems
{

    static function exists(Path $path): bool
    {
        $scheme = $path->getScheme();
        if ($scheme === DokuFs::SCHEME) {
            return DokuFs::getOrCreate()->exists($path);
        }
        throw new ExceptionComboRuntime("File system ($scheme) unknown");
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

}
