<?php

use ComboStrap\CacheExpirationDate;
use ComboStrap\CacheExpirationFrequency;
use ComboStrap\CacheLog;
use ComboStrap\Cron;
use ComboStrap\Event;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExecutionContext;
use ComboStrap\Iso8601Date;
use ComboStrap\LogUtility;
use ComboStrap\MarkupCacheDependencies;
use ComboStrap\MarkupPath;
use ComboStrap\PagePath;
use dokuwiki\Cache\CacheRenderer;

require_once(__DIR__ . '/../vendor/autoload.php');

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
         * Process the Async event
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

        $executionContext = ExecutionContext::getActualOrCreateFromEnv();
        $cacheManager = $executionContext->getCacheManager();
        $shouldSlotExpire = $cacheManager->shouldSlotExpire($pageId);
        if ($shouldSlotExpire) {
            try {
                $requestedWikiId = $executionContext->getRequestedPath()->getWikiId();
            } catch (ExceptionNotFound $e) {
                LogUtility::internalError("Cache expiration: The requested path could not be determined, default context path was set instead.");
                $requestedWikiId = $executionContext->getContextPath()->getWikiId();
            }
            Event::createEvent(
                self::SLOT_CACHE_EXPIRATION_EVENT,
                [
                    PagePath::getPersistentName() => $pageId,
                    self::REQUESTED_ID => $requestedWikiId
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
         * ie (@link MarkupCacheDependencies::OUTPUT_DEPENDENCIES}
         */
        global $ID;
        $keep = $ID;
        try {
            $ID = $requestedId;
            $slot = MarkupPath::createPageFromAbsoluteId($slotPath);

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
            } catch (ExceptionCompile $e) {
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
            } catch (ExceptionCompile $e) {
                LogUtility::msg("Error while persisting the new expiration date. Error:{$e->getMessage()}");
                return;
            }

            /**
             * Cache deletion
             */
            $message = "Expiration Date has expired";
            $outputDocument = $slot->getInstructionsDocument();
            CacheLog::deleteCacheIfExistsAndLog(
                $outputDocument,
                self::SLOT_CACHE_EXPIRATION_EVENT,
                $message);
            $fetcher = $slot->createHtmlFetcherWithItselfAsContextPath();
            CacheLog::deleteCacheIfExistsAndLog(
                $fetcher,
                self::SLOT_CACHE_EXPIRATION_EVENT,
                $message);

            /**
             * Re-render
             */
            $fetcher2 = $slot->createHtmlFetcherWithItselfAsContextPath();
            CacheLog::renderCacheAndLog(
                $fetcher2,
                self::SLOT_CACHE_EXPIRATION_EVENT,
                $message);


        } finally {
            $ID = $keep;
        }

    }


}
