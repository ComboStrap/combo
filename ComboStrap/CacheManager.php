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
     * The list of cache runtimes dependencies by slot {@link MarkupCacheDependencies}
     */
    private $slotCacheDependencies;

    /**
     * The list of cache results slot {@link CacheResults}
     */
    private array $slotCacheResults = [];

    /**
     * @var array hold the result for slot cache expiration
     */
    private $slotsExpiration;

    private ExecutionContext $executionContext;

    public function __construct(ExecutionContext $executionContext)
    {
        $this->executionContext = $executionContext;
    }


    /**
     * @deprecated use the {@link ExecutionContext::getCacheManager()} instead otherwise you may mix context run
     * @return CacheManager
     */
    public static function getFromContextExecution(): CacheManager
    {

        return ExecutionContext::getActualOrCreateFromEnv()->getCacheManager();

    }




    /**
     * @param Path $path
     * @return MarkupCacheDependencies
     */
    public function getCacheDependenciesForPath(Path $path): MarkupCacheDependencies
    {

        $pathId = $path->toQualifiedId();
        $cacheRuntimeDependencies = $this->slotCacheDependencies[$pathId];
        if ($cacheRuntimeDependencies === null) {
            $cacheRuntimeDependencies = MarkupCacheDependencies::create($path, $this->executionContext->getRequestedPath());
            $this->slotCacheDependencies[$pathId] = $cacheRuntimeDependencies;
        }
        return $cacheRuntimeDependencies;

    }


    public function isCacheResultPresentForSlot($slotId, $mode): bool
    {
        $cacheReporter = $this->getCacheResultsForSlot($slotId);
        return $cacheReporter->hasResultForMode($mode);
    }



    /**
     * @param string $dependencyName
     * @return CacheManager
     */
    public function addDependencyForCurrentSlot(string $dependencyName): CacheManager
    {

        try {
            $currentMarkup = WikiPath::createExecutingMarkupWikiPath();
        } catch (ExceptionNotFound $e) {
            LogUtility::internalError("We couldn't add the dependency ($dependencyName). The executing markup was unknown.");
            return $this;
        }
        $cacheDependencies = $this->getCacheDependenciesForPath($currentMarkup);
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
     * @return CacheResults[] - null if the page does not exists
     */
    public function getCacheResults(): array
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
         */
        if (isset($this->slotsExpiration[$pageId])) {
            return false;
        }

        $page = MarkupPath::createMarkupFromId($pageId);
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
