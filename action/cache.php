<?php

use ComboStrap\CacheManager;
use ComboStrap\CacheMenuItem;
use ComboStrap\CacheReportHtmlDataBlockArray;
use ComboStrap\ExecutionContext;
use ComboStrap\Http;
use ComboStrap\Identity;
use ComboStrap\IFetcher;
use ComboStrap\PluginUtility;
use ComboStrap\SiteConfig;

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
        if (empty($slotId)) {
            // on edit mode, the page is emtpy
            return;
        }
        $cacheReporter = CacheManager::getFromContextExecution()->getCacheResultsForSlot($slotId);
        $cacheReporter->setData($event);


    }


    /**
     * Add cache data to the rendered html page
     * @param Doku_Event $event
     * @param $params
     */
    function addCacheLogHtmlDataBlock(Doku_Event $event, $params)
    {

        $isPublic = ExecutionContext::getActualOrCreateFromEnv()
            ->isPublicationAction();
        if (!$isPublic) {
            return;
        }
        $cacheSlotResults = CacheReportHtmlDataBlockArray::getFromContext();
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
            if (isset($_REQUEST[IFetcher::CACHE_BUSTER_KEY])) {
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
        if (SiteConfig::getConfValue(action_plugin_combo_staticresource::CONF_STATIC_CACHE_ENABLED, 1)) {
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
        $exists = $INFO['exists'] ?? null;
        if (!$exists) {
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
