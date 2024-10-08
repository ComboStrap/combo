<?php

use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExecutionContext;
use ComboStrap\FetcherRaster;
use ComboStrap\FileSystems;
use ComboStrap\Http;
use ComboStrap\HttpResponse;
use ComboStrap\HttpResponseStatus;
use ComboStrap\Identity;
use ComboStrap\IFetcher;
use ComboStrap\LocalPath;
use ComboStrap\LogUtility;
use ComboStrap\Mime;
use ComboStrap\Path;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\SiteConfig;
use ComboStrap\Web\Url;
use ComboStrap\Web\UrlRewrite;
use ComboStrap\WikiPath;
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
     * Enable an infinite cache on static resources (image, script, ...) with a {@link IFetcher::CACHE_BUSTER_KEY}
     */
    public const CONF_STATIC_CACHE_ENABLED = "staticCacheEnabled";
    const NO_TRANSFORM = "no-transform";


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

    /**
     * @param Doku_Event $event
     * https://www.dokuwiki.org/devel:event:fetch_media_status
     */
    function handleMediaStatus(Doku_Event $event, $params)
    {

        $drive = $_GET[WikiPath::DRIVE_ATTRIBUTE] ?? null;
        $fetcher = $_GET[IFetcher::FETCHER_KEY] ?? null;
        if ($drive === null && $fetcher === null) {
            return;
        }
        if ($fetcher === FetcherRaster::CANONICAL) {
            // not yet implemented
            return;
        }

        /**
         * Security
         */
        if ($drive === WikiPath::CACHE_DRIVE) {
            $event->data['download'] = false;
            if (!Identity::isManager()) {
                $event->data['status'] = HttpResponseStatus::NOT_AUTHORIZED;
                return;
            }
        }


        /**
         * Add the extra attributes
         */
        $fetchUrl = Url::createFromGetOrPostGlobalVariable();
        $executionContext = ExecutionContext::getActualOrCreateFromEnv();
        try {

            $fetcher = $executionContext->createPathMainFetcherFromUrl($fetchUrl);
            $fetchPath = $fetcher->getFetchPath();
            $filePath = $fetchPath->toAbsoluteId();
            /**
             * Bug
             *
             * We have a bug with {@link WikiPath::toValidAbsolutePath} that uses {@link cleanID()}.
             * `/` becomes `_` if the useSlash conf is not enabled with web server useRewrite
             * and the file then does not exists.
             *
             * Furthermore, passing a file that does not exist, will break dokuwiki and returns a 500
             */
            if (!file_exists($fetchPath)) {
                $useRewrite = Site::getUrlRewrite();
                $useSlash = Site::getUseSlash();
                if($useRewrite == UrlRewrite::WEB_SERVER_REWRITE && !$useSlash){
                    $executionContext
                        ->response()
                        ->setStatus(400)
                        ->setBodyAsJsonMessage("The `useSlash` configuration should be enabled when the `useRewrite` is `htaccess` (ie web server), otherwise the file is not found.")
                        ->end();
                } else {
                    $executionContext
                        ->response()
                        ->setStatus(404)
                        ->end();
                }
                return;
            }
            $event->data['file'] = $filePath;
            $event->data['status'] = HttpResponseStatus::ALL_GOOD;
            $mime = $fetcher->getMime();
            $event->data["mime"] = $mime->toString();
            /**
             * TODO: set download as parameter of the fetch url
             */
            if ($mime->isImage() || in_array($mime->toString(), [Mime::JAVASCRIPT, Mime::CSS])) {
                $event->data['download'] = false;
            } else {
                $event->data['download'] = true;
            }
            $event->data['statusmessage'] = '';
        } catch (\Exception $e) {

            $executionContext
                ->response()
                ->setException($e)
                ->setStatusAndBodyFromException($e)
                ->end();

            //$event->data['file'] = WikiPath::createComboResource("images:error-bad-format.svg")->toLocalPath()->toAbsolutePath()->toQualifiedId();
            $event->data['file'] = "error.json";
            $event->data['statusmessage'] = $e->getMessage();
            //$event->data['status'] = $httpResponse->getStatus();
            $event->data['mime'] = Mime::JSON;


        }

    }

    function handleSendFile(Doku_Event $event, $params)
    {

        if (ExecutionContext::getActualOrCreateFromEnv()->response()->hasEnded()) {
            // when there is an error for instance
            return;
        }
        /**
         * If there is no buster key, the infinite cache is off
         */
        $busterKey = $_GET[IFetcher::CACHE_BUSTER_KEY] ?? null;
        if ($busterKey === null) {
            return;
        }

        /**
         * The media to send
         */
        $originalFile = $event->data["orig"]; // the original file
        $physicalFile = $event->data["file"]; // the file modified or the file to send
        if (empty($physicalFile)) {
            $physicalFile = $originalFile;
        }
        $mediaToSend = LocalPath::createFromPathString($physicalFile);
        if (!FileSystems::exists($mediaToSend)) {
            if (PluginUtility::isDevOrTest()) {
                LogUtility::internalError("The media ($mediaToSend) does not exist", self::CANONICAL);
            }
            return;
        }

        /**
         * Combo Media
         * (Static file from the combo resources are always taken over)
         */
        $drive = $_GET[WikiPath::DRIVE_ATTRIBUTE] ?? null;
        if ($drive === null) {

            $confValue = SiteConfig::getConfValue(self::CONF_STATIC_CACHE_ENABLED, 1);
            if (!$confValue) {
                return;
            }

            try {
                $dokuPath = $mediaToSend->toWikiPath();
            } catch (ExceptionCompile $e) {
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
        try {
            if ($mediaToSend->getExtension() === "js") {
                // if a SRI is given and that a proxy is
                // reducing javascript, it will not match
                // no-transform will avoid that
                $cacheControlDirective[] = self::NO_TRANSFORM;
            }
        } catch (ExceptionNotFound $e) {
            LogUtility::warning("The media ($mediaToSend) does not have any extension.");
        }
        header("Cache-Control: " . implode(", ", $cacheControlDirective));
        Http::removeHeaderIfPresent("Pragma");

        $excutingContext = ExecutionContext::getActualOrCreateFromEnv();
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
        try {
            $etag = self::getEtagValue($mediaToSend, $_REQUEST);
            header("ETag: $etag");
        } catch (ExceptionNotFound $e) {
            // internal error
            $excutingContext->response()
                ->setStatus(HttpResponseStatus::INTERNAL_ERROR)
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->setBodyAsJsonMessage("We were unable to get the etag because the media was not found. Error: {$e->getMessage()}")
                ->end();
            return;
        }


        /**
         * Conditional Request ?
         * We don't check on HTTP_IF_MODIFIED_SINCE because this is useless
         */
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            $ifNoneMatch = stripslashes($_SERVER['HTTP_IF_NONE_MATCH']);
            if ($ifNoneMatch && $ifNoneMatch === $etag) {
                /**
                 * Don't add a body
                 */
                $excutingContext
                    ->response()
                    ->setStatus(HttpResponseStatus::NOT_MODIFIED)
                    ->setEvent($event)
                    ->setCanonical(self::CANONICAL)
                    ->end();
                return;
            }
        }


        /**
         * Download or display feature
         * (Taken over from SendFile)
         */
        try {
            $mime = FileSystems::getMime($mediaToSend);
        } catch (ExceptionNotFound $e) {
            $excutingContext->response()
                ->setStatus(HttpResponseStatus::INTERNAL_ERROR)
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->setBodyAsJsonMessage("Mime not found")
                ->end();
            return;
        }
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
        http_sendfile($mediaToSend->toAbsolutePath()->toAbsoluteId());

        /**
         * Send the file
         */
        $filePointer = @fopen($mediaToSend->toAbsolutePath()->toAbsoluteId(), "rb");
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
            $excutingContext->response()
                ->setStatus(HttpResponseStatus::INTERNAL_ERROR)
                ->setBodyAsJsonMessage("Could not read $mediaToSend - bad permissions?")
                ->end();
        }

    }

    /**
     * @param Path $mediaFile
     * @param Array $properties - the query properties
     * @return string
     * @throws ExceptionNotFound
     */
    public
    static function getEtagValue(Path $mediaFile, array $properties): string
    {
        clearstatcache();
        $etagString = FileSystems::getModifiedTime($mediaFile)->format('r');
        ksort($properties);
        foreach ($properties as $key => $value) {

            /**
             * Media is already on the URL
             * tok is just added when w and h are on the url
             * Buster is the timestamp
             */
            if (in_array($key, ["media", "tok", IFetcher::CACHE_BUSTER_KEY])) {
                continue;
            }
            /**
             * If empty means not used
             */
            if (trim($value) === "") {
                continue;
            }
            $etagString .= "$key=$value";
        }
        return '"' . md5($etagString) . '"';
    }


}
