<?php

namespace ComboStrap;

/**
 * Utility font class
 *
 *
 * Font Library to get info on font
 * https://github.com/dompdf/php-font-lib
 *
 * Linux:
 *   * Conf file: `/etc/fonts/fonts.conf`
 *   * Command: `fc-list` command
 *      * font for a language: `fc-list :lang=fr`
 *      * font family name:  `fc-list : family | sort | uniq`
 *
 * Windows:
 *   * Location: 'c:\windows\fonts'
 *
 */
class Font
{


    /**
     * There is no default system font
     * This function will return a sort of default font
     * for the operating system
     * @return LocalPath
     */
    static public function getSystemFont(): LocalPath
    {
        if (Os::isWindows()) {
            return self::getWindowsFontDirectory()->resolve('Arial.ttf');
        } else {
            return LocalPath::createFromPathString('/usr/share/fonts/liberation/LiberationSans-Regular.ttf');
        }
    }

    /**
     * @return LocalPath - the font locale path
     * https://github.com/liberationfonts/liberation-fonts/releases
     */
    static public function getLiberationSansFontRegularPath(): LocalPath
    {
        return WikiPath::createComboResource(":fonts:LiberationSans-Regular.ttf")->toLocalPath();
    }

    static public function getLiberationSansFontBoldPath(): LocalPath
    {
        return WikiPath::createComboResource(":fonts:LiberationSans-Bold.ttf")->toLocalPath();
    }

    static public function printWindowsTrueTypeFont()
    {
        $path = self::getWindowsFontDirectory();
        foreach (FileSystems::getChildrenLeaf($path) as $path) {
            $extension = strtolower($path->getExtension());
            if ($extension === "ttf") {
                echo $path->toPathString() . "\n";
            }
        }
    }

    public static function getWindowsFontDirectory(): LocalPath
    {
        return LocalPath::createFromPathString('c:\windows\fonts');
    }
}
