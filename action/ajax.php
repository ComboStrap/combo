<?php

use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionInternal;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionRuntimeInternal;
use ComboStrap\ExecutionContext;
use ComboStrap\FetcherSystem;
use ComboStrap\HttpResponseStatus;
use ComboStrap\Mime;
use ComboStrap\PluginUtility;
use ComboStrap\Url;

require_once(__DIR__ . '/../vendor/autoload.php');


/**
 * Ajax search data
 */
class action_plugin_combo_ajax extends DokuWiki_Action_Plugin
{
    const CANONICAL = "ajax";
    const COMBO_CALL_NAME = "combo";


    /**
     * @param Doku_Event_Handler $controller
     */
    function register(Doku_Event_Handler $controller)
    {

        /**
         * The ajax api to return data
         * We do a AFTER because {@link action_plugin_move_rename} use the before to
         * set data to check if it will add a menu item
         */
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'searchAjaxHandler');

    }

    /**
     * @param Doku_Event $event
     * Adapted from callQSearch in Ajax.php
     */
    function searchAjaxHandler(Doku_Event $event)
    {

        $call = $event->data;
        if ($call !== self::COMBO_CALL_NAME) {
            return;
        }

        // no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();


        $fetchUrl = Url::createFromGetOrPostGlobalVariable();
        $executionContext = ExecutionContext::getActualOrCreateFromEnv();
        try {
            $fetcher = $executionContext->createStringMainFetcherFromRequestedUrl($fetchUrl);
        } catch (ExceptionInternal|ExceptionBadArgument|ExceptionNotFound $e) {
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


}
