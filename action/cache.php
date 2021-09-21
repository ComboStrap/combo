<?php

use ComboStrap\CacheManager;
use ComboStrap\CacheMedia;
use ComboStrap\DokuPath;
use ComboStrap\Iso8601Date;
use ComboStrap\PluginUtility;
use dokuwiki\Cache\CacheRenderer;
use dokuwiki\Menu\Item\Media;

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * Can we use the parser cache
 */
class action_plugin_combo_cache extends DokuWiki_Action_Plugin
{
    const COMBO_CACHE_PREFIX = "combo:cache:";

    /**
     * https://www.ietf.org/rfc/rfc2616.txt
     * To mark a response as "never expires," an origin server sends an Expires date approximately one year
     * from the time the response is sent.
     * HTTP/1.1 servers SHOULD NOT send Expires dates more than one year in the future.
     *
     * In seconds = 365*24*60*60
     */
    const INFINITE_MAX_AGE = 31536000;

    /**
     * Enable an infinite cache on image URL with the {@link CacheMedia::CACHE_BUSTER_KEY}
     * present
     */
    const CONF_SMART_IMAGE_CACHE_ENABLED = "smartImageCacheEnabled";

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
         * Control the HTTP cache of the image
         */
        $controller->register_hook('MEDIA_SENDFILE', 'BEFORE', $this, 'imageHTTPCache', array());

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

                $expirationDate = Iso8601Date::create($expirationStringDate)->getDateTime();
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
     * https://www.dokuwiki.org/devel:event:media_sendfile
     *
     * @param Doku_Event $event
     * @param $params
     */
    function imageHttpCache(Doku_Event $event, $params)
    {

        if (PluginUtility::getConfValue(self::CONF_SMART_IMAGE_CACHE_ENABLED, 1)) {
            /**
             * If there is the buster key, the infinite cache is on
             */
            if (isset($_GET[CacheMedia::CACHE_BUSTER_KEY])) {

                /**
                 * To avoid buggy code, we check that the value is not empty
                 */
                $cacheKey = $_GET[CacheMedia::CACHE_BUSTER_KEY];
                if (!empty($cacheKey)) {

                    /**
                     * Only for Image
                     */
                    $mediaPath = DokuPath::createMediaPathFromId($event->data["media"]);
                    if ($mediaPath->isImage()) {

                        /**
                         * in {@link sendFile()}, DokuWiki set the `Cache-Control` and
                         * may exit early / send a 304 (not modified) with the function {@link http_conditionalRequest()}
                         * Meaning that the AFTER event is never reached
                         * that we can't send a cache control as below
                         * header("Cache-Control: public, max-age=$infiniteMaxAge, s-maxage=$infiniteMaxAge");
                         *
                         * We use therefore the standard mechanism
                         */
                        $infiniteMaxAge = self::INFINITE_MAX_AGE;
                        $event->data["cache"] = $infiniteMaxAge;

                        /**
                         * Content location is mandatory for a 304
                         */
                        header("Content-Location", $mediaPath->getAbsolutePath());


                    }
                }

            }
        }

    }


}
