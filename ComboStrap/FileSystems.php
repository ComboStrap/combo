<?php


namespace ComboStrap;


class FileSystems
{

    static function exists(Path $path): bool
    {
        $scheme = $path->getScheme();
        if($scheme === DokuFs::SCHEME){
            return DokuFs::getOrCreate()->exists($path);
        }
        throw new ExceptionComboRuntime("File system ($scheme) unknown");
    }

}
