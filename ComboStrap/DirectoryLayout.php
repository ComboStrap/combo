<?php

namespace ComboStrap;

/**
 * A class that contains static method that returns known directory
 * of the application
 */
class DirectoryLayout
{



    public static function getPluginInfoPath(): LocalPath
    {
        return self::getComboHome()->resolve("plugin.info.txt");
    }

    public static function getConfLocalFilePath(): LocalPath
    {
        return self::getConfDirectory()->resolve('local.php');
    }

    public static function getConfDirectory(): LocalPath{
        return LocalPath::createFromPath(DOKU_CONF);
    }

    public static function getComboHome(): LocalPath
    {
        return LocalPath::createFromPath(DOKU_PLUGIN . PluginUtility::PLUGIN_BASE_NAME);
    }

    public static function getComboImagesDirectory(): LocalPath
    {
        return self::getComboResourcesDirectory()->resolve("images");
    }

    public static function getComboResourcesDirectory(): LocalPath
    {
        return DirectoryLayout::getComboHome()->resolve("resources");
    }

    public static function getComboDictionaryDirectory(): LocalPath
    {
        return DirectoryLayout::getComboResourcesDirectory()->resolve("dictionary");
    }

    public static function getComboResourceSnippetDirectory(): LocalPath
    {
        return DirectoryLayout::getComboResourcesDirectory()->resolve("snippet");
    }

}
