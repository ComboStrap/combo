<?php

namespace ComboStrap;


use dokuwiki\Search\Indexer;
use dokuwiki\TaskRunner;

/**
 * Adapted from the {@link Indexer::lock()}
 * because the TaskRunner does not run serially
 * Only the indexer does
 * https://forum.dokuwiki.org/d/21044-taskrunner-running-multiple-times-eating-the-memory-lock
 *
 * Example with debug
 * ```
 * ComboLockTaskRunner(): Trying to get a lock
 * ComboLockTaskRunner(): Locked
 * runIndexer(): started
 * Indexer: index for web:browser:selection up to date
 * runSitemapper(): started
 * runSitemapper(): finished
 * sendDigest(): started
 * sendDigest(): disabled
 * runTrimRecentChanges(): started
 * runTrimRecentChanges(): finished
 * runTrimRecentChanges(1): started
 * runTrimRecentChanges(1): finished
 * ComboDispatchEvent(): Trying to get a lock
 * ComboDispatchEvent(): Locked
 * ComboDispatchEvent(): Lock Released
 * ComboLockTaskRunner(): Lock Released
 *
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
     * @var int 1 - no timeout just returns
     */
    private int $timeOut = 1;
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
            sleep(1);
        }
        if ($this->perm) {
            chmod($this->lockFile, $this->perm);
        }
        register_shutdown_function([Lock::class, 'shutdownHandling'], $this->lockName);
        return $this;

    }

    /**
     *
     * A function that is called when the process shutdown
     * due to time exceed for instance that cleans the lock created.
     *
     * https://www.php.net/manual/en/function.register-shutdown-function.php
     *
     * Why ?
     * The lock are created in the `before` of the the task runner event
     * and deleted in the `after` of the task runner event
     * If their is an error somewhere such as as a timeout, the lock
     * is not deleted and there is no task runner anymore for 5 minutes.
     *
     * @param $name - the lock name
     * @return void
     */
    public static function shutdownHandling($name)
    {
        /**
         * For an unknown reason, if we print in this function
         * that is a called via the register_shutdown_function of {@link Lock::acquire()}
         * no content is send with the {@link TaskRunner} (ie the gif is not sent)
         */
        global $INPUT, $conf;
        $output = $INPUT->has('debug') && $conf['allowdebug'];
        if ($output) {
            print "Lock::shutdownHandling(): Deleting the lock $name";
        }

        Lock::create($name)->release();
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
