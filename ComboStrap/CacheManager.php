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
        try {
            $page = Page::createPageFromRequestedPage();
            $cacheKey = $page->getDokuwikiId();
        } catch (ExceptionCombo $e) {
            /**
             * In test, we may generate html from snippet without
             * request. No error in this case
             */
            if (!PluginUtility::isTest()) {
                LogUtility::msg("The cache manager cannot find the requested page. Cache Errors may occurs. Error: {$e->getMessage()}");
            }
            $cacheKey = PluginUtility::getRequestId();
        }
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
     * In test, we may run more than once
     * This function delete the cache manager
     * and is called
     * when a new request is created {@link \TestUtility::createTestRequest()}
     */
    public static function reset()
    {

        self::$cacheManager = null;

    }


    public function isCacheResultPresentForSlot($slotId, $mode): bool
    {
        $cacheReporter = $this->getCacheResultsForSlot($slotId);
        return $cacheReporter->hasResultForMode($mode);
    }


    public function hasNoCacheResult(): bool
    {
        if($this->slotCacheResults===null){
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
     * @throws ExceptionCombo
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
        $cacheExpirationFrequency = CacheExpirationFrequency::createForPage($page)
            ->getValue();
        if ($cacheExpirationFrequency === null) {
            $this->slotsExpiration[$pageId] = false;
            return false;
        }

        $cacheExpirationDateMeta = CacheExpirationDate::createForPage($page);
        $expirationDate = $cacheExpirationDateMeta->getValue();

        if ($expirationDate === null) {

            $expirationDate = Cron::getDate($cacheExpirationFrequency);
            $cacheExpirationDateMeta->setValue($expirationDate);

        }


        $actualDate = new DateTime();
        if ($expirationDate > $actualDate) {
            $this->slotsExpiration[$pageId] = false;
            return false;
        }

        $this->slotsExpiration[$pageId] = true;
        return true;

    }

}
