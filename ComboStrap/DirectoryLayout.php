<?php

namespace ComboStrap;

class DirectoryLayout
{

    public static function getConfigFile()
    {

    }

    public static function getPluginInfoPath(): LocalPath
    {
        return LocalPath::createFromPath( __DIR__ . '/../plugin.info.txt');
    }

    public static function getLocalConfPath(): LocalPath
    {
        return LocalPath::createFromPath( __DIR__ . '/../../../../conf/local.php');
    }
}
