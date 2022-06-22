<?php


namespace ComboStrap;

/**
 * Class Media
 * @package ComboStrap
 *
 *
 * This is why there is a cache attribute - this is the cache of the generated file
 * if any
 */
abstract class FetcherAbs implements Fetcher
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
     * @param Url $fetchUrl
     * @return Fetcher
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     * @throws ExceptionNotFound
     * @throws ExceptionInternal
     */
    public static function createFetcherFromFetchUrl(Url $fetchUrl): Fetcher
    {

        try {
            $fetcherAtt = $fetchUrl->getQueryPropertyValue(Fetcher::FETCHER_KEY);
            try {
                $fetchers = ClassUtility::getObjectImplementingInterface(Fetcher::class);
            } catch (\ReflectionException $e) {
                throw new ExceptionInternal("We could read fetch classes via reflection Error: {$e->getMessage()}");
            }
            foreach ($fetchers as $fetcher) {
                /**
                 * @var Fetcher $fetcher
                 */
                if ($fetcher->getFetcherName() === $fetcherAtt) {
                    $fetcher->buildFromUrl($fetchUrl);
                    return $fetcher;
                }
            }
        } catch (ExceptionNotFound $e) {
            // no fetcher property
        }

        try {
            $fetchDoku = FetcherRaw::createFetcherFromFetchUrl($fetchUrl);
            $dokuPath = $fetchDoku->getOriginalPath();
        } catch (ExceptionBadArgument $e) {
            throw new ExceptionNotFound("No fetcher could be matched to the url ($fetchUrl)");
        }
        try {
            $mime = FileSystems::getMime($dokuPath);
        } catch (ExceptionNotFound $e) {
            throw new ExceptionNotFound("No fetcher could be created. The mime us unknown. Error: {$e->getMessage()}");
        }
        switch ($mime->toString()) {
            case Mime::SVG:
                return FetcherSvg::createSvgFromFetchUrl($fetchUrl);
            default:
                if ($mime->isImage()) {
                    return FetcherRaster::createRasterFromFetchUrl($fetchUrl);
                } else {
                    return $fetchDoku;
                }
        }

    }

    /**
     * @param Url|null $url
     * @return Url
     */
    function getFetchUrl(Url $url = null): Url
    {
        if ($url === null) {
            $url = UrlEndpoint::createFetchUrl();
        }
        /**
         * The cache
         */
        try {
            $value = $this->getRequestedCache();
            if ($value !== self::CACHE_DEFAULT_VALUE) {
                $url->setQueryParameter(self::CACHE_KEY, $value);
            }
        } catch (ExceptionNotFound $e) {
            // ok
        }
        /**
         * The buster
         */
        $url->setQueryParameter(Fetcher::CACHE_BUSTER_KEY, $this->getBuster());
        /**
         * The fetcher name
         */
        $fetcherName = $this->getFetcherName();
        $url->setQueryParameter(Fetcher::FETCHER_KEY, $fetcherName);
        return $url;
    }


    /**
     * @throws ExceptionBadArgument
     */
    public function buildFromUrl(Url $url): Fetcher
    {
        $query = $url->getQuery();
        $tagAttributes = TagAttributes::createFromCallStackArray($query);
        $this->buildFromTagAttributes($tagAttributes);
        return $this;
    }

    /**
     * @throws ExceptionBadArgument
     */
    public function buildFromTagAttributes(TagAttributes $tagAttributes): Fetcher
    {

        $cache = $tagAttributes->getValueAndRemove(self::CACHE_KEY);
        if ($cache !== null) {
            $this->setRequestedCache($cache);
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
