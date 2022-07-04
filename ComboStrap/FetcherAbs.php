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
    const RECACHE_VALUE = "recache";
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


    private string $requestedUrlFragment;


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
            $fetchDoku = FetcherLocalPath::createLocalFromFetchUrl($fetchUrl);
            $dokuPath = $fetchDoku->getOriginalPath();
        } catch (ExceptionBadArgument $e) {
            throw new ExceptionNotFound("No fetcher could be matched to the url ($fetchUrl)");
        }
        try {
            $mime = FileSystems::getMime($dokuPath);
        } catch (ExceptionNotFound $e) {
            throw new ExceptionNotFound("No fetcher could be created. The mime is unknown for the path ($dokuPath). Error: {$e->getMessage()}");
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
     * @throws ExceptionNotFound
     */
    function getFetchUrl(Url $url = null): Url
    {

        if ($url === null) {
            $url = UrlEndpoint::createFetchUrl();
        }

        try {
            $url->setFragment($this->getRequestedUrlFragment());
        } catch (ExceptionNotFound $e) {
            // no fragment
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
        $buster = $this->getBuster();
        if ($buster !== "") {
            $url->setQueryParameter(Fetcher::CACHE_BUSTER_KEY, $buster);
        }

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
        try {
            $this->setRequestedUrlFragment($url->getFragment());
        } catch (ExceptionNotFound $e) {
            // no fragment
        }
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
     * @return string $cache - one of {@link FetcherCache::CACHE_KEY}
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
     *
     * @throws ExceptionBadArgument
     */
    public function setRequestedCache(string $requestedCache)
    {
        /**
         * Cache transformation
         * From Image cache value (https://www.dokuwiki.org/images#caching)
         * to {@link FetcherCache::setMaxAgeInSec()}
         */
        switch ($requestedCache) {
            case "nocache":
            case self::RECACHE_VALUE:
            case "cache":
                $this->requestedCache = $requestedCache;
                break;
            default:
                throw new ExceptionBadArgument("The cache value ($requestedCache) is unknown");
        }
    }

    /**
     * Get cache age from cache property
     *
     * to {@link FetcherCache::setMaxAgeInSec()}
     */
    public function getCacheMaxAgeInSec(string $cacheValue): int
    {
        /**
         * From the Dokuwiki Rule
         * From Image cache value (https://www.dokuwiki.org/images#caching)
         * and https://www.dokuwiki.org/devel:event:fetch_media_status
         *
         * Not if a value is passed numerically inside dokuwiki, this rule applies
         *  $maxAge < 0 // cache forever
         *  $maxAge === 0 // never cache
         *  $maxAge > 0 // cache for a number of seconds
         */
        switch ($cacheValue) {
            case "nocache":
            case "no":
                // never cache
                return 0;
            case self::RECACHE_VALUE:
            case "re":
                return Site::getXhtmlCacheTime();
            case "cache":
            default:
                // cache forever
                return PHP_INT_MAX;

        }


    }

    /**
     *
     * The fragment may be used by plugin to jump into a media.
     * This is the case of the PDF plugin
     * @param string $urlFragment a fragment added to the {@link Fetcher::getFetchUrl() fetch URL}
     */
    public function setRequestedUrlFragment(string $urlFragment): Fetcher
    {
        $this->requestedUrlFragment = $urlFragment;
        return $this;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getRequestedUrlFragment(): string
    {

        if (!isset($this->requestedUrlFragment)) {
            throw new ExceptionNotFound("No url fragment was requested");
        }
        return $this->requestedUrlFragment;

    }

}
