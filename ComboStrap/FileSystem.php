<?php


namespace ComboStrap;


use DateTime;

interface FileSystem
{

    function exists(Path $path): bool;

    /**
     * @param Path $path
     * @return string
     */
    function getContent(Path $path): string;

    function getModifiedTime(Path $path): DateTime;

    public function getChildren(Path $path, string $type = null): array;



}
