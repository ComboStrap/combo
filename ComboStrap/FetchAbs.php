<?php


namespace ComboStrap;

/**
 * Class Media
 * @package ComboStrap
 *
 * * It represents a generated file:
 *   * if the image width is 20 -> the image is generated
 *   * same for svg ...
 *
 * This is why there is a cache attribute - this is the cache of the generated file
 * if any
 */
abstract class FetchAbs implements Fetch
{

    private ?string $externalCacheRequested = null;

    /**
     * Doc: https://www.dokuwiki.org/images#caching
     * Cache
     * values:
     *   * cache
     *   * nocache
     *   * recache
     */
    public const CACHE_KEY = 'cache';
    public const CACHE_DEFAULT_VALUE = "cache";

    /**
     * @param Url|null $url
     * @return Url
     */
    function getFetchUrl(Url $url = null): Url
    {
        if ($url === null) {
            $url = Url::createEmpty();
        }
        /**
         * The cache
         */
        try {
            $value = $this->getRequestedCache();
            if ($value !== self::CACHE_DEFAULT_VALUE) {
                $url->addQueryParameter(self::CACHE_KEY, $value);
            }
        } catch (ExceptionNotFound $e) {
            // ok
        }
        /**
         * The buster
         */
        $url->addQueryCacheBuster($this->getBuster());
        return $url;
    }


    /**
     * @throws ExceptionBadArgument
     */
    public function buildFromUrl(Url $url): Fetch
    {
        $cache = $url->getQueryPropertyValue(self::CACHE_KEY);
        $this->setRequestedCache($cache);
        return $this;
    }

    /**
     * @return string $cache - one of {@link FetchCache::CACHE_KEY}
     * @throws ExceptionNotFound
     */
    public function getRequestedCache(): string
    {
        if ($this->externalCacheRequested === null) {
            throw new ExceptionNotFound("No cache was requested");
        }
        return $this->externalCacheRequested;
    }

    /**
     * @throws ExceptionBadArgument
     */
    public function setRequestedCache(string $requestedExternalCache)
    {
        /**
         * Cache transformation
         * From Image cache value (https://www.dokuwiki.org/images#caching)
         * to {@link FetchCache::setMaxAgeInSec()}
         */
        switch ($requestedExternalCache) {
            case "nocache":
            case "recache":
            case "cache":
                $this->externalCacheRequested = $requestedExternalCache;
                break;
            default:
                throw new ExceptionBadArgument("The cache value ($requestedExternalCache) is unknown");
        }
    }

    /**
     * Cache transformation
     * From Image cache value (https://www.dokuwiki.org/images#caching)
     * to {@link FetchCache::setMaxAgeInSec()}
     */
    public function getExternalCacheMaxAgeInSec(): int
    {
        switch ($this->externalCacheRequested) {
            case "nocache":
            case "no":
                $cacheParameter = 0;
                break;
            case "recache":
            case "re":
                try {
                    $cacheParameter = Site::getCacheTime();
                } catch (ExceptionNotFound|ExceptionBadArgument $e) {
                    LogUtility::error("Image Fetch cache was set to `cache`. Why ? We got an error when reading the cache time configuration. Error: {$e->getMessage()}");
                    $cacheParameter = -1;
                }
                break;
            case "cache":
            default:
                $cacheParameter = -1;
                break;
        }
        return $cacheParameter;
    }

}
