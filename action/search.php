<?php

use ComboStrap\ExceptionCompile;
use ComboStrap\LogUtility;
use ComboStrap\LinkMarkup;
use ComboStrap\Mime;
use ComboStrap\PageFragment;
use ComboStrap\Search;

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
            // The call for dokuwiki is "qsearch"
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

        /**
         * Ter info: Old call: how dokuwiki call it.
         * It's then executing the SEARCH_QUERY_PAGELOOKUP event
         *
         * $inTitle = useHeading('navigation');
         * $pages = ft_pageLookup($query, true, $inTitle);
         */
        $pages = Search::getPages($query);
        $maxElements = 50;
        if (count($pages) > $maxElements) {
            array_splice($pages, 0, $maxElements);
        }

        $data = [];
        foreach ($pages as $page) {
            if (!$page->exists()) {
                $page->getDatabasePage()->delete();
                LogUtility::log2file("The page ($page) returned from the search query does not exist and was deleted from the database");
                continue;
            }
            $linkUtility = LinkMarkup::createFromPageIdOrPath($page->getDokuwikiId());
            try {
                $html = $linkUtility->toAttributes()->toHtmlEnterTag("a") . $page->getTitleOrDefault() . "</a>";
            } catch (ExceptionCompile $e) {
                $html = "Unable to render the link for the page ($page). Error: {$e->getMessage()}";
            }
            $data[] = $html;
        }
        $count = count($data);
        if (!$count) {
            \ComboStrap\HttpResponse::create(\ComboStrap\HttpResponse::STATUS_ALL_GOOD)
                ->sendMessage(["No pages found"]);
            return;
        }

        $dataJson = json_encode($data);
        \ComboStrap\HttpResponse::create(\ComboStrap\HttpResponse::STATUS_ALL_GOOD)
            ->send($dataJson, Mime::JSON);

    }


}
