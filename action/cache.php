<?php

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

        $controller->register_hook('PARSER_CACHE_USE', 'AFTER', $this, 'logCacheResult', array());

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
    function logCacheResult(Doku_Event $event, $params)
    {

        /**
         * To log the cache used by bar
         */
        $data = $event->data;
        if ($data->mode == "xhtml") {

            /* @var CacheRenderer $data */
            $pageId = $data->page;
            $cached = $event->result;
            PluginUtility::getCacheManager()->addSlot($pageId, $cached);

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

    function close(Doku_Event $event, $params){
        \ComboStrap\CacheManager::close();
    }


}
