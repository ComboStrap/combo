<?php

use ComboStrap\ExceptionCompile;
use ComboStrap\Json;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Sqlite;
use ComboStrap\StringUtility;

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


/**
 * Set the home of the web site documentation
 */
class action_plugin_combo_linkwizard extends DokuWiki_Action_Plugin
{

    const CONF_ENABLE_ENHANCED_LINK_WIZARD = "enableEnhancedLinkWizard";
    const CANONICAL = "linkwizard";

    /**
     * @param Doku_Event_Handler $controller
     */
    function register(Doku_Event_Handler $controller)
    {

        /**
         * https://www.dokuwiki.org/devel:event:search_query_pagelookup
         */
        $controller->register_hook('SEARCH_QUERY_PAGELOOKUP', 'BEFORE', $this, 'searchPage', array());

    }


    /**
     * Modify the returned pages
     * The {@link callLinkWiz} of inc/Ajax.php do
     * just a page search with {@link ft_pageLookup()}
     * https://www.dokuwiki.org/search
     * @param Doku_Event $event
     * @param $params
     * The path are initialized in {@link init_paths}
     * @return void
     */
    function searchPage(Doku_Event $event, $params)
    {
        global $INPUT;
        /**
         * linkwiz is the editor toolbar action
         * qsearch is the search button
         */
        $postCall = $INPUT->post->str('call');
        if (!(in_array($postCall, ["linkwiz", "qsearch", action_plugin_combo_search::CALL]))) {
            return;
        }
        if (PluginUtility::getConfValue(self::CONF_ENABLE_ENHANCED_LINK_WIZARD, 1) === 0) {
            return;
        }
        $sqlite = Sqlite::createOrGetSqlite();
        if ($sqlite === null) {
            return;
        }

        $searchTerm = $event->data["id"]; // yes id is the search term
        $minimalWordLength = 3;
        if (strlen($searchTerm) < $minimalWordLength) {
            return;
        }
        $searchTermWords = StringUtility::getWords($searchTerm);
        if (sizeOf($searchTermWords) === 0) {
            return;
        }
        $sqlParameters = [];
        $sqlPredicates = [];
        foreach ($searchTermWords as $searchTermWord) {
            if (strlen($searchTermWord) < $minimalWordLength) {
                continue;
            }
            $pattern = "%$searchTermWord%";
            $sqlParameters = array_merge([$pattern, $pattern, $pattern, $pattern, $pattern], $sqlParameters);
            $sqlPredicates[] = "(id like ? COLLATE NOCASE or H1 like ? COLLATE NOCASE or title like ? COLLATE NOCASE or name like ? COLLATE NOCASE or path like ? COLLATE NOCASE)";
        }
        $sqlPredicate = implode(" and ", $sqlPredicates);
        $searchTermSql = <<<EOF
select id as "id", title as "title", h1 as "h1", name as "name", description as "description" from pages where $sqlPredicate order by name;
EOF;
        $rows = [];
        $request = $sqlite
            ->createRequest()
            ->setQueryParametrized($searchTermSql, $sqlParameters);
        try {
            $rows = $request
                ->execute()
                ->getRows();
        } catch (ExceptionCompile $e) {
            LogUtility::msg("Error while trying to retrieve a list of page", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
        } finally {
            $request->close();
        }


        /**
         * Adapted from {@link Ajax::callLinkwiz()}
         * because it delete the pages in the same namespace and shows the group instead
         * Breaking the flow
         */

        global $lang;
        if (!count($rows)) {
            \ComboStrap\HttpResponse::create(\ComboStrap\HttpResponse::STATUS_NOT_FOUND)
                ->sendTxtMessage($lang['nothingfound']);
            return;
        }

        // output the found data
        $even = 1;
        $html = "";
        $lowerSearchTerm = strtolower($searchTerm);
        foreach ($rows as $row) {
            $id = $row["id"];
            $path = ":$id";
            $name = $row["name"];
            $title = $row["title"];
            $h1 = $row["h1"];
            $even *= -1; //zebra
            $link = wl($id);
            $evenOrOdd = (($even > 0) ? 'even' : 'odd');
            $label = null;
            if (strpos(strtolower($title), $lowerSearchTerm) !== false) {
                $label = $title;
            }
            if ($label === null && strpos(strtolower($h1), $lowerSearchTerm) !== false) {
                $label = "$h1 (h1)";
            } else {
                $label = $title;
            }
            // path is used in the title to create the link
            $html .= <<<EOF
<div class="$evenOrOdd">
   <a href="$link" title="$path" class="wikilink1">$name</a>
   <span>$label </span>
</div>
EOF;
        }
        \ComboStrap\HttpResponse::create(\ComboStrap\HttpResponse::STATUS_ALL_GOOD)
            ->sendHtmlMessage($html);


    }


}
