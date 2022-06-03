<?php


namespace ComboStrap;


use DateTime;

/**
 * Class CacheManager
 * @package ComboStrap
 *
 * The cache manager is public static object
 * that can be used by plugin to report cache dependency {@link CacheManager::addDependencyForCurrentSlot()}
 * reports and influence the cache
 * of all slot for a requested page
 */
class CacheManager
{


    const CACHE_DELETION = "deletion";
    const CACHE_CREATION = "creation";


    /**
     * @var CacheManager
     */
    private static $cacheManager;


    /**
     * The list of cache runtimes dependencies by slot {@link CacheDependencies}
     */
    private $slotCacheDependencies;

    /**
     * The list of cache results slot {@link CacheResults}
     */
    private $slotCacheResults;

    /**
     * @var array hold the result for slot cache expiration
     */
    private $slotsExpiration;


    /**
     * @return CacheManager
     */
    public static function getOrCreate(): CacheManager
    {

        $page = Page::createPageFromRequestedPage();
        $cacheKey = $page->getDokuwikiId();
        $cacheManager = self::$cacheManager[$cacheKey];
        if ($cacheManager === null) {
            // new run, delete all old cache managers
            self::$cacheManager = [];
            // create
            $cacheManager = new CacheManager();
            self::$cacheManager[$cacheKey] = $cacheManager;
        }
        return $cacheManager;
    }


    public static function resetAndGet(): CacheManager
    {
        self::reset();
        return self::getOrCreate();
    }

    /**
     * @param $id
     * @return CacheDependencies
     */
    public function getCacheDependenciesForSlot($id): CacheDependencies
    {

        $cacheRuntimeDependencies = $this->slotCacheDependencies[$id];
        if ($cacheRuntimeDependencies === null) {
            $cacheRuntimeDependencies = new CacheDependencies($id);
            $this->slotCacheDependencies[$id] = $cacheRuntimeDependencies;
        }
        return $cacheRuntimeDependencies;

    }

    /**
     * @deprecated as the cache manager is now scoped to the requested page
     */
    public static function reset()
    {
    }


    public function isCacheResultPresentForSlot($slotId, $mode): bool
    {
        $cacheReporter = $this->getCacheResultsForSlot($slotId);
        return $cacheReporter->hasResultForMode($mode);
    }


    public function hasNoCacheResult(): bool
    {
        if ($this->slotCacheResults === null) {
            return true;
        }
        return sizeof($this->slotCacheResults) === 0;
    }

    /**
     * @param string $dependencyName
     * @return CacheManager
     */
    public function addDependencyForCurrentSlot(string $dependencyName): CacheManager
    {
        $ID = PluginUtility::getCurrentSlotId();
        $cacheDependencies = $this->getCacheDependenciesForSlot($ID);
        $cacheDependencies->addDependency($dependencyName);
        return $this;

    }


    public function getCacheResultsForSlot(string $id): CacheResults
    {
        $cacheManagerForSlot = $this->slotCacheResults[$id];
        if ($cacheManagerForSlot === null) {
            $cacheManagerForSlot = new CacheResults($id);
            $this->slotCacheResults[$id] = $cacheManagerForSlot;
        }
        return $cacheManagerForSlot;
    }

    /**
     * @return null|CacheResults[] - null if the page does not exists
     */
    public function getCacheResults(): ?array
    {
        return $this->slotCacheResults;
    }

    /**
     */
    public function shouldSlotExpire($pageId): bool
    {

        /**
         * Because of the recursive nature of rendering
         * inside dokuwiki, we just return a result for
         * the first call to the function
         *
         * We use the cache manager as scope element
         * (ie it's {@link CacheManager::reset()} for each request
         */
        if (isset($this->slotsExpiration[$pageId])) {
            return false;
        }

        $page = Page::createPageFromId($pageId);
        try {
            $cacheExpirationFrequency = CacheExpirationFrequency::createForPage($page)
                ->getValue();
        } catch (ExceptionNotFound $e) {
            $this->slotsExpiration[$pageId] = false;
            return false;
        }

        $cacheExpirationDateMeta = CacheExpirationDate::createForPage($page);
        try {
            $expirationDate = $cacheExpirationDateMeta->getValue();
        } catch (ExceptionNotFound $e) {
            $expirationDate = Cron::getDate($cacheExpirationFrequency);
        }
        $cacheExpirationDateMeta->setValue($expirationDate);

        $actualDate = new DateTime();
        if ($expirationDate > $actualDate) {
            $this->slotsExpiration[$pageId] = false;
            return false;
        }

        $this->slotsExpiration[$pageId] = true;
        return true;

    }

}
