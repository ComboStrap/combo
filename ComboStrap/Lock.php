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
     * @var false|resource
     */
    private $filePointer = null;


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
        /**
         * The flock function follows the semantics of the Unix system call bearing the same name.
         * Flock utilizes ADVISORY locking only; that is:
         * * other processes may ignore the lock completely it only affects those that call the flock call.
         *
         * * LOCK_SH means SHARED LOCK. Any number of processes MAY HAVE A SHARED LOCK simultaneously. It is commonly called a reader lock.
         * * LOCK_EX means EXCLUSIVE LOCK. Only a single process may possess an exclusive lock to a given file at a time.
         *
         * ie if the file has been LOCKED with LOCK_SH in another process,
         * * flock with LOCK_SH will SUCCEED.
         * * flock with LOCK_EX will BLOCK UNTIL ALL READER LOCKS HAVE BEEN RELEASED.
         *
         * When the file is closed, the lock is released by the system anyway.
         */
        // LOCK_NB to not block the process
        while (!$this->getLock()) {
            usleep(1000);
            /**
             * Old lock ? More than 10 minutes run
             */
            if (is_file($this->lockFile) && (time() - @filemtime($this->lockFile)) > 60 * 10) {
                if (!@unlink($this->lockFile)) {
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
     * Release the lock
     * and the resources
     * (Need to be called in all cases)
     */
    function release()
    {
        if ($this->filePointer !== null) {
            fclose($this->filePointer);
            $this->filePointer = null;
        }
        if (file_exists($this->lockFile)) {
            unlink($this->lockFile);
        }
    }

    public function isReleased(): bool
    {
        return !file_exists($this->lockFile);
    }

    public function isLocked(): bool
    {
        return file_exists($this->lockFile);
    }

    public function setTimeout(int $int): Lock
    {
        $this->timeOut = $int;
        return $this;
    }

    private function getLock(): bool
    {
        /**
         * We test also on the file because
         * on some operating systems, flock() is implemented at the process level.
         *
         * ie when using a multithreaded server API you may not be able to rely on flock()
         * to protect files against other PHP scripts running in parallel threads of the same server instance
         */
        if (file_exists($this->lockFile)) {
            return false;
        }

        if ($this->filePointer === null) {
            $mode = "c"; // as specified in the doc
            $this->filePointer = fopen($this->lockFile, $mode);
        }
        /**
         * LOCK_EX: exclusive lock
         * LOCK_NB: to not wait
         */
        return flock($this->filePointer, LOCK_EX | LOCK_NB);
    }

}
