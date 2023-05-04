<?php

namespace ComboStrap;


use dokuwiki\Search\Indexer;

/**
 * Adapted from the {@link Indexer::lock()}
 * because the TaskRunner does not run serially
 * Only the indexer does
 * https://forum.dokuwiki.org/d/21044-taskrunner-running-multiple-times-eating-the-memory-lock
 */
class Lock
{
    private string $lockName;
    private string $lockFile;
    /**
     * @var mixed|null
     */
    private $perm;
    private int $timeOut = 5;


    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->lockName = $name;
        global $conf;
        $this->lockFile = $conf['lockdir'] . "/_{$this->lockName}.lock";
        $this->perm = $conf['dperm'] ?? null;
    }

    public static function create(string $name): Lock
    {
        return new Lock($name);
    }

    /**
     * @throws ExceptionTimeOut - with the timeout
     */
    function acquire(): Lock
    {
        $run = 0;
        while (!@mkdir($this->lockFile)) {
            usleep(1000);
            /**
             * Old lock ? More than 5 minutes run
             */
            if (is_dir($this->lockFile) && (time() - @filemtime($this->lockFile)) > 60 * 5) {
                if (!@rmdir($this->lockFile)) {
                    throw new ExceptionRuntimeInternal("Removing the lock failed ($this->lockFile)");
                }
            }
            $run++;
            if ($run >= $this->timeOut) {
                throw new ExceptionTimeOut("Unable to get the lock ($this->lockFile) for ($this->timeOut) seconds");
            }
        }
        if ($this->perm) {
            chmod($this->lockFile, $this->perm);
        }
        return $this;

    }

    /**
     * Release the indexer lock.
     *
     */
    function release()
    {
        @rmdir($this->lockFile);
    }

    public function isReleased(): bool
    {
        return !is_dir($this->lockFile);
    }

    public function setTimeout(int $int)
    {
        $this->timeOut = $int;
        return $this;
    }

}
