<?php

use ComboStrap\CacheManager;
use ComboStrap\CacheMedia;
use ComboStrap\Http;
use ComboStrap\Iso8601Date;
use ComboStrap\Page;
use ComboStrap\PluginUtility;
use ComboStrap\TplUtility;
use dokuwiki\Cache\CacheRenderer;

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * Can we use the parser cache
 */
class action_plugin_combo_cache extends DokuWiki_Action_Plugin
{
    const COMBO_CACHE_PREFIX = "combo:cache:";


    const CANONICAL = "cache";
    const STATIC_SCRIPT_NAMES = ["/lib/exe/jquery.php", "/lib/exe/js.php", "/lib/exe/css.php"];

    /**
     * @param Doku_Event_Handler $controller
     */
    function register(Doku_Event_Handler $controller)
    {

        /**
         * Log the cache usage and also
         */
        $controller->register_hook('PARSER_CACHE_USE', 'AFTER', $this, 'logCacheUsage', array());

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

        /**
         * To delete the VARY on css.php, jquery.php, js.php
         */
        $controller->register_hook('INIT_LANG_LOAD', 'BEFORE', $this, 'deleteVaryFromStaticGeneratedResources', array());

        /**
         * To delete sidebar (cache) cache when a page was modified in a namespace
         * https://combostrap.com/sideslots
         */
        $controller->register_hook('IO_WIKIPAGE_WRITE', 'BEFORE', $this, 'sideSlotsCacheBursting', array());
    }

    /**
     *
     * @param Doku_Event $event
     * @param $params
     */
    function logCacheUsage(Doku_Event $event, $params)
    {

        /**
         * To log the cache used by bar
         * @var \dokuwiki\Cache\CacheParser $data
         */
        $data = $event->data;
        $result = $event->result;
        $pageId = $data->page;
        $cacheManager = PluginUtility::getCacheManager();
        $cacheManager->addSlot($pageId, $result, $data);


    }

    /**
     *
     * @param Doku_Event $event
     * @param $params
     */
    function purgeIfNeeded(Doku_Event $event, $params)
    {

        /**
         * No cache for all mode
         * (ie xhtml, instruction)
         */
        $data = &$event->data;
        $pageId = $data->page;

        /**
         * For whatever reason, the cache file of XHMTL
         * may be empty - No error found on the web server or the log.
         *
         * We just delete it then.
         *
         * It has been seen after the creation of a new page or a `move` of the page.
         */
        if ($data instanceof CacheRenderer) {
            if ($data->mode === "xhtml") {
                if (file_exists($data->cache)) {
                    if (filesize($data->cache) === 0) {
                        $data->depends["purge"] = true;
                    }
                }
            }
        }
        /**
         * Because of the recursive nature of rendering
         * inside dokuwiki, we just handle the first
         * rendering for a request.
         *
         * The first will be purged, the other one not
         * because they can use the first one
         */
        if (!PluginUtility::getCacheManager()->isCacheLogPresent($pageId, $data->mode)) {
            $expirationStringDate = p_get_metadata($pageId, CacheManager::DATE_CACHE_EXPIRATION_META_KEY, METADATA_DONT_RENDER);
            if ($expirationStringDate !== null) {

                $expirationDate = Iso8601Date::createFromString($expirationStringDate)->getDateTime();
                $actualDate = new DateTime();
                if ($expirationDate < $actualDate) {
                    /**
                     * As seen in {@link Cache::makeDefaultCacheDecision()}
                     * We request a purge
                     */
                    $data->depends["purge"] = true;
                }
            }
        }


    }

    /**
     * Add HTML meta to be able to debug
     * @param Doku_Event $event
     * @param $params
     */
    function addMeta(Doku_Event $event, $params)
    {

        $cacheManager = PluginUtility::getCacheManager();
        $slots = $cacheManager->getCacheSlotResults();
        foreach ($slots as $slotId => $modes) {

            $cachedMode = [];
            foreach ($modes as $mode => $values) {
                if ($values[CacheManager::RESULT_STATUS] === true) {
                    $metaContentData = $mode;
                    if (!PluginUtility::isTest()) {
                        /**
                         * @var DateTime $dateModified
                         */
                        $dateModified = $values[CacheManager::DATE_MODIFIED];
                        $metaContentData .= ":" . $dateModified->format('Y-m-d\TH:i:s');
                    }
                    $cachedMode[] = $metaContentData;
                }
            }

            if (sizeof($cachedMode) === 0) {
                $value = "nocache";
            } else {
                sort($cachedMode);
                $value = implode(",", $cachedMode);
            }

            // Add cache information into the head meta
            // to test
            $event->data["meta"][] = array("name" => self::COMBO_CACHE_PREFIX . $slotId, "content" => hsc($value));
        }

    }

    function close(Doku_Event $event, $params)
    {
        CacheManager::close();
    }


    /**
     * Delete the Vary header
     * @param Doku_Event $event
     * @param $params
     */
    public static function deleteVaryFromStaticGeneratedResources(Doku_Event $event, $params)
    {

        $script = $_SERVER["SCRIPT_NAME"];
        if (in_array($script, self::STATIC_SCRIPT_NAMES)) {
            // To be extra sure, they must have the buster key
            if (isset($_REQUEST[CacheMedia::CACHE_BUSTER_KEY])) {
                self::deleteVaryHeader();
            }
        }

    }

    /**
     *
     * No Vary: Cookie
     * Introduced at
     * https://github.com/splitbrain/dokuwiki/issues/1594
     * But cache problem at:
     * https://github.com/splitbrain/dokuwiki/issues/2520
     *
     */
    public static function deleteVaryHeader(): void
    {
        if (PluginUtility::getConfValue(action_plugin_combo_staticresource::CONF_STATIC_CACHE_ENABLED, 1)) {
            Http::removeHeaderIfPresent("Vary");
        }
    }

    function sideSlotsCacheBursting($event)
    {

        global $conf;

        $sidebars = [
            $conf['sidebar']
        ];

        /**
         * @see {@link \ComboStrap\TplConstant::CONF_SIDEKICK}
         */
        $loaded = PluginUtility::loadStrapUtilityTemplateIfPresentAndSameVersion();
        if ($loaded) {

            $sideKickSlotPageName = TplUtility::getSideKickSlotPageName();
            if (!empty($sideKickSlotPageName)) {
                $sidebars[] = $sideKickSlotPageName;
            }

        }


        /**
         * Delete the cache for the sidebar
         */
        foreach ($sidebars as $sidebarRelativePath) {

            $page = Page::createPageFromNonQualifiedPath($sidebarRelativePath);
            //$page->deleteCache();

        }

    }

}
