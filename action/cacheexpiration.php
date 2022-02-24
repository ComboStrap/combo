<?php

use ComboStrap\CacheDependencies;
use ComboStrap\CacheExpirationDate;
use ComboStrap\CacheExpirationFrequency;
use ComboStrap\CacheLog;
use ComboStrap\CacheManager;
use ComboStrap\CacheMedia;
use ComboStrap\CacheMenuItem;
use ComboStrap\CacheReportHtmlDataBlockArray;
use ComboStrap\Cron;
use ComboStrap\Event;
use ComboStrap\ExceptionCombo;
use ComboStrap\FileSystems;
use ComboStrap\Http;
use ComboStrap\Iso8601Date;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PagePath;
use ComboStrap\PluginUtility;
use dokuwiki\Cache\CacheRenderer;

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * Can we use the parser cache
 *
 *
 *
 */
class action_plugin_combo_cacheexpiration extends DokuWiki_Action_Plugin
{


    const CANONICAL = CacheExpirationFrequency::CANONICAL;
    const SLOT_CACHE_EXPIRATION_EVENT = "slot-cache-expiration";
    const REQUESTED_ID = "requested-id";


    /**
     * @param Doku_Event_Handler $controller
     */
    function register(Doku_Event_Handler $controller)
    {


        /**
         * Page expiration feature
         */
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, 'slotCreateCacheExpiration', array());


        /**
         * process the Async event
         */
        $controller->register_hook(self::SLOT_CACHE_EXPIRATION_EVENT, 'AFTER', $this, 'handleSlotCacheExpiration');

    }


    /**
     *
     * Purge the cache if needed
     * @param Doku_Event $event
     * @param $params
     */
    function slotCreateCacheExpiration(Doku_Event $event, $params)
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
                        return;
                    }
                }
            }
        }

        $cacheManager = PluginUtility::getCacheManager();
        try {
            $shouldSlotExpire = $cacheManager->shouldSlotExpire($pageId);
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Error while trying to check if the slot ($pageId) should expired. Error: {$e->getMessage()}", self::CANONICAL);
            return;
        }
        if ($shouldSlotExpire) {
            Event::createEvent(
                self::SLOT_CACHE_EXPIRATION_EVENT,
                [
                    PagePath::getPersistentName() => $pageId,
                    self::REQUESTED_ID => PluginUtility::getRequestedWikiId()
                ]
            );
        }


    }

    public function handleSlotCacheExpiration($event)
    {
        $data = $event->data;
        $slotPath = $data[PagePath::getPersistentName()];
        $requestedId = $data[self::REQUESTED_ID];

        /**
         * The cache file may be dependent on the requested id
         * ie (@link CacheDependencies::OUTPUT_DEPENDENCIES}
         */
        global $ID;
        $keep = $ID;
        try {
            $ID = $requestedId;
            $slot = Page::createPageFromQualifiedPath($slotPath);

            /**
             * Calculate a new expiration date
             * And set it here because setting a new metadata
             * will make the cache unusable
             */
            $cacheExpirationDateMeta = CacheExpirationDate::createForPage($slot);
            $actualDate = $cacheExpirationDateMeta->getValue();
            $cacheExpirationFrequency = CacheExpirationFrequency::createForPage($slot)
                ->getValue();
            try {
                $newDate = Cron::getDate($cacheExpirationFrequency);
            } catch (ExceptionCombo $e) {
                LogUtility::msg("Error while calculating the new expiration date. Error: {$e->getMessage()}");
                return;
            }
            if ($newDate < $actualDate) {
                LogUtility::msg("The new calculated date cache expiration frequency ({$newDate->format(Iso8601Date::getFormat())}) is lower than the current date ({$actualDate->format(Iso8601Date::getFormat())})");
            }
            try {
                $cacheExpirationDateMeta
                    ->setValue($newDate)
                    ->persist();
            } catch (ExceptionCombo $e) {
                LogUtility::msg("Error while persisting the new expiration date. Error:{$e->getMessage()}");
                return;
            }

            /**
             * Cache deletion
             */
            $message = "Expiration Date has expired";
            CacheLog::deleteCacheIfExistsAndLog(
                $slot->getInstructionsDocument(),
                self::SLOT_CACHE_EXPIRATION_EVENT,
                $message);
            CacheLog::deleteCacheIfExistsAndLog(
                $slot->getHtmlDocument(),
                self::SLOT_CACHE_EXPIRATION_EVENT,
                $message);

            /**
             * Re-render
             */
            CacheLog::renderCacheAndLog(
                $slot->getHtmlDocument(),
                self::SLOT_CACHE_EXPIRATION_EVENT,
                $message);

        } finally {
            $ID = $keep;
        }

    }


}
