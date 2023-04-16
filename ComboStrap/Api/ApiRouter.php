<?php

namespace ComboStrap\Api;

use Api\AjaxHandler;
use ComboStrap\ExceptionInternal;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionRuntimeInternal;
use ComboStrap\ExecutionContext;
use ComboStrap\HttpResponseStatus;
use ComboStrap\IFetcher;
use ComboStrap\Mime;
use ComboStrap\PluginUtility;
use ComboStrap\Web\Url;
use dokuwiki\Extension\Event;

class ApiRouter
{
    public const CANONICAL = "ajax";

    /**
     * The generic call that should be used for {@link \action_plugin_combo_ajax call}
     */
    public const AJAX_CALL_VALUE = "combo";
    const AJAX_CALL_ATTRIBUTE = 'call';

    /**
     * @param Event $event
     * @return void
     */
    public static function handle(Event $event)
    {

        $call = $event->data;
        switch ($call) {
            case QualityMessageHandler::CALL_ID:
                QualityMessageHandler::handle($event);
                return;
            case MetaManagerHandler::META_MANAGER_CALL_ID:
            case MetaManagerHandler::META_VIEWER_CALL_ID:
                MetaManagerHandler::handle($event);
                return;
        }

        $fetchUrl = Url::createFromGetOrPostGlobalVariable();
        if ($call !== self::AJAX_CALL_VALUE && !$fetchUrl->hasProperty(IFetcher::FETCHER_KEY)) {
            return;
        }

        // no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();


        $executionContext = ExecutionContext::getActualOrCreateFromEnv();
        try {
            $fetcher = $executionContext->createStringMainFetcherFromRequestedUrl($fetchUrl);
        } catch (\Exception $e) {
            if (PluginUtility::isTest()) {
                throw new ExceptionRuntimeInternal("Error while creating the ajax fetcher.", self::CANONICAL, 1, $e);
            }
            $executionContext
                ->response()
                ->setException($e)
                ->setBody("Error while creating the fetcher for the fetch Url ($fetchUrl)", Mime::getText())
                ->end();
            return;
        }

        $executionContext
            ->response()
            ->setStatus(HttpResponseStatus::ALL_GOOD)
            ->setBody($fetcher->getFetchString(), $fetcher->getMime())
            ->end();


    }

    /**
     * @throws ExceptionNotFound
     */
    public static function getRequestParameter(string $parameter)
    {
        /**
         * Shared check between post and get HTTP method
         */
        if (array_key_exists($parameter, $_GET)) {
            /**
             * May be null value with boolean
             */
            return $_GET[$parameter];
        }

        /**
         * With {@link TestRequest}
         * for instance
         */
        if (array_key_exists($parameter, $_REQUEST)) {
            return $_REQUEST[$parameter];
        }

        global $INPUT;
        if ($INPUT->has($parameter)) {
            return $INPUT->str($parameter);
        }

        if (defined('DOKU_UNITTEST')) {
            global $COMBO;
            if (array_key_exists($parameter, $COMBO)) {
                return $COMBO[$parameter];
            }
        }

        throw new ExceptionNotFound("The parameter ($parameter) was not found for this request");

    }

    public static function hasRequestParameter(string $parameter): bool
    {
        try {
            self::getRequestParameter($parameter);
            return true;
        } catch (ExceptionNotFound $e) {
            return false;
        }
    }
}
