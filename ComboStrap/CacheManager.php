<?php


namespace ComboStrap;


use dokuwiki\Cache\CacheParser;

/**
 * Class CacheManager
 * @package ComboStrap
 *
 * The cache manager reports and influence the cache
 * of all slot for a requested page
 */
class CacheManager
{


    /**
     * @var CacheManager
     */
    private static $cacheManager;


    /**
     * The list of cache runtimes dependencies by slot {@link CacheRuntimeDependencies}
     */
    private $slotCacheRuntimeDependencies;
    /**
     * The list of cache results slot {@link CacheResults}
     */
    private $slotCacheResults;


    /**
     * @return CacheManager
     */
    public static function getOrCreate(): CacheManager
    {
        $page = Page::createPageFromRequestedPage();
        $cacheManager = self::$cacheManager[$page->getDokuwikiId()];
        if ($cacheManager === null) {
            // new run, delete all old cache managers
            self::$cacheManager = [];
            // create
            $cacheManager = new CacheManager();
            self::$cacheManager[$page->getDokuwikiId()] = $cacheManager;
        }
        return $cacheManager;
    }

    /**
     * @param $id
     * @return CacheRuntimeDependencies
     */
    public function getRuntimeCacheDependenciesForSlot($id): CacheRuntimeDependencies
    {
        $cacheManagerForSlot = $this->slotCacheRuntimeDependencies[$id];
        if ($cacheManagerForSlot === null) {
            $cacheManagerForSlot = new CacheRuntimeDependencies($id);
            $this->slotCacheRuntimeDependencies[$id] = $cacheManagerForSlot;
        }
        return $cacheManagerForSlot;

    }

    /**
     * In test, we may run more than once
     * This function delete the cache manager
     * and is called when Dokuwiki close (ie {@link \action_plugin_combo_cache::close()})
     */
    public static function reset(): CacheManager
    {

        self::$cacheManager = null;
        return self::getOrCreate();

    }


    public function isCacheResultPresentForSlot($slotId, $mode): bool
    {
        $cacheReporter = $this->getCacheResultsForSlot($slotId);
        return $cacheReporter->hasResultForMode($mode);
    }


    public function hasNoCacheResult(): bool
    {
        return sizeof($this->slotCacheResults) === 0;
    }

    /**
     * @param string $dependencyName
     * @throws ExceptionCombo
     */
    public function addDependency(string $dependencyName)
    {
        $this->getCacheManagerForCurrentSlot()->addDependency($dependencyName);

    }




    /**
     * @return CacheRuntimeDependencies
     * @throws ExceptionCombo
     */
    private function getCacheManagerForCurrentSlot(): CacheRuntimeDependencies
    {
        global $ID;
        if ($ID === null) {
            throw new ExceptionCombo("The actual slot is unknown (global ID is null). We cannot add a dependency");
        }
        return $this->getRuntimeCacheDependenciesForSlot($ID);
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
     * @return CacheResults[]
     */
    public function getCacheResults(): array
    {
        return $this->slotCacheResults;
    }

}
