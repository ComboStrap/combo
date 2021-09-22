<?php


namespace ComboStrap;


class Os
{

    /**
     * From 7.2
     * https://www.php.net/manual/en/reserved.constants.php#constant.php-os-family
     * @return bool
     */
    public static function isWindows(): bool
    {
        return (PHP_OS_FAMILY === "Windows");
    }

}
