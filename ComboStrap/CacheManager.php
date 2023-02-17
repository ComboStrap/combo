<?php


namespace ComboStrap;


use DateTime;

/**
 * Class CacheManager
 * @package ComboStrap
 *
 * The cache manager handles all things cache
 * This is just another namespace extension of {@link ExecutionContext}
 * to not have all function in the same place
 *
 * Except for the cache dependencies of a {@link FetcherMarkup::getCacheDependencies() Markup}
 */
class CacheManager
{


    const CACHE_DELETION = "deletion";
    const CACHE_CREATION = "creation";


    /**
     * The list of cache runtimes dependencies by slot {@link MarkupCacheDependencies}
     */
    private array $slotCacheDependencies = [];

    /**
     * The list of cache results slot {@link CacheResults}
     */
    private array $slotCacheResults = [];

    /**
     * @var array hold the result for slot cache expiration
     */
    private array $slotsExpiration = [];


    private ExecutionContext $executionContext;

    public function __construct(ExecutionContext $executionContext)
    {
        $this->executionContext = $executionContext;
    }


    /**
     * @return CacheManager
     * @deprecated use the {@link ExecutionContext::getCacheManager()} instead otherwise you may mix context run
     */
    public static function getFromContextExecution(): CacheManager
    {

        return ExecutionContext::getActualOrCreateFromEnv()->getCacheManager();

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
            try {
                $expirationDate = Cron::getDate($cacheExpirationFrequency);
            } catch (ExceptionBadSyntax $e) {
                LogUtility::error("The cron expression ($cacheExpirationFrequency) of the page ($page) is not a valid cron expression");
                return false;
            }
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
