<?php

use ComboStrap\CacheMedia;
use ComboStrap\DokuPath;
use ComboStrap\ExceptionCombo;
use ComboStrap\FileSystems;
use ComboStrap\Http;
use ComboStrap\HttpResponse;
use ComboStrap\Identity;
use ComboStrap\LocalPath;
use ComboStrap\LogUtility;
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

        if (!isset($_GET[DokuPath::DRIVE_ATTRIBUTE])) {
            return;
        }
        $drive = $_GET[DokuPath::DRIVE_ATTRIBUTE];
        if (!in_array($drive, DokuPath::DRIVES)) {
            // The other resources have ACL
            // and this endpoint is normally only for
            $event->data['status'] = HttpResponse::STATUS_NOT_AUTHORIZED;
            return;
        }
        $mediaId = $event->data['media'];
        $mediaPath = DokuPath::createDokuPath($mediaId, $drive);
        $event->data['file'] = $mediaPath->toLocalPath()->toAbsolutePath()->toString();
        if (FileSystems::exists($mediaPath)) {
            $event->data['status'] = HttpResponse::STATUS_ALL_GOOD;
            $event->data['statusmessage'] = '';
            $event->data['mime'] = $mediaPath->getMime();
        }
        if ($drive === DokuPath::CACHE_DRIVE) {
            $event->data['download'] = false;
            if (!Identity::isManager()) {
                $event->data['status'] = HttpResponse::STATUS_NOT_AUTHORIZED;
            }
        }

    }

    function handleSendFile(Doku_Event $event, $params)
    {


        /**
         * If there is no buster key, the infinite cache is off
         */
        $busterKey = $_GET[CacheMedia::CACHE_BUSTER_KEY];
        if ($busterKey === null) {
            return;
        }

        /**
         * The media to send
         */
        $originalFile = $event->data["orig"]; // the original file
        $physicalFile = $event->data["file"]; // the file modified
        if (empty($physicalFile)) {
            $physicalFile = $originalFile;
        }
        $mediaToSend = LocalPath::createFromPath($physicalFile);
        if (!FileSystems::exists($mediaToSend)) {
            return;
        }

        /**
         * Combo Media
         * (Static file from the combo resources are always taken over)
         */
        $drive = $_GET[DokuPath::DRIVE_ATTRIBUTE];
        if ($drive === null) {

            $confValue = PluginUtility::getConfValue(self::CONF_STATIC_CACHE_ENABLED, 1);
            if (!$confValue) {
                return;
            }

            try {
                $dokuPath = $mediaToSend->toDokuPath();
            } catch (ExceptionCombo $e) {
                // not a dokuwiki file ?
                LogUtility::msg("Error: {$e->getMessage()}");
                return;
            }
            if (!$dokuPath->isPublic()) {
                return; // Infinite static is only for public media
            }

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
        $mime = $mediaToSend->getMime();
        $download = $event->data["download"];
        if ($download && $mime->toString() !== "image/svg+xml") {
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
             * The {@link http_rangeRequest} exit not on test
             * Trying to stop the dokuwiki processing of {@link sendFile()}
             * Until {@link HttpResponse} can send resource
             * TODO: integrate it in {@link HttpResponse}
             */
            if (PluginUtility::isDevOrTest()) {
                /**
                 * Add test info into the request
                 */
                $testRequest = TestRequest::getRunning();

                if ($testRequest !== null) {
                    $testRequest->addData(HttpResponse::EXIT_KEY, "File Send");
                }
                if ($event !== null) {
                    $event->stopPropagation();
                    $event->preventDefault();
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
