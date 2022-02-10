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
    const COMBO_CACHE_PREFIX = "combo:cache:";


    const CANONICAL = "cache";
    const STATIC_SCRIPT_NAMES = ["/lib/exe/jquery.php", "/lib/exe/js.php", "/lib/exe/css.php"];

    /**
     * @var string[]
     */
    private static $sideSlotNames;


    private static function getSideSlotNames(): array
    {
        if (self::$sideSlotNames === null) {
            global $conf;

            self::$sideSlotNames = [
                $conf['sidebar']
            ];

            /**
             * @see {@link \ComboStrap\TplConstant::CONF_SIDEKICK}
             */
            $sideKickSlotPageName = Site::getSideKickSlotPageName();
            if (!empty($sideKickSlotPageName)) {
                self::$sideSlotNames[] = $sideKickSlotPageName;
            }


        }
        return self::$sideSlotNames;
    }

    private static function removeSideSlotCache()
    {
        $sidebars = self::getSideSlotNames();


        /**
         * Delete the cache for the sidebar
         */
        foreach ($sidebars as $sidebarRelativePath) {

            $page = Page::createPageFromNonQualifiedPath($sidebarRelativePath);
            $page->deleteCache();

        }
    }

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
         * Page expiration feature
         */
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, 'pageCacheExpiration', array());

        /**
         * To add the cache result in the HTML
         */
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'addCacheLogHtmlDataBlock', array());


        /**
         * To delete the VARY on css.php, jquery.php, js.php
         */
        $controller->register_hook('INIT_LANG_LOAD', 'BEFORE', $this, 'deleteVaryFromStaticGeneratedResources', array());

        /**
         * To delete sidebar (cache) cache when a page was modified in a namespace
         * https://combostrap.com/sideslots
         */
        $controller->register_hook(MetadataDokuWikiStore::PAGE_METADATA_MUTATION_EVENT, 'AFTER', $this, 'sideSlotsCacheBurstingForMetadataMutation', array());
        $controller->register_hook('IO_WIKIPAGE_WRITE', 'BEFORE', $this, 'sideSlotsCacheBurstingForPageCreationAndDeletion', array());

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
     *
     * Purge the cache if needed
     * @param Doku_Event $event
     * @param $params
     */
    function pageCacheExpiration(Doku_Event $event, $params)
    {

        /**
         * No cache for all mode
         * (ie xhtml, instruction)
         */
        $data = &$event->data;
        $pageId = $data->page;

        /**
         * For whatever reason, the cache file of XHTML
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
         * because they can't use the first one
         */
        if (!PluginUtility::getCacheManager()->isCacheResultPresentForSlot($pageId, $data->mode)) {
            $page = Page::createPageFromId($pageId);
            $cacheExpirationFrequency = $page->getCacheExpirationFrequency();
            if ($cacheExpirationFrequency === null) {
                return;
            }

            $expirationDate = CacheExpirationDate::createForPage($page)
                ->getValue();

            if ($expirationDate === null) {
                try {
                    $expirationDate = Cron::getDate($cacheExpirationFrequency);
                    $page->setCacheExpirationDate($expirationDate);
                } catch (ExceptionCombo $e) {
                    LogUtility::msg("The cache expiration frequency ($cacheExpirationFrequency) is not a valid cron expression");
                }
            }
            if ($expirationDate !== null) {

                $actualDate = new DateTime();
                if ($expirationDate < $actualDate) {
                    /**
                     * As seen in {@link Cache::makeDefaultCacheDecision()}
                     * We request a purge
                     */
                    $data->depends["purge"] = true;

                    /**
                     * Calculate a new expiration date
                     */
                    try {
                        $newDate = Cron::getDate($cacheExpirationFrequency);
                        if ($newDate < $actualDate) {
                            LogUtility::msg("The new calculated date cache expiration frequency ({$newDate->format(Iso8601Date::getFormat())}) is lower than the current date ({$actualDate->format(Iso8601Date::getFormat())})");
                        }
                        $page->setCacheExpirationDate($newDate);
                    } catch (ExceptionCombo $e) {
                        LogUtility::msg("The cache expiration frequency ($cacheExpirationFrequency) is not a value cron expression");
                    }
                }
            }
        }


    }

    /**
     * Add HTML meta to be able to debug
     * @param Doku_Event $event
     * @param $params
     */
    function addCacheLogHtmlDataBlock(Doku_Event $event, $params)
    {

        if(!PluginUtility::isRenderingRequestedPageProcess()){
            return;
        }
        $cacheSlotResults = CacheReportHtmlDataBlockArray::get();
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

    function sideSlotsCacheBurstingForMetadataMutation($event)
    {

        $data = $event->data;
        /**
         * The side slot cache is deleted only when the
         * below property are updated
         */
        $descriptionProperties = [PageTitle::PROPERTY_NAME, ResourceName::PROPERTY_NAME, PageH1::PROPERTY_NAME, PageDescription::DESCRIPTION_PROPERTY];
        if (!in_array($data["name"], $descriptionProperties)) return;

        self::removeSideSlotCache();

    }

    /**
     * @param $event
     * @throws Exception
     * @link https://www.dokuwiki.org/devel:event:io_wikipage_write
     */
    function sideSlotsCacheBurstingForPageCreationAndDeletion($event)
    {

        $data = $event->data;
        $pageName = $data[2];

        /**
         * Modification to the side slot is not processed further
         */
        if (in_array($pageName, self::getSideSlotNames())) return;

        /**
         * Pointer to see if we need to delete the cache
         */
        $doWeNeedToDeleteTheSideSlotCache = false;

        /**
         * File creation
         *
         * ```
         * Page creation may be detected by checking if the file already exists and the revision is false.
         * ```
         * From https://www.dokuwiki.org/devel:event:io_wikipage_write
         *
         */
        $rev = $data[3];
        $filePath = $data[0][0];
        $file = File::createFromPath($filePath);
        if (!$file->exists() && $rev === false) {
            $doWeNeedToDeleteTheSideSlotCache = true;
        }

        /**
         * File deletion
         * (No content)
         *
         * ```
         * Page deletion may be detected by checking for empty page content.
         * On update to an existing page this event is called twice, once for the transfer of the old version to the attic (rev will have a value)
         * and once to write the new version of the page into the wiki (rev is false)
         * ```
         * From https://www.dokuwiki.org/devel:event:io_wikipage_write
         */
        $append = $data[0][2];
        if (!$append) {

            $content = $data[0][1];
            if (empty($content) && $rev === false) {
                // Deletion
                $doWeNeedToDeleteTheSideSlotCache = true;
            }

        }

        if ($doWeNeedToDeleteTheSideSlotCache) self::removeSideSlotCache();

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
        array_splice($event->data['items'], -1, 0, array(new CacheMenuItem()));


    }

}
