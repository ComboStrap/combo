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
     * @param CacheParser $cacheParser
     */
    public function __construct(CacheParser $cacheParser)
    {
        $this->cacheParser = $cacheParser;
    }

    public function getKey(): string
    {
        return $this->cacheParser->key;
    }

    public function getPath(): LocalPath
    {
        return LocalPath::create($this->cacheParser->cache);
    }

    public function getMode(): string
    {
        return $this->cacheParser->mode;
    }

    public function getSlotId(): string
    {
        return $this->cacheParser->page;
    }

    public function getResult(): bool
    {
        return $this->result;
    }

    public function setResult($result): CacheResult
    {
        $this->result = $result;
        return $this;
    }

}
