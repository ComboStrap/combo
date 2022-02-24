<?php

use ComboStrap\CacheExpirationDate;
use ComboStrap\CacheManager;
use ComboStrap\CacheMedia;
use ComboStrap\CacheMenuItem;
use ComboStrap\CacheReportHtmlDataBlockArray;
use ComboStrap\Cron;
use ComboStrap\ExceptionCombo;
use ComboStrap\File;
use ComboStrap\Http;
use ComboStrap\Identity;
use ComboStrap\Iso8601Date;
use ComboStrap\LogUtility;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\Page;
use ComboStrap\PageDescription;
use ComboStrap\PageH1;
use ComboStrap\PageTitle;
use ComboStrap\PluginUtility;
use ComboStrap\ResourceName;
use ComboStrap\Site;
use dokuwiki\Cache\CacheRenderer;

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * Can we use the parser cache
 *
 *
 *
 */
class action_plugin_combo_cache extends DokuWiki_Action_Plugin
{



    const CANONICAL = "cache";
    const STATIC_SCRIPT_NAMES = ["/lib/exe/jquery.php", "/lib/exe/js.php", "/lib/exe/css.php"];




    /**
     * @param Doku_Event_Handler $controller
     */
    function register(Doku_Event_Handler $controller)
    {

        /**
         * Create a {@link \ComboStrap\CacheResult}
         */
        $controller->register_hook('PARSER_CACHE_USE', 'AFTER', $this, 'createCacheResult', array());


        /**
         * To add the cache result in the HTML
         */
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'addCacheLogHtmlDataBlock', array());


        /**
         * To delete the VARY on css.php, jquery.php, js.php
         */
        $controller->register_hook('INIT_LANG_LOAD', 'BEFORE', $this, 'deleteVaryFromStaticGeneratedResources', array());


        /**
         * Add a icon in the page tools menu
         * https://www.dokuwiki.org/devel:event:menu_items_assembly
         */
        $controller->register_hook('MENU_ITEMS_ASSEMBLY', 'AFTER', $this, 'addMenuItem');

    }

    /**
     *
     * @param Doku_Event $event
     * @param $params
     */
    function createCacheResult(Doku_Event $event, $params)
    {

        /**
         * To log the cache used by bar
         * @var \dokuwiki\Cache\CacheParser $data
         */
        $data = $event->data;
        $slotId = $data->page;
        $cacheReporter = CacheManager::getOrCreate()->getCacheResultsForSlot($slotId);
        $cacheReporter->setData($event);


    }


    /**
     * Add cache data to the rendered html page
     * @param Doku_Event $event
     * @param $params
     */
    function addCacheLogHtmlDataBlock(Doku_Event $event, $params)
    {

        if(!PluginUtility::isRenderingRequestedPageProcess()){
            return;
        }
        $cacheSlotResults = CacheReportHtmlDataBlockArray::getFromRuntime();
        $cacheJson = \ComboStrap\Json::createFromArray($cacheSlotResults);

        if (PluginUtility::isDevOrTest()) {
            $result = $cacheJson->toPrettyJsonString();
        } else {
            $result = $cacheJson->toMinifiedJsonString();
        }

        $event->data["script"][] = array(
            "type" => CacheReportHtmlDataBlockArray::APPLICATION_COMBO_CACHE_JSON,
            "_data" => $result,
        );


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




    function addMenuItem(Doku_Event $event, $param)
    {

        /**
         * The `view` property defines the menu that is currently built
         * https://www.dokuwiki.org/devel:menus
         * If this is not the page menu, return
         */
        if ($event->data['view'] != 'page') return;

        global $INFO;
        if (!$INFO['exists']) {
            return;
        }
        /**
         * Cache is for manager
         */
        if (!Identity::isManager()) {
            return;
        }
        array_splice($event->data['items'], -1, 0, array(new CacheMenuItem()));


    }

}
