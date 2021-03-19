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
    const DATE_PUBLISHED_KEY = "datePublished";
    const DATE_MODIFIED_KEY = "dateModified";
    const SPEAKABLE = "speakable";
    const PUBLISHER = "publisher";

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
        if (empty($type)) {
            return;
        }
        switch (strtolower($type)) {
            case Page::WEBSITE_TYPE:

                /**
                 * https://schema.org/WebSite
                 * https://developers.google.com/search/docs/data-types/sitelinks-searchbox
                 */

                $ldJson = array(
                    '@context' => 'http://schema.org',
                    '@type' => 'WebSite',
                    'url' => Site::getUrl(),
                    'name' => Site::getTitle()
                );

                if ($page->isHomePage()) {

                    $ldJson['potentialAction'] = array(
                        '@type' => 'SearchAction',
                        'target' => Site::getUrl() . DOKU_SCRIPT . '?do=search&amp;id={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    );
                }

                $tag = Site::getTag();
                if (!empty($tag)) {
                    $ldJson['description'] = $tag;
                }
                $siteImageUrl = Site::getLogoUrlAsPng();
                if (!empty($siteImageUrl)) {
                    $ldJson['image'] = $siteImageUrl;
                }

                break;

            case Page::ORGANIZATION_TYPE:

                /**
                 * Organization + Logo
                 * https://developers.google.com/search/docs/data-types/logo
                 */
                $ldJson = array(
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
                switch (strtolower($type)) {
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

                $ldJson = array(
                    "@context" => "https://schema.org",
                    "@type" => $schemaType,
                    'url' => $page->getCanonicalUrlOrDefault(),
                    "headline" => $page->getTitleNotEmpty(),
                    self::DATE_PUBLISHED_KEY => date('c', $page->getPublishedElseCreationTimeStamp()),
                    self::DATE_MODIFIED_KEY => date('c', $page->getModifiedTimestamp()),
                );

                /**
                 * Publisher info
                 */
                $publisher = array(
                    "@type" => "Organization",
                    "name" => Site::getTitle()
                );
                $logoUrlAsPng = Site::getLogoUrlAsPng();
                if (!empty($logoUrlAsPng)) {
                    $publisher["logo"] = array(
                        "@type" => "ImageObject",
                        "url" => $logoUrlAsPng
                    );
                }
                $ldJson["publisher"] = $publisher;


                $imagesSet = $page->getImageSet();
                $schemaImages = array();
                foreach ($imagesSet as $imageId) {
                    $image = new Image($imageId);
                    if ($image->exists()) {
                        $imageObjectSchema = array(
                            "@type" => "ImageObject",
                            "url" => $image->getUrl()
                        );
                        if ($image->isAnalyzable()) {
                            if (!empty($image->getWidth())) {
                                $imageObjectSchema["width"] = $image->getWidth();
                            }
                            if (!empty($image->getHeight())) {
                                $imageObjectSchema["height"] = $image->getHeight();
                            }
                        }
                        $schemaImages[] = $imageObjectSchema;
                    } else {
                        LogUtility::msg("The image ($imageId) does not exist and was not added to the google ld-json", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    }
                }
                if (!empty($schemaImages)) {
                    $ldJson["image"] = $schemaImages;
                }
                break;

            default:

                // May be added manually by the user itself
                $ldJson = array(
                    '@context' => 'http://schema.org',
                    '@type' => $type,
                    'url' => $page->getCanonicalUrlOrDefault()
                );
                break;
        }


        /**
         * https://developers.google.com/search/docs/data-types/speakable
         */
        $speakableXpath = array();
        if (!empty($page->getTitle())) {
            $speakableXpath[] = "/html/head/title";
        }
        if (!empty($page->getDescription())) {
            /**
             * Only the description written otherwise this is not speakable
             * you can have link and other strangeness
             */
            $speakableXpath[] = "/html/head/meta[@name='description']/@content";
        }
        $ldJson[self::SPEAKABLE] = array(
            "@type" => "SpeakableSpecification",
            "xpath" => $speakableXpath
        );

        /**
         * Do we have extra ld-json properties
         */
        $extraLdJson = $page->getMetadata(self::JSON_LD_PROPERTY);
        if (!empty($extraLdJson)) {
            $ldJson = array_merge($ldJson, $extraLdJson);
        }


        /**
         * Publish
         */
        if (!empty($ldJson)) {
            $event->data["script"][] = array(
                "type" => "application/ld+json",
                "_data" => json_encode($ldJson, JSON_PRETTY_PRINT),
            );
        }
    }


}
