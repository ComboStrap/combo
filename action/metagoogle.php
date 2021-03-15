<?php

use ComboStrap\LogUtility;
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
 * To test locally use ngrok
 * https://developers.google.com/search/docs/guides/debug#testing-firewalled-pages
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


    const WEBSITE_TYPE = "website";

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


        global $ID;
        if (empty($ID)) {
            // $ID is null
            // case on "/lib/exe/mediamanager.php"
            return;
        }
        $page = new Page($ID);

        /**
         * No metadata for bars
         */
        if ($page->isBar()) {
            return;
        }

        $type = strtolower($page->getType());
        if (empty($type)) {
            $type = self::WEBSITE_TYPE;
        }

        if (empty($type)) {
            if ($page->isHomePage()) {
                $type = self::WEBSITE_TYPE;
            }
        }



        $ldJsonSite = array();
        switch ($type) {
            case self::WEBSITE_TYPE:

                /**
                 * https://schema.org/WebSite
                 * https://developers.google.com/search/docs/data-types/sitelinks-searchbox
                 */

                $ldJsonSite = array(
                    '@context' => 'http://schema.org',
                    '@type' => 'WebSite',
                    'url' => Site::getUrl(),
                    'name' => Site::getTitle()
                );

                if ($page->isHomePage()) {

                    $ldJsonSite['potentialAction'] = array(
                        '@type' => 'SearchAction',
                        'target' => Site::getUrl() . DOKU_SCRIPT . '?do=search&amp;id={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    );
                }

                $tag = Site::getTag();
                if (!empty($tag)) {
                    $ldJsonSite['description'] = $tag;
                }
                $siteImageUrl = Site::getLogoUrlAsPng();
                if (!empty($siteImageUrl)) {
                    $ldJsonSite['image'] = $siteImageUrl;
                }

                break;

            case
            "organization":

                /**
                 * Organization + Logo
                 * https://developers.google.com/search/docs/data-types/logo
                 */
                $ldJsonSite = array(
                    "@context" => "https://schema.org",
                    "@type" => "Organization",
                    "url" => Site::getUrl(),
                    "logo" => Site::getLogoUrlAsPng()
                );

                break;

            case "article":

                // https://developers.google.com/search/docs/data-types/article

                // Image (at least 696 pixels wide)
                // https://developers.google.com/search/docs/advanced/guidelines/google-images#supported-image-formats

                // Date
                // https://en.wikipedia.org/wiki/ISO_8601

                break;
            default:
                LogUtility::msg("The type ($type) is unknown for the page (" . $page->getId() . ")", LogUtility::LVL_MSG_ERROR, "semantic:type");

                break;
        }

        /**
         * Do we have extra ld-json properties
         */
        $extraLdJson = $page->getMetadata($type);
        if (empty($extraLdJson)) {
            $extraLdJson = $page->getMetadata($ldJsonSite["@type"]);
        }
        if (!empty($extraLdJson)) {
            $ldJsonSite = array_merge($ldJsonSite, $extraLdJson);
        }


        /**
         * Publish
         */
        if (!empty($ldJsonSite)) {
            $event->data["script"][] = array(
                "type" => "application/ld+json",
                "_data" => json_encode($ldJsonSite, JSON_PRETTY_PRINT),
            );
        }
    }


}
