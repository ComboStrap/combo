<?php


namespace ComboStrap;

use dokuwiki\Cache\CacheInstructions;

/**
 * Class BarCache
 * @package ComboStrap
 *
 * See {@link CacheByLogicalKey} for explanation
 *
 * Adapted from {@link CacheInstructions}
 * because the storage is an array and the storage process is cached in the code function
 * {@link CacheInstructionsByLogicalKey::retrieveCache()} and
 * {@link CacheInstructionsByLogicalKey::storeCache()}
 */
class CacheInstructionsByLogicalKey extends CacheByLogicalKey
{
    public $page;
    public $file;
    public $mode;

    /**
     * BarCache constructor.
     *
     *
     *
     * @param $logicalId - logical id
     * @param $file - file used
     */
    public function __construct($logicalId, $file)
    {
        $this->page = $logicalId;
        $this->file = $file;
        $this->mode = "i";

        /**
         * Same than
         */
        $cacheKey = $logicalId . $_SERVER['HTTP_HOST'] . $_SERVER['SERVER_PORT'];
        parent::__construct($cacheKey, $file, $this->mode);

    }

    /**
     * retrieve the cached data
     *
     * @param bool $clean true to clean line endings, false to leave line endings alone
     * @return  array          cache contents
     */
    public function retrieveCache($clean = true)
    {
        $contents = io_readFile($this->cache, false);
        return !empty($contents) ? unserialize($contents) : array();
    }

    /**
     * cache $instructions
     *
     * @param array $instructions the instruction to be cached
     * @return  bool                  true on success, false otherwise
     */
    public function storeCache($instructions)
    {
        if ($this->_nocache) {
            return false;
        }

        return io_saveFile($this->cache, serialize($instructions));
    }


}
