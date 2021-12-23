<?php

use ComboStrap\CacheMedia;
use ComboStrap\DokuPath;
use ComboStrap\File;
use ComboStrap\FileSystems;
use ComboStrap\Http;
use ComboStrap\HttpResponse;
use ComboStrap\JavascriptLibrary;
use ComboStrap\LocalPath;
use ComboStrap\Path;
use ComboStrap\PluginUtility;
use dokuwiki\Utf8\PhpString;

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * Modify the serving of static resource via fetch.php
 */
class action_plugin_combo_staticresource extends DokuWiki_Action_Plugin
{


    /**
     * https://www.ietf.org/rfc/rfc2616.txt
     * To mark a response as "never expires," an origin server sends an Expires date approximately one year
     * from the time the response is sent.
     * HTTP/1.1 servers SHOULD NOT send Expires dates more than one year in the future.
     *
     * In seconds = 365*24*60*60
     */
    const INFINITE_MAX_AGE = 31536000;

    const CANONICAL = "cache";

    /**
     * Enable an infinite cache on static resources (image, script, ...) with a {@link CacheMedia::CACHE_BUSTER_KEY}
     */
    public const CONF_STATIC_CACHE_ENABLED = "staticCacheEnabled";


    /**
     * @param Doku_Event_Handler $controller
     */
    function register(Doku_Event_Handler $controller)
    {

        /**
         * Redirect the combo resources to the good file path
         * https://www.dokuwiki.org/devel:event:fetch_media_status
         */
        $controller->register_hook('FETCH_MEDIA_STATUS', 'BEFORE', $this, 'handleMediaStatus', array());

        /**
         * Serve the image and static resources with HTTP cache control
         * https://www.dokuwiki.org/devel:event:media_sendfile
         */
        $controller->register_hook('MEDIA_SENDFILE', 'BEFORE', $this, 'handleSendFile', array());


    }


    function handleMediaStatus(Doku_Event $event, $params)
    {

        if (!isset($_GET[DokuPath::WIKI_FS_TYPE])) {
            return;
        }
        $type = $_GET[DokuPath::WIKI_FS_TYPE];
        if ($type !== DokuPath::RESOURCE_TYPE) {
            // The other resources have ACL
            // and this endpoint is normally only for
            $event->data['status'] = HttpResponse::STATUS_NOT_AUTHORIZED;
            return;
        }
        $mediaId = $event->data['media'];
        $mediaPath = DokuPath::createDokuPath($mediaId, $type);
        $event->data['file'] = $mediaPath->toAbsolutePath()->toString();
        if (FileSystems::exists($mediaPath)) {
            $event->data['status'] = HttpResponse::STATUS_ALL_GOOD;
            $event->data['statusmessage'] = '';
            $event->data['mime'] = $mediaPath->getMime();
        }

    }

    function handleSendFile(Doku_Event $event, $params)
    {

        $mediaId = $event->data["media"];

        /**
         * Do we send this file
         */
        $isStaticFileManaged = false;

        /**
         * Combo Media
         * (Static file from the combo resources are always taken over)
         */
        if (isset($_GET[DokuPath::WIKI_FS_TYPE])) {

            $isStaticFileManaged = $_GET[DokuPath::WIKI_FS_TYPE] === DokuPath::RESOURCE_TYPE;

        }

        /**
         * DokuWiki Resource media
         */
        if (!$isStaticFileManaged) {

            /**
             * If there is the buster key, the infinite cache is on
             */
            if (isset($_GET[CacheMedia::CACHE_BUSTER_KEY])) {

                /**
                 * To avoid buggy code, we check that the value is not empty
                 */
                $cacheKey = $_GET[CacheMedia::CACHE_BUSTER_KEY];
                if (!empty($cacheKey)) {

                    if (PluginUtility::getConfValue(self::CONF_STATIC_CACHE_ENABLED, 1)) {

                        $dokuPath = DokuPath::createMediaPathFromId($mediaId);
                        if ($dokuPath->isPublic()) {
                            /**
                             * Only for public media
                             */
                            $isStaticFileManaged = true;
                        }

                    }
                }
            }
        }

        if (!$isStaticFileManaged) {
            return;
        }

        /**
         * We take over the complete {@link sendFile()} function and exit
         *
         * in {@link sendFile()}, DokuWiki set the `Cache-Control` and
         * may exit early / send a 304 (not modified) with the function {@link http_conditionalRequest()}
         * Meaning that the AFTER event is never reached
         * that we can't send a cache control as below
         * header("Cache-Control: public, max-age=$infiniteMaxAge, s-maxage=$infiniteMaxAge");
         *
         * We take the control over then
         */

        /**
         * Send the file
         */
        $originalFile = $event->data["orig"]; // the original file
        $physicalFile = $event->data["file"]; // the file modified
        if (empty($physicalFile)) {
            $physicalFile = $originalFile;
        }
        $mediaToSend = LocalPath::createFromPath($physicalFile);

        /**
         * The mime
         */
        $mime = $mediaToSend->getMime();
        header("Content-Type: {$mime}");

        /**
         * The cache instructions
         */
        $infiniteMaxAge = self::INFINITE_MAX_AGE;
        $expires = time() + $infiniteMaxAge;
        header('Expires: ' . gmdate("D, d M Y H:i:s", $expires) . ' GMT');
        $cacheControlDirective = ["public", "max-age=$infiniteMaxAge", "immutable"];
        if ($mediaToSend->getExtension() === "js") {
            // if a SRI is given and that a proxy is
            // reducing javascript, it will not match
            // no-transform will avoid that
            $cacheControlDirective[] = "no-transform";
        }
        header("Cache-Control: " . implode(", ", $cacheControlDirective));
        Http::removeHeaderIfPresent("Pragma");

        /**
         * The Etag cache validator
         *
         * Dokuwiki {@link http_conditionalRequest()} uses only the datetime of
         * the file but we need to add the parameters also because they
         * are generated image
         *
         * Last-Modified is not needed for the same reason
         *
         */
        $etag = self::getEtagValue($mediaToSend, $_REQUEST);
        header("ETag: $etag");

        /**
         * Conditional Request ?
         * We don't check on HTTP_IF_MODIFIED_SINCE because this is useless
         */
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            $ifNoneMatch = stripslashes($_SERVER['HTTP_IF_NONE_MATCH']);
            if ($ifNoneMatch && $ifNoneMatch === $etag) {

                HttpResponse::create(HttpResponse::STATUS_NOT_MODIFIED)
                    ->setEvent($event)
                    ->setCanonical(self::CANONICAL)
                    ->sendMessage("File not modified");
                return;
            }
        }


        /**
         * Download or display feature
         * (Taken over from SendFile)
         */
        $download = $event->data["download"];
        if ($download && $mime !== "image/svg+xml") {
            header('Content-Disposition: attachment;' . rfc2231_encode(
                    'filename', PhpString::basename($originalFile)) . ';'
            );
        } else {
            header('Content-Disposition: inline;' . rfc2231_encode(
                    'filename', PhpString::basename($originalFile)) . ';'
            );
        }

        /**
         * The vary header avoid caching
         * Delete it
         */
        action_plugin_combo_cache::deleteVaryHeader();

        /**
         * Use x-sendfile header to pass the delivery to compatible web servers
         * (Taken over from SendFile)
         */
        http_sendfile($mediaToSend->toAbsolutePath()->toString());

        /**
         * Send the file
         */
        $filePointer = @fopen($mediaToSend->toAbsolutePath()->toString(), "rb");
        if ($filePointer) {
            http_rangeRequest($filePointer, FileSystems::getSize($mediaToSend), $mime->toString());
            /**
             * For test because the {@link HttpResponse} cannot yet send an blob
             */
            if(PluginUtility::isDevOrTest()) {
                $testRequest = TestRequest::getRunning();
                if ($testRequest !== null) {
                    $testRequest->addData(HttpResponse::EXIT_KEY, "File Send");
                }
            }
        } else {
            HttpResponse::create(HttpResponse::STATUS_INTERNAL_ERROR)
                ->sendMessage("Could not read $mediaToSend - bad permissions?");
        }

    }

    /**
     * @param Path $mediaFile
     * @param Array $properties - the query properties
     * @return string
     */
    public
    static function getEtagValue(Path $mediaFile, array $properties): string
    {
        $etagString = FileSystems::getModifiedTime($mediaFile)->format('r');
        ksort($properties);
        foreach ($properties as $key => $value) {
            /**
             * Media is already on the URL
             * tok is just added when w and h are on the url
             * Buster is the timestamp
             */
            if (in_array($key, ["media", "tok", CacheMedia::CACHE_BUSTER_KEY])) {
                continue;
            }
            /**
             * If empty means not used
             */
            if (empty($value)) {
                continue;
            }
            $etagString .= "$key=$value";
        }
        return '"' . md5($etagString) . '"';
    }


}
