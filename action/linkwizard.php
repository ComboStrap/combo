<?php

use ComboStrap\ExceptionCompile;
use ComboStrap\ExecutionContext;
use ComboStrap\FileSystems;
use ComboStrap\HttpResponseStatus;
use ComboStrap\Json;
use ComboStrap\LogUtility;
use ComboStrap\LinkMarkup;
use ComboStrap\Mime;
use ComboStrap\PluginUtility;
use ComboStrap\Search;
use ComboStrap\Site;
use ComboStrap\SiteConfig;
use ComboStrap\Sqlite;
use ComboStrap\StringUtility;

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


/**
 *
 *
 * This action enhance the search of page in:
 * linkwiz: the editor toolbar action
 *
 * The entry point is in the {@link Ajax::callLinkwiz()}
 * function
 *
 */
class action_plugin_combo_linkwizard extends DokuWiki_Action_Plugin
{

    const CONF_ENABLE_ENHANCED_LINK_WIZARD = "enableEnhancedLinkWizard";
    const CANONICAL = "linkwizard";
    const CALL = "linkwiz";
    const MINIMAL_WORD_LENGTH = 3;


    /**
     * @param Doku_Event_Handler $controller
     */
    function register(Doku_Event_Handler $controller)
    {

        /**
         * https://www.dokuwiki.org/devel:event:search_query_pagelookup
         */
        $controller->register_hook('SEARCH_QUERY_PAGELOOKUP', 'BEFORE', $this, 'linkWizard', array());


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
    function linkWizard(Doku_Event $event, $params)
    {

        global $INPUT;

        $postCall = $INPUT->post->str('call');
        if ($postCall !== self::CALL) {
            return;
        }

        if (SiteConfig::getConfValue(self::CONF_ENABLE_ENHANCED_LINK_WIZARD, 1) === 0) {
            return;
        }


        $searchTerm = $event->data["id"]; // yes id is the search term
        $pages = Search::getPages($searchTerm);


        /**
         * Adapted from {@link Ajax::callLinkwiz()}
         * because link-wizard delete the pages in the same namespace and shows the group instead
         * Breaking the flow
         */

        global $lang;
        if (!count($pages)) {
            ExecutionContext::getActualOrCreateFromEnv()
                ->response()
                ->setStatus(HttpResponseStatus::ALL_GOOD)
                ->setBody("<div>" . $lang['nothingfound'] . "</div>", Mime::getHtml())
                ->end();
            return;
        }

        // output the found data
        $even = 1;
        $html = "";
        $lowerSearchTerm = strtolower($searchTerm);
        foreach ($pages as $page) {
            $id = $page->getWikiId();
            $path = $page->getPathObject()->toAbsoluteString();
            /**
             * The name is the label that is put
             * punt in the markup link
             * We set it in lowercase then.
             * Nobody want uppercase letter in their link label
             */
            $name = strtolower($page->getNameOrDefault());
            $title = $page->getTitleOrDefault();
            $h1 = $page->getH1OrDefault();
            $even *= -1; //zebra
            $link = wl($id);
            $evenOrOdd = (($even > 0) ? 'even' : 'odd');
            $label = null;
            if (strpos(strtolower($title), $lowerSearchTerm) !== false) {
                $label = "Title: $title";
            }
            if ($label === null && strpos(strtolower($h1), $lowerSearchTerm) !== false) {
                $label = "H1: $h1";
            } else {
                $label = "Title: $title";
            }
            $class = "";
            if (!FileSystems::exists($page->getPathObject())) {
                $errorClass = LinkMarkup::getHtmlClassNotExist();
                $class = "class=\"$errorClass\"";
            }
            /**
             * Because path is used in the title to create the link
             * by {@link file linkwiz.js} we set a title on the span
             */
            $html .= <<<EOF
<div class="$evenOrOdd">
   <a href="$link" title="$path" $class>$path</a><span title="$label">$name</span>
</div>
EOF;
        }
        ExecutionContext::getActualOrCreateFromEnv()
            ->response()
            ->setStatus(HttpResponseStatus::ALL_GOOD)
            ->setBody($html, Mime::getHtml())
            ->end();

    }


}
