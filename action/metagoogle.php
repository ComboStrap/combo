<?php

use ComboStrap\Iso8601Date;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\Site;

if (!defined('DOKU_INC')) die();

require_once(__DIR__ . '/../ComboStrap/Site.php');

/**
 *
 *
 * To test locally use ngrok
 * https://developers.google.com/search/docs/guides/debug#testing-firewalled-pages
 *
 * Tool:
 * https://support.google.com/webmasters/answer/2774099# - Data Highlighter
 * to tag page manually (you see well what kind of information they need)
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
    const JSON_LD_META_PROPERTY = "json-ld";
    const NEWSARTICLE_SCHEMA_ORG_LOWERCASE = "newsarticle";
    const BLOGPOSTING_SCHEMA_ORG_LOWERCASE = "blogposting";
    const DATE_PUBLISHED_KEY = "datePublished";
    const DATE_MODIFIED_KEY = "dateModified";
    const SPEAKABLE = "speakable";
    const PUBLISHER = "publisher";

    /**
     * @deprecated
     * This attribute was used to hold json-ld organization
     * data
     */
    public const OLD_ORGANIZATION_PROPERTY = "organization";

    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }

    /**
     * @param array $ldJson
     * @param Page $page
     */
    private static function addImage(array &$ldJson, Page $page)
    {
        /**
         * Image must belong to the page
         * https://developers.google.com/search/docs/guides/sd-policies#images
         *
         * Image may have IPTC metadata: not yet implemented
         * https://developers.google.com/search/docs/advanced/appearance/image-rights-metadata
         *
         * Image must have the supported format
         * https://developers.google.com/search/docs/advanced/guidelines/google-images#supported-image-formats
         * BMP, GIF, JPEG, PNG, WebP, and SVG
         */
        $supportedMime = [
            "image/bmp",
            "image/gif",
            "image/jpeg",
            "image/png",
            "image/webp",
            "image/svg+xml",
        ];
        $imagesSet = $page->getPageImagesAsImageOrDefault();
        $schemaImages = array();
        foreach ($imagesSet as $image) {

            $mime = $image->getMime();
            if (in_array($mime, $supportedMime)) {
                if ($image->exists()) {
                    $imageObjectSchema = array(
                        "@type" => "ImageObject",
                        "url" => $image->getAbsoluteUrl()
                    );
                    if (!empty($image->getIntrinsicWidth())) {
                        $imageObjectSchema["width"] = $image->getIntrinsicWidth();
                    }
                    if (!empty($image->getIntrinsicHeight())) {
                        $imageObjectSchema["height"] = $image->getIntrinsicHeight();
                    }
                    $schemaImages[] = $imageObjectSchema;
                } else {
                    LogUtility::msg("The image ($image) does not exist and was not added to the google ld-json", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                }
            }
        }

        if (!empty($schemaImages)) {
            $ldJson["image"] = $schemaImages;
        }
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
        $page = Page::createPageFromId($ID);
        if (!$page->exists()) {
            return;
        }

        /**
         * No metadata for bars
         */
        if ($page->isSlot()) {
            return;
        }

        $type = $page->getTypeNotEmpty();
        switch (strtolower($type)) {
            case Page::WEBSITE_TYPE:

                /**
                 * https://schema.org/WebSite
                 * https://developers.google.com/search/docs/data-types/sitelinks-searchbox
                 */

                $ldJson = array(
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'url' => Site::getUrl(),
                    'name' => Site::getTitle()
                );

                if ($page->isRootHomePage()) {

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
            case PAGE::HOME_TYPE:
            case PAGE::WEB_PAGE_TYPE:

                switch (strtolower($type)) {
                    case Page::NEWS_TYPE:
                    case self::NEWSARTICLE_SCHEMA_ORG_LOWERCASE:
                        $schemaType = "NewsArticle";
                        break;
                    case Page::BLOG_TYPE:
                    case self::BLOGPOSTING_SCHEMA_ORG_LOWERCASE:
                        $schemaType = "BlogPosting";
                        break;
                    case PAGE::HOME_TYPE:
                    case PAGE::WEB_PAGE_TYPE:
                        // https://schema.org/WebPage
                        $schemaType = "WebPage";
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
                    self::DATE_PUBLISHED_KEY => $page->getPublishedElseCreationTime()->format(Iso8601Date::getFormat())
                );

                /**
                 * Modified Time
                 */
                $modifiedTime = $page->getModifiedTime();
                if ($modifiedTime != null) {
                    $ldJson[self::DATE_MODIFIED_KEY] = $modifiedTime->format(Iso8601Date::getFormat());
                };

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

                self::addImage($ldJson, $page);
                break;

            case PAGE::EVENT_TYPE:
                // https://developers.google.com/search/docs/advanced/structured-data/event
                $ldJson = array(
                    "@context" => "https://schema.org",
                    "@type" => "Event");
                $eventName = $page->getPageName();
                if (!blank($eventName)) {
                    $ldJson["name"] = $eventName;
                } else {
                    LogUtility::msg("The name metadata is mandatory for a event page", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    return;
                }
                $eventDescription = $page->getDescription();
                if (blank($eventDescription)) {
                    LogUtility::msg("The description metadata is mandatory for a event page", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    return;
                }
                $ldJson["description"] = $eventDescription;
                $startDate = $page->getStartDateAsString();
                if ($startDate === null) {
                    LogUtility::msg("The date_start metadata is mandatory for a event page", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    return;
                }
                $ldJson["startDate"] = $page->getStartDateAsString();

                $endDate = $page->getEndDateAsString();
                if ($endDate === null) {
                    LogUtility::msg("The date_end metadata is mandatory for a event page", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    return;
                }
                $ldJson["endDate"] = $page->getEndDateAsString();


                self::addImage($ldJson, $page);
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
        $extraLdJson = $page->getLdJson();
        if (!empty($extraLdJson)) {
            if (isset($extraLdJson["@type"]) && isset($extraLdJson["@context"])) {
                $ldJson = $extraLdJson;
            } else {
                $ldJson = array_merge($ldJson, $extraLdJson);
            }
        }


        /**
         * Publish
         */
        if (!empty($ldJson)) {
            $jsonEncode = json_encode($ldJson, JSON_PRETTY_PRINT);
            $event->data["script"][] = array(
                "type" => "application/ld+json",
                "_data" => $jsonEncode,
            );
        }
    }


}
