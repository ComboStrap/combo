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
     * @var CacheManager[]
     */
    private static array $cacheManager = [];


    /**
     * The list of cache runtimes dependencies by slot {@link MarkupCacheDependencies}
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

    private WikiPath $requestedPath;

    public function __construct(WikiPath $requestedPath)
    {
        $this->requestedPath = $requestedPath;
    }


    /**
     * @return CacheManager
     */
    public static function getOrCreateFromRequestedPath(): CacheManager
    {

        $path = WikiPath::createRequestedPagePathFromRequest();
        return self::getOrCreateForContextPage($path);

    }


    public static function resetAndGet(): CacheManager
    {
        self::reset();
        return self::getOrCreateFromRequestedPath();
    }

    public static function getOrCreateForContextPage(WikiPath $requestedContextPath): CacheManager
    {
        $contextId = $requestedContextPath->getWikiId();
        $cacheManager = self::$cacheManager[$contextId];
        if ($cacheManager === null) {
            // new run, delete all old cache managers
            self::reset();
            // create
            $cacheManager = new CacheManager($requestedContextPath);
            self::$cacheManager[$contextId] = $cacheManager;
        }
        return $cacheManager;
    }


    /**
     * @param Path $path
     * @return MarkupCacheDependencies
     */
    public function getCacheDependenciesForPath(Path $path): MarkupCacheDependencies
    {

        $pathId = $path->toPathString();
        $cacheRuntimeDependencies = $this->slotCacheDependencies[$pathId];
        if ($cacheRuntimeDependencies === null) {
            $cacheRuntimeDependencies = MarkupCacheDependencies::create($path, $this->requestedPath);
            $this->slotCacheDependencies[$pathId] = $cacheRuntimeDependencies;
        }
        return $cacheRuntimeDependencies;

    }

    /**
     * Still needed even if the cache manager is scoped at the request level
     * as the cache manager only save the first
     * cache result (other will then be true)
     *
     */
    public static function reset()
    {
        self::$cacheManager = [];
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

        $currentFragment = WikiPath::createRunningMarkupWikiPath();
        $cacheDependencies = $this->getCacheDependenciesForPath($currentFragment);
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

        $page = MarkupPath::createPageFromId($pageId);
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
