<?php

use ComboStrap\Image;
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


    const CANONICAL = "google";
    const JSON_LD_PROPERTY = "json-ld";
    const NEWSARTICLE_SCHEMA_ORG_LOWERCASE = "newsarticle";
    const BLOGPOSTING_SCHEMA_ORG_LOWERCASE = "blogposting";

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

        $type = $page->getType();
        if (!empty($type)) {
            switch (strtolower($type)) {
                case Page::WEBSITE_TYPE:

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

                case Page::ORGANIZATION_TYPE:

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

                case Page::ARTICLE_TYPE:
                case Page::NEWS_TYPE:
                case Page::BLOG_TYPE:
                case self::NEWSARTICLE_SCHEMA_ORG_LOWERCASE:
                case self::BLOGPOSTING_SCHEMA_ORG_LOWERCASE:

                    $schemaType = "Article";
                    switch(strtolower($type)){
                        case Page::NEWS_TYPE:
                        case self::NEWSARTICLE_SCHEMA_ORG_LOWERCASE:
                            $schemaType = "NewsArticle";
                            break;
                        case Page::BLOG_TYPE:
                        case self::BLOGPOSTING_SCHEMA_ORG_LOWERCASE:
                            $schemaType = "BlogPosting";
                            break;
                    }
                    // https://developers.google.com/search/docs/data-types/article
                    // https://schema.org/Article

                    // Image (at least 696 pixels wide)
                    // https://developers.google.com/search/docs/advanced/guidelines/google-images#supported-image-formats
                    // BMP, GIF, JPEG, PNG, WebP, and SVG.

                    // Date should be https://en.wikipedia.org/wiki/ISO_8601

                    $ldJsonSite = array(
                        "@context" => "https://schema.org",
                        "@type" => $schemaType,
                        'url' => $page->getCanonicalUrlOrDefault(),
                        "headline" => $page->getTitleNotEmpty(),
                        "datePublished" => date('c', $page->getPublishedElseCreationTimeStamp()),
                        "dateModified" => date('c', $page->getModifiedTimestamp()),
                        "publisher" => array(
                            "@type" => "Organization",
                            "name" => Site::getTitle(),
                            "logo" => array(
                                "@type" => "ImageObject",
                                "url" => Site::getLogoUrlAsPng()
                            )
                        )
                    );

                    $imagesId = $page->getImageSet();
                    $imagesUrl = array();
                    foreach ($imagesId as $imageId) {
                        $image = new Image($imageId);
                        if ($image->exists()) {
                            $imagesUrl[] = $image->getUrl();
                        } else {
                            LogUtility::msg("The image ($imageId) does not exist and was not added to the google ld-json", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                        }
                    }
                    $imagesCount = sizeof($imagesUrl);
                    switch ($imagesCount) {
                        case 0:
                            // No image
                            break;
                        case 1:
                            $ldJsonSite["image"] = $imagesUrl[0];
                            break;
                        default:
                            $ldJsonSite["image"] = $imagesUrl;
                    }
                    break;

                default:

                    // May be added manually by the user itself
                    $ldJsonSite = array(
                        '@context' => 'http://schema.org',
                        '@type' => $type,
                        'url' => $page->getCanonicalUrlOrDefault()
                    );
                    break;
            }

            /**
             * Do we have extra ld-json properties
             */
            $extraLdJson = $page->getMetadata(self::JSON_LD_PROPERTY);
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


}
