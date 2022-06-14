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

    public const NOCACHE_VALUE = "nocache";
    private ?string $requestedCache = null;

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
            $url = Url::createFetchUrl();
        }
        /**
         * The cache
         */
        try {
            $value = $this->getRequestedCache();
            if ($value !== self::CACHE_DEFAULT_VALUE) {
                $url->addQueryParameterIfNotActualSameValue(self::CACHE_KEY, $value);
            }
        } catch (ExceptionNotFound $e) {
            // ok
        }
        /**
         * The buster
         */
        $url->addQueryParameterIfNotActualSameValue(Fetch::CACHE_BUSTER_KEY, $this->getBuster());
        return $url;
    }


    /**
     * @throws ExceptionBadArgument
     */
    public function buildFromUrl(Url $url): Fetch
    {
        try {
            $cache = $url->getQueryPropertyValue(self::CACHE_KEY);
            $this->setRequestedCache($cache);
        } catch (ExceptionNotFound $e) {
            // ok
        }
        return $this;
    }

    /**
     * @return string $cache - one of {@link FetchCache::CACHE_KEY}
     * @throws ExceptionNotFound
     */
    public function getRequestedCache(): string
    {
        if ($this->requestedCache === null) {
            throw new ExceptionNotFound("No cache was requested");
        }
        return $this->requestedCache;
    }

    /**
     * @throws ExceptionBadArgument
     */
    public function setRequestedCache(string $requestedCache)
    {
        /**
         * Cache transformation
         * From Image cache value (https://www.dokuwiki.org/images#caching)
         * to {@link FetchCache::setMaxAgeInSec()}
         */
        switch ($requestedCache) {
            case "nocache":
            case "recache":
            case "cache":
                $this->requestedCache = $requestedCache;
                break;
            default:
                throw new ExceptionBadArgument("The cache value ($requestedCache) is unknown");
        }
    }

    /**
     * Cache transformation
     * From Image cache value (https://www.dokuwiki.org/images#caching)
     * to {@link FetchCache::setMaxAgeInSec()}
     */
    public function getExternalCacheMaxAgeInSec(): int
    {
        switch ($this->requestedCache) {
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
