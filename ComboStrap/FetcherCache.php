<?php


namespace ComboStrap;

use dokuwiki\Cache\Cache;

/**
 * Class Cache
 * A wrapper around {@link \dokuwiki\Cache\Cache}
 * @package ComboStrap
 * that takes into account the arguments / properties of the media
 * to create the cache file
 */
class FetcherCache
{

    const CANONICAL = "fetcher:cache";


    /**
     * @var Cache
     */
    private Cache $fileCache;


    private array $fileDependencies = [];
    private IFetcher $fetcher;


    /**
     * @param IFetcher $fetcher
     * @param String[] $keys - extra cache keys that are not in the url because they are retrieved from a database
     * This is the case of the {@link FetcherPage::getRequestedTemplate()} as it can be changed in the database
     * but we don't want to see it in the URL.
     */
    public function __construct(IFetcher $fetcher, array $keys = [])
    {

        $this->fetcher = $fetcher;
        /**
         * Cache Key Construction
         */
        $cacheKey = $fetcher->getFetchUrl()->toAbsoluteUrlString();
        foreach ($keys as $key) {
            $cacheKey .= $key;
        }
        $this->fileCache = new Cache($cacheKey, ".{$fetcher->getMime()->getExtension()}");

    }

    /**
     * @param IFetcher $fetch
     * @param String[] $cacheKeys - extra cache keys that are not in the url because they are retrieved from a database
     * @return FetcherCache
     */
    public static function createFrom(IFetcher $fetch, array $cacheKeys = []): FetcherCache
    {
        return new FetcherCache($fetch, $cacheKeys);
    }


    /**
     * Cache file depends on code version and configuration
     * @return bool
     */
    public function isCacheUsable(): bool
    {

        $this->addFileDependency(DirectoryLayout::getPluginInfoPath());
        $files = $this->fileDependencies;
        $dependencies = array('files' => $files);

        /**
         * Cache Attribute
         */
        try {
            $requestedCache = $this->fetcher->getRequestedCache();
            $maxAge = $this->fetcher->getCacheMaxAgeInSec($requestedCache);
            $dependencies['age'] = $maxAge;
        } catch (ExceptionNotFound $e) {
            // no requested cache
        }
        return $this->fileCache->useCache($dependencies);

    }


    public function storeCache($content)
    {
        $this->fileCache->storeCache($content);
    }

    public function getFile(): LocalPath
    {
        return LocalPath::createFromPathString($this->fileCache->cache);
    }

    public function addFileDependency(Path $path): FetcherCache
    {
        try {
            $this->fileDependencies[] = LocalPath::createFromPathObject($path)->toAbsolutePath()->toAbsoluteId();
        } catch (ExceptionCast|ExceptionBadArgument $e) {
            LogUtility::internalError("The path seems to be not local, it should never happen.", self::CANONICAL, $e);
        }
        return $this;
    }


}
