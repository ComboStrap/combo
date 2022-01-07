<?php

use ComboStrap\Mime;

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


/**
 * Ajax search data
 */
class action_plugin_combo_search extends DokuWiki_Action_Plugin
{

    const CALL = "combo-search";


    /**
     * @param Doku_Event_Handler $controller
     */
    function register(Doku_Event_Handler $controller)
    {

        /**
         * The ajax api to return data
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
        if ($call !== self::CALL) {
            return;
        }
        //no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();


        /**
         * Shared check between post and get HTTP method
         */
        $query = $_GET["q"];
        if ($query === null) {
            /**
             * With {@link TestRequest}
             * for instance
             */
            $query = $_REQUEST["q"];
        }
        if (empty($query)) return;


        $query = urldecode($query);

        $inTitle = useHeading('navigation');
        $data = ft_pageLookup($query, true, true);
        $count = count($data);
        if (!$count) {
            \ComboStrap\HttpResponse::create(\ComboStrap\HttpResponse::STATUS_NOT_FOUND)
                ->sendMessage(["No pages found"]);
            return;
        }

        $maxElements = 50;
        if ($count > $maxElements) {
            array_splice($data, 0, $maxElements);
        }
        $dataJson = json_encode($data);
        \ComboStrap\HttpResponse::create(\ComboStrap\HttpResponse::STATUS_ALL_GOOD)
            ->send($dataJson, Mime::JSON);

    }


}
