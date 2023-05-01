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
    function acquire()
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
            if ($run++ == 5) {
                // we waited 5 seconds for that lock
                throw new ExceptionTimeOut("Unable to get the lock ($this->lockFile)");
            }
        }
        if ($this->perm) {
            chmod($this->lockFile, $this->perm);
        }

    }

    /**
     * Release the indexer lock.
     *
     */
    function release()
    {
        @rmdir($this->lockFile);
    }

}
