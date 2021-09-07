<?php

use ComboStrap\CacheManager;
use ComboStrap\Is8601Date;
use ComboStrap\PluginUtility;
use dokuwiki\Cache\CacheRenderer;

require_once(__DIR__ . '/../class/PluginUtility.php');

/**
 * Can we use the parser cache
 */
class action_plugin_combo_cache extends DokuWiki_Action_Plugin
{
    const COMBO_CACHE_PREFIX = "combo:cache:";

    /**
     * @param Doku_Event_Handler $controller
     */
    function register(Doku_Event_Handler $controller)
    {

        /**
         * Log the cache usage and also
         */
        $controller->register_hook('PARSER_CACHE_USE', 'AFTER', $this, 'logRenderCacheUsage', array());

        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, 'purgeIfNeeded', array());

        /**
         * To add the cache result in the header
         */
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'addMeta', array());

        /**
         * To reset the cache manager
         * between two run in the test
         */
        $controller->register_hook('DOKUWIKI_DONE', 'BEFORE', $this, 'close', array());

    }

    /**
     *
     * @param Doku_Event $event
     * @param $params
     */
    function logRenderCacheUsage(Doku_Event $event, $params)
    {

        /**
         * To log the cache used by bar
         */
        $data = $event->data;
        $mode = $data->mode;
        switch ($mode) {
            case "xhtml":
                /* @var CacheRenderer $data */
                $pageId = $data->page;
                $cached = $event->result;
                PluginUtility::getCacheManager()->addSlot($pageId, $cached);
                break;
        }

    }

    /**
     *
     * @param Doku_Event $event
     * @param $params
     */
    function purgeIfNeeded(Doku_Event $event, $params)
    {

        /**
         * To log the cache used by bar
         */
        $data = $event->data;
        $mode = $data->mode;
        switch ($mode) {
            case "i":
                /* @var \dokuwiki\Cache\CacheInstructions $data */

                $pageId = $data->page;
                $expirationStringDate = p_get_metadata($pageId, CacheManager::DATE_CACHE_EXPIRED_META_KEY, METADATA_DONT_RENDER);
                if ($expirationStringDate !== null) {

                    $expirationDate = Is8601Date::create($expirationStringDate)->getDateTime();
                    $actualDate = new DateTime();
                    if ($expirationDate < $actualDate) {
                        /**
                         * As seen in {@link Cache::makeDefaultCacheDecision()}
                         * We request a purge
                         */
                        $data->depends["purge"] = true;
                    }
                }
                break;
        }

    }

    /**
     *
     * @param Doku_Event $event
     * @param $params
     */
    function addMeta(Doku_Event $event, $params)
    {

        $cacheManager = PluginUtility::getCacheManager();
        $slots = $cacheManager->getSlotsOfPage();
        foreach ($slots as $slotId => $servedFromCache) {

            // Add cache information into the head meta
            // to test
            $event->data["meta"][] = array("name" => self::COMBO_CACHE_PREFIX . $slotId, "content" => var_export($servedFromCache, true));
        }

    }

    function close(Doku_Event $event, $params)
    {
        CacheManager::close();
    }


}
