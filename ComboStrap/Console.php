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
        $consoleOn = ExecutionContext::getActualOrCreateFromEnv()->isConsoleOn();
        if ($consoleOn) {
            fputs(STDOUT, "Console Info: " . $message . PHP_EOL);
        }
    }

    /**
     * @deprecated for {@link ExecutionContext::setConsoleOff()}
     * @return void
     */
    public static function setOff()
    {
        ExecutionContext::getActualOrCreateFromEnv()->setConsoleOff();
    }

    /**
     * @deprecated for {@link ExecutionContext::setConsoleOn()}
     * @return void
     */
    public static function setOn()
    {
        ExecutionContext::getActualOrCreateFromEnv()->setConsoleOn();
    }

    public static function isConsoleRun(): bool
    {
        return (php_sapi_name() === 'cli');
    }

}
