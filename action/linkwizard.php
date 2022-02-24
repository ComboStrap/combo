<?php

use ComboStrap\ExceptionCombo;
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
        $controller->register_hook('SEARCH_QUERY_PAGELOOKUP', 'AFTER', $this, 'searchPage', array());

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
select id as "id", title as "title" from pages where $sqlPredicate order by id ASC;
EOF;
        $rows = [];
        $request = $sqlite
            ->createRequest()
            ->setQueryParametrized($searchTermSql, $sqlParameters);
        try {
            $rows = $request
                ->execute()
                ->getRows();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Error while trying to retrieve a list of page", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
        } finally {
            $request->close();
        }

        foreach ($rows as $row) {
            $event->result[$row["id"]] = $row["title"];
        }


    }


}
