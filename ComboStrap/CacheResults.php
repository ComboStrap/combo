<?php


namespace ComboStrap;

/**
 * Class CacheResults
 * @package ComboStrap
 */
class CacheResults
{

    private $cacheResults;
    /**
     * @var string
     */
    private $wikiId;

    /**
     * CacheReporter constructor.
     * @param string $wikiId
     */
    public function __construct(string $wikiId)
    {
        $this->wikiId = $wikiId;
    }

    public function setData(\Doku_Event $event)
    {

        $cacheParser = $event->data;
        /**
         * Metadata and other rendering may occurs
         * recursively in one request
         *
         * We record only the first one because the second call one will use the first
         * one and overwrite the result
         */
        if (!isset($this->cacheResults[$cacheParser->mode])) {
            $this->cacheResults[$cacheParser->mode] = new CacheResult($event);
        }
    }

    /**
     * @return CacheResult[]
     */
    public function getResults(): array
    {
        return $this->cacheResults;
    }

    public function hasResultForMode($mode): bool
    {
        return isset($this->cacheResults[$mode]);
    }

    /**
     * @param string $mode
     * @return null|CacheResult
     */
    public function getResultForMode(string $mode): ?CacheResult
    {
        return $this->cacheResults[$mode];
    }

}
