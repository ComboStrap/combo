<?php

use ComboStrap\Json;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Sqlite;

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


/**
 * Set the home of the web site documentation
 */
class action_plugin_combo_linkwizard extends DokuWiki_Action_Plugin
{

    const CONF_ENABLE_ENHANCED_LINK_WIZARD = "enableEnhancedLinkWizard";

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
        if (!(in_array($INPUT->post->str('call'), [ "linkwiz","qsearch"]))) {
            return;
        }
        if(PluginUtility::getConfValue(self::CONF_ENABLE_ENHANCED_LINK_WIZARD,1)===0){
            return;
        }
        $sqlite = Sqlite::createOrGetSqlite();
        if ($sqlite === null) {
            return;
        }
        $id = $event->data["id"];
        if(strlen($id)<3){
            return;
        }
        $pattern = "*$id*";
        $patterns = [$pattern,$pattern,$pattern,$pattern];
        $query = <<<EOF
select id, title from pages where id glob ? or H1 glob ? or title glob ? or name glob ? order by id ASC;
EOF;
        $res = $sqlite->query($query, $patterns);
        if (!$res) {
            LogUtility::msg("An exception has occurred with the link wizard query with the pattern ($pattern)");
            return;
        }
        $rows = $sqlite->res2arr($res);
        foreach ($rows as $row) {
            $event->result[$row["ID"]] = $row["TITLE"];
        }


    }


}
