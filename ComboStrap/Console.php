<?php


namespace ComboStrap;


class Console
{

    /**
     * Print to the console even if OB (Output buffer) is used
     * @param $message
     */
    public static function log($message)
    {
        fputs(STDOUT, $message . PHP_EOL);
    }

}
