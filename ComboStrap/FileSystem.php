<?php


namespace ComboStrap;


use DateTime;

interface FileSystem
{

    function exists(Path $path);

    function getContent(Path $path);

    function getModifiedTime(Path $path): ?DateTime;



}
