<?php


namespace ComboStrap;


class CacheManager
{

    /**
     * The meta key that has the expiration date
     */
    const DATE_CACHE_EXPIRED_META_KEY = "date_cache_expired";

    /**
     * Just an utility variable to tracks the slot processed
     * @var array the processed slot
     */
    private $slotsProcessed = array();


    /**
     * @return CacheManager
     */
    public static function get()
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
     * @param $renderCacheUsed
     */
    public function addSlot($pageId, $renderCacheUsed)
    {
        $this->slotsProcessed[$pageId] = $renderCacheUsed;
    }

    public function getSlotsOfPage()
    {
        return $this->slotsProcessed;
    }


}
