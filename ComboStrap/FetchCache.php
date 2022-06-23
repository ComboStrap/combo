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
class FetchCache
{


    /**
     * @var Cache
     */
    private Cache $fileCache;
    private int $maxAge;

    private array $fileDependencies = [];


    /**
     * Cache constructor.
     */
    public function __construct(Fetcher $fetch)
    {

        /**
         * Cache Key Construction
         */
        $cacheKey = $fetch->getFetchUrl()->toAbsoluteUrlString();

        /**
         * Cache Attribute
         */
        $this->setMaxAgeInSec($fetch->getExternalCacheMaxAgeInSec());


        $this->fileCache = new Cache($cacheKey, ".{$fetch->getMime()->getExtension()}");

    }

    public static function createFrom(Fetcher $fetch): FetchCache
    {
        return new FetchCache($fetch);
    }


    /**
     * Cache file depends on code version and configuration
     * @return bool
     */
    public function isCacheUsable(): bool
    {
        if ($this->maxAge === 0) {
            return false;
        } else {
            $files = $this->fileDependencies;
            $files[] = DirectoryLayout::getPluginInfoPath();
            $dependencies = array('files' => $files);
            $dependencies['age'] = $this->maxAge;
            return $this->fileCache->useCache($dependencies);
        }
    }

    public function setMaxAgeInSec($maxAge)
    {

        /**
         * Got the Dokuwiki Rule
         * from
         * https://www.dokuwiki.org/devel:event:fetch_media_status
         */
        if ($maxAge < 0) {
            // cache forever
            $this->maxAge = PHP_INT_MAX;
        } elseif ($maxAge == 0) {
            // never cache
            $this->maxAge = 0;
        } else {
            $this->maxAge = $maxAge;
        }
    }

    public function storeCache($content)
    {
        $this->fileCache->storeCache($content);
    }

    public function getFile(): LocalPath
    {
        return LocalPath::createFromPath($this->fileCache->cache);
    }

    public function addFileDependency(Path $path): FetchCache
    {
        $this->fileDependencies[] = $path->toAbsolutePath()->toPathString();
        return $this;
    }


}
