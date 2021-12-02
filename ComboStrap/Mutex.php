<?php


namespace ComboStrap;

/**
 * Class Mutex
 * @package ComboStrap
 * Based on https://www.php.net/manual/en/function.flock.php
 *
 * May use also:
 * https://www.php.net/manual/en/class.syncmutex.php
 * https://github.com/php-lock/lock
 *
 */
class Mutex
{
    /**
     * @var string
     */
    private $filePath;
    /**
     * @var false|ResourceCombo
     */
    private $filePointer;


    /**
     * Mutex constructor.
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    function lock($wait=10)
    {

        $this->filePointer = fopen($this->filePath,"w");

        $lock = false;
        for($i = 0; $i < $wait && !($lock = flock($this->filePointer,LOCK_EX|LOCK_NB)); $i++)
        {
            sleep(1);
        }

        if(!$lock)
        {
            trigger_error("Not able to create a lock in $wait seconds");
        }

        return $this->filePointer;
    }

    function unlock(): bool
    {
        $result = flock($this->filePointer,LOCK_UN);
        fclose($this->filePointer);
        @unlink($this->filePath);

        return $result;
    }
}
