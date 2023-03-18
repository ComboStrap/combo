<?php


namespace ComboStrap;

use ComboStrap\Web\Url;
use ComboStrap\Web\UrlEndpoint;

/**
 * Class Media
 * @package ComboStrap
 *
 *
 * This is why there is a cache attribute - this is the cache of the generated file
 * if any
 */
abstract class IFetcherAbs implements IFetcher
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
     * @param Url|null $url
     * @return Url
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
        try {
            $buster = $this->getBuster();
            if ($buster !== "") {
                $url->setQueryParameter(IFetcher::CACHE_BUSTER_KEY, $buster);
            }
        } catch (ExceptionNotFound $e) {
            //
        }


        /**
         * The fetcher name
         */
        $fetcherName = $this->getFetcherName();
        $url->setQueryParameter(IFetcher::FETCHER_KEY, $fetcherName);

        return $url;
    }


    /**
     * @throws ExceptionBadArgument
     */
    public function buildFromUrl(Url $url): IFetcher
    {
        $query = $url->getQueryProperties();
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
    public function buildFromTagAttributes(TagAttributes $tagAttributes): IFetcher
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
     * @param string $urlFragment a fragment added to the {@link IFetcher::getFetchUrl() fetch URL}
     */
    public function setRequestedUrlFragment(string $urlFragment): IFetcher
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

    public function getContentCachePath(): LocalPath
    {
        throw new ExceptionNotSupported("No cache support by default, overwrite this function to give access to your cache path");
    }

    public function process(): IFetcher
    {
        throw new ExceptionNotSupported("The fetcher ($this) does not support to feed the cache, overwrite this function to give access to this functionality");
    }

    public function __toString()
    {
        return get_class($this);
    }


}
