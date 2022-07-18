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
     * Cache constructor.
     */
    public function __construct(IFetcher $fetcher)
    {

        $this->fetcher = $fetcher;
        /**
         * Cache Key Construction
         */
        $cacheKey = $fetcher->getFetchUrl()->toAbsoluteUrlString();
        $this->fileCache = new Cache($cacheKey, ".{$fetcher->getMime()->getExtension()}");

    }

    public static function createFrom(IFetcher $fetch): FetcherCache
    {
        return new FetcherCache($fetch);
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
        return LocalPath::createFromPath($this->fileCache->cache);
    }

    public function addFileDependency(Path $path): FetcherCache
    {
        try {
            $this->fileDependencies[] = LocalPath::createFromPathObject($path)->toAbsolutePath()->toPathString();
        } catch (ExceptionBadArgument $e) {
            throw new ExceptionRuntimeInternal("The path seems to be not local, it should never happen.", self::CANONICAL, 1, $e);
        }
        return $this;
    }


}
