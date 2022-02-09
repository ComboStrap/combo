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

    const RESULT_STATUS = 'result';
    const DATE_MODIFIED = 'ftime';

    /**
     * Used when the cache data report
     * are injected in the page in a json format
     */
    public const APPLICATION_COMBO_CACHE_JSON = "application/combo+cache+json";

    /**
     * @var CacheManager
     */
    private static $cacheManager;

    /**
     * Just an utility variable to tracks the cache result of each slot
     * @var array the processed slot by:
     *   * requested page id, (to avoid inconsistency  on multiple page run in one test)
     *   * slot id
     */
    private $cacheResults = array();

    /**
     * The list of cache manager for slot
     */
    private $slotCacheManagers;


    /**
     * @return CacheManager
     */
    public static function getOrCreate(): CacheManager
    {
        $page = Page::createPageFromRequestedPage();
        $cacheManager = self::$cacheManager[$page->getDokuwikiId()];
        if ($cacheManager === null) {
            // delete all old cache managers
            self::$cacheManager = [];
            // create
            $cacheManager = new CacheManager();
            self::$cacheManager[$page->getDokuwikiId()] = $cacheManager;
        }
        return $cacheManager;
    }

    /**
     * @param $id
     * @return CacheManagerForSlot
     */
    public function getCacheManagerForSlot($id): CacheManagerForSlot
    {
        $cacheManagerForSlot = $this->slotCacheManagers[$id];
        if ($cacheManagerForSlot === null) {
            $cacheManagerForSlot = new CacheManagerForSlot($id);
            $this->slotCacheManagers[$id] = $cacheManagerForSlot;
        }
        return $cacheManagerForSlot;

    }

    /**
     * In test, we may run more than once
     * This function delete the cache manager
     * and is called when Dokuwiki close (ie {@link \action_plugin_combo_cache::close()})
     */
    public static function reset()
    {

        self::$cacheManager = null;

    }


    /**
     * Keep track of the parsed slot (ie page in page)
     * @param $slotId
     * @param $result
     * @param CacheParser $cacheParser
     */
    public function addSlotForRequestedPage($slotId, $result, CacheParser $cacheParser)
    {

        $requestedPageSlotResults = &$this->getCacheSlotResultsForRequestedPage();


        if (!isset($requestedPageSlotResults[$slotId])) {
            $requestedPageSlotResults[$slotId] = [];
        }

        /**
         * Metadata and other rendering may occurs
         * recursively in one request
         *
         * We record only the first one because the second call one will use the first
         * one
         */
        if (!isset($requestedPageSlotResults[$slotId][$cacheParser->mode])) {
            $date = null;
            if (file_exists($cacheParser->cache)) {
                $date = Iso8601Date::createFromTimestamp(filemtime($cacheParser->cache))->getDateTime();
            }
            $requestedPageSlotResults[$slotId][$cacheParser->mode] = [
                self::RESULT_STATUS => $result,
                self::DATE_MODIFIED => $date
            ];
        }

    }

    public function getXhtmlCacheSlotResultsForRequestedPage(): array
    {
        $cacheSlotResultsForRequestedPage = $this->getCacheSlotResultsForRequestedPage();
        if ($cacheSlotResultsForRequestedPage === null) {
            return [];
        }
        $xhtmlRenderResult = [];
        foreach ($cacheSlotResultsForRequestedPage as $slotId => $modes) {
            foreach ($modes as $mode => $values) {
                if ($mode === "xhtml") {
                    $xhtmlRenderResult[$slotId] = $values[self::RESULT_STATUS];
                }
            }
        }
        return $xhtmlRenderResult;
    }

    private function &getCacheSlotResultsForRequestedPage(): ?array
    {
        $requestedPageId = PluginUtility::getRequestedWikiId();


        if (PluginUtility::isTest() && $requestedPageId === null) {
            /**
             * {@link p_get_metadata()} check the cache and is used
             * also in several place such as {@link feed.php}
             * where we don't have any influence
             */
            LogUtility::msg("The requested page should be known to register a page cache result");
        }

        $requestedPageSlotResults = &$this->cacheResults[$requestedPageId];
        if (!isset($requestedPageSlotResults)) {
            $requestedPageSlotResults = [];
        }
        return $requestedPageSlotResults;
    }

    public function isCacheLogPresentForSlot($slotId, $mode): bool
    {
        $cacheSlotResultsForRequestedPage = $this->getCacheSlotResultsForRequestedPage();
        return isset($cacheSlotResultsForRequestedPage[$slotId][$mode]);
    }


    /**
     * @return array - a array that will be transformed as json HTML data block
     * to be included in a HTML page
     */
    public function getCacheSlotResultsAsHtmlDataBlockArray(): array
    {
        $htmlDataBlock = [];
        $cacheSlotResultsForRequestedPage = $this->getCacheSlotResultsForRequestedPage();
        if ($cacheSlotResultsForRequestedPage === null) {
            LogUtility::msg("No page slot results were found");
            return [];
        }
        foreach ($cacheSlotResultsForRequestedPage as $pageId => $resultByFormat) {
            foreach ($resultByFormat as $format => $result) {
                $modifiedDate = $result[self::DATE_MODIFIED];
                if ($modifiedDate !== null) {
                    $modifiedDate = Iso8601Date::createFromDateTime($modifiedDate)->toString();
                }
                $htmlDataBlock[$pageId][$format] = [
                    self::RESULT_STATUS => $result[self::RESULT_STATUS],
                    "mtime" => $modifiedDate
                ];
            }

        }
        return $htmlDataBlock;
    }


    public function isEmpty(): bool
    {
        return sizeof($this->cacheResults) === 0;
    }

    /**
     * @param string $dependencyName
     * @param string|array|null $attributes
     * @throws ExceptionCombo
     */
    public function addDependency(string $dependencyName, $attributes)
    {
        $this->getCacheManagerForCurrentSlot()->addDependency($dependencyName,$attributes);

    }

    public function getDepsForSlot(string $slotId)
    {
        return $this->slotCacheManagers[$slotId];
    }



    /**
     * @return CacheManagerForSlot
     * @throws ExceptionCombo
     */
    private function getCacheManagerForCurrentSlot(): CacheManagerForSlot
    {
        global $ID;
        if ($ID === null) {
            throw new ExceptionCombo("The actual slot is unknown (global ID is null). We cannot add a dependency");
        }
        return $this->getCacheManagerForSlot($ID);
    }

}
