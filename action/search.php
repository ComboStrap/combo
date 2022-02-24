<?php

use ComboStrap\ExceptionCombo;
use ComboStrap\MarkupRef;
use ComboStrap\Mime;
use ComboStrap\Page;

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
        $pages = ft_pageLookup($query, true, $inTitle);
        $count = count($pages);
        if (!$count) {
            \ComboStrap\HttpResponse::create(\ComboStrap\HttpResponse::STATUS_ALL_GOOD)
                ->sendMessage(["No pages found"]);
            return;
        }

        $maxElements = 50;
        if ($count > $maxElements) {
            array_splice($pages, 0, $maxElements);
        }

        $data = [];
        foreach ($pages as $id => $title) {
            $page = Page::createPageFromId($id);
            $linkUtility = MarkupRef::createFromPageId($id);
            try {
                $html = $linkUtility->toAttributes()->toHtmlEnterTag("a") . $page->getTitleOrDefault() . "</a>";
            } catch (ExceptionCombo $e) {
                $html = "Unable to render the link for the page ($id). Error: {$e->getMessage()}";
            }
            $data[] = $html;
        }
        $dataJson = json_encode($data);
        \ComboStrap\HttpResponse::create(\ComboStrap\HttpResponse::STATUS_ALL_GOOD)
            ->send($dataJson, Mime::JSON);

    }


}
