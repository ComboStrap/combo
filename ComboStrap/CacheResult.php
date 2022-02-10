<?php


namespace ComboStrap;


use dokuwiki\Cache\CacheParser;

/**
 * Class CacheResult
 * @package ComboStrap
 *
 * A class to tracks the cache result of each rendering by slot
 *
 */
class CacheResult
{
    /**
     * @var mixed|null
     */
    private $result;
    /**
     * @var CacheParser
     */
    private $cacheParser;


    /**
     * CacheReport constructor.
     * @param \Doku_Event $event
     */
    public function __construct(\Doku_Event $event)
    {
        $this->result = $event->result;
        $this->cacheParser = $event->data;
    }

    public function getKey()
    {
        return $this->cacheParser->key;
    }

    public function getPath(): LocalPath
    {
        return LocalPath::create($this->cacheParser->cache);
    }

    public function getMode()
    {
        return $this->cacheParser->mode;
    }

    public function getSlotId()
    {
        return $this->cacheParser->page;
    }

    public function getResult(): bool
    {
        return $this->result;
    }

}
