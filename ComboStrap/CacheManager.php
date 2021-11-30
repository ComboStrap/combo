<?php


namespace ComboStrap;


use dokuwiki\Cache\CacheParser;

class CacheManager
{

    const RESULT_STATUS = 'result';
    const DATE_MODIFIED = 'ftime';
    public const APPLICATION_COMBO_CACHE_JSON = "application/combo+cache+json";

    /**
     * Just an utility variable to tracks the slot processed
     * @var array the processed slot
     */
    private $cacheDataBySlots = array();


    /**
     * @return CacheManager
     */
    public static function get(): CacheManager
    {
        global $comboCacheManagerScript;
        if (empty($comboCacheManagerScript)) {
            self::init();
        }
        return $comboCacheManagerScript;
    }

    public static function init()
    {
        global $comboCacheManagerScript;
        $comboCacheManagerScript = new CacheManager();

    }

    /**
     * In test, we may run more than once
     * This function delete the cache manager
     * and is called when Dokuwiki close (ie {@link \action_plugin_combo_cache::close()})
     */
    public static function close()
    {

        global $comboCacheManagerScript;
        unset($comboCacheManagerScript);

    }

    /**
     * Keep track of the parsed bar (ie page in page)
     * @param $pageId
     * @param $result
     * @param CacheParser $cacheParser
     */
    public function addSlot($pageId, $result, $cacheParser)
    {
        if (!isset($this->cacheDataBySlots[$pageId])) {
            $this->cacheDataBySlots[$pageId] = [];
        }
        /**
         * Metadata and other rendering may occurs
         * recursively in one request
         *
         * We record only the first one because the second call one will use the first
         * one
         */
        if (!isset($this->cacheDataBySlots[$pageId][$cacheParser->mode])) {
            $date = null;
            if (file_exists($cacheParser->cache)) {
                $date = Iso8601Date::createFromTimestamp(filemtime($cacheParser->cache))->getDateTime();
            }
            $this->cacheDataBySlots[$pageId][$cacheParser->mode] = [
                self::RESULT_STATUS => $result,
                self::DATE_MODIFIED => $date
            ];
        }

    }

    public function getXhtmlRenderCacheSlotResults(): array
    {
        $xhtmlRenderResult = [];
        foreach ($this->cacheDataBySlots as $pageId => $modes) {
            foreach ($modes as $mode => $values) {
                if ($mode === "xhtml") {
                    $xhtmlRenderResult[$pageId] = $this->cacheDataBySlots[$pageId][$mode][self::RESULT_STATUS];
                }
            }
        }
        return $xhtmlRenderResult;
    }

    public function getCacheSlotResults(): array
    {
        return $this->cacheDataBySlots;
    }

    public function isCacheLogPresent($pageId, $mode): bool
    {
        return isset($this->cacheDataBySlots[$pageId][$mode]);
    }


    public function getCacheSlotResultsAsHtmlDataBlockArray()
    {
        $htmlDataBlock = [];
        foreach ($this->getCacheSlotResults() as $pageId => $resultByFormat) {
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


}
