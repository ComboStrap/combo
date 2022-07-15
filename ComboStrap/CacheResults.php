<?php


namespace ComboStrap;

use dokuwiki\Cache\CacheParser;

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
    private string $wikiId;

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
            $this->cacheResults[$cacheParser->mode] = (new CacheResult($cacheParser))
                ->setResult($event->result);
            /**
             * Add snippet and output dependencies
             */
            if ($cacheParser->mode === FetcherMarkup::XHTML_MODE) {
                $page = $cacheParser->page;
                $pageFragment = Markup::createPageFromId($page)->getHtmlFetcher();
                try {
                    /**
                     * @var CacheParser[] $cacheStores
                     */
                    $cacheStores = [$pageFragment->getSnippetCacheStore(), $pageFragment->getDependenciesCacheStore()];
                    foreach ($cacheStores as $cacheStore) {
                        if (file_exists($cacheStore->cache)) {
                            $this->cacheResults[$cacheStore->mode] = (new CacheResult($cacheStore))
                                ->setResult($event->result);
                        }
                    }
                } finally {
                    $pageFragment->close();
                }
            }
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

    /**
     * @return string
     */
    public function getWikiId(): string
    {
        return $this->wikiId;
    }

}
