<?php


namespace ComboStrap;


class Console
{

    static $on = false;

    /**
     * Print to the console even if OB (Output buffer) is used
     * @param $message
     */
    public static function log($message)
    {
        if (self::$on) {
            fputs(STDOUT, "Console Info: " . $message . PHP_EOL);
        }
    }

    public static function setOff()
    {
        self::$on = false;
    }

    public static function setOn()
    {
        self::$on = true;
    }

    public static function isConsoleRun(): bool
    {
        return (php_sapi_name() === 'cli');
    }

}
