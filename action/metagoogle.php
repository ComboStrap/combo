<?php

use ComboStrap\MetadataUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Page;
use ComboStrap\Site;
use ComboStrap\StringUtility;

if (!defined('DOKU_INC')) die();

require_once(__DIR__ . '/../class/Site.php');

/**
 *
 *
 *
 *
 * Ref:
 * https://developers.google.com/search/docs/guides/intro-structured-data
 * https://github.com/giterlizzi/dokuwiki-plugin-semantic/blob/master/helper.php
 * https://json-ld.org/
 * https://schema.org/docs/documents.html
 * https://search.google.com/structured-data/testing-tool/u/0/#url=https%3A%2F%2Fen.wikipedia.org%2Fwiki%2FPacu_jawi
 */
class action_plugin_combo_metagoogle extends DokuWiki_Action_Plugin
{



    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }

    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'metaGoogleProcessing', array());
    }

    /**
     *
     * @param $event
     */
    function metaGoogleProcessing($event)
    {


        if ($_SERVER['SCRIPT_NAME'] == "/lib/exe/mediamanager.php") {
            // $ID is null
            return;
        }

        global $ID;
        $page = new Page($ID);

        /**
         * No social for bars
         */
        if ($page->isBar()) {
            return;
        }

        if ($page->isHomePage()) {
            /**
             * https://schema.org/WebSite
             * https://developers.google.com/search/docs/data-types/sitelinks-searchbox
             */

            $ldJsonSite = array(
                '@context' => 'http://schema.org',
                '@type' => 'WebSite',
                'url' => Site::getUrl(),
                'name' => Site::getTitle(),
                'potentialAction' => array(
                    '@type' => 'SearchAction',
                    'target' => Site::getUrl() . DOKU_SCRIPT . '?do=search&amp;id={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                )
            );
            $tag = Site::getTag();
            if (!empty($tag)) {
                $ldJsonSite['description'] = $tag;
            }
            $siteImageUrl = Site::getLogoUrlAsPng();
            if (!empty($siteImageUrl)) {
                $ldJsonSite['image'] = $siteImageUrl;
            }

            $event->data["script"][] = array(
                "type" => "application/ld+json",
                "_data" => json_encode($ldJsonSite, JSON_PRETTY_PRINT),
            );

            /**
             * Organization + Logo
             * https://developers.google.com/search/docs/data-types/logo
             */

            $ldJsonOrganization = array(
                "@context" => "https://schema.org",
                "@type" => "Organization",
                "url" => Site::getUrl(),
                "logo" => Site::getLogoUrlAsPng()
            );

            $event->data["script"][] = array(
                "type" => "application/ld+json",
                "_data" => json_encode($ldJsonOrganization, JSON_PRETTY_PRINT),
            );
        }

}

}
