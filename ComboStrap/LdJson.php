<?php


namespace ComboStrap;


use action_plugin_combo_metagoogle;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataJson;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;

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
class LdJson extends MetadataJson
{

    public const PROPERTY_NAME = "json-ld";

    public const SPEAKABLE = "speakable";
    public const NEWSARTICLE_SCHEMA_ORG_LOWERCASE = "newsarticle";
    public const BLOGPOSTING_SCHEMA_ORG_LOWERCASE = "blogposting";
    /**
     * @deprecated
     * This attribute was used to hold json-ld organization
     * data
     */
    public const OLD_ORGANIZATION_PROPERTY = "organization";
    public const DATE_PUBLISHED_KEY = "datePublished";
    public const DATE_MODIFIED_KEY = "dateModified";

    public const CANONICAL = action_plugin_combo_metagoogle::CANONICAL;

    public static function createForPage(MarkupPath $page): LdJson
    {
        return (new LdJson())
            ->setResource($page);
    }

    /**
     * @param array $ldJson
     * @param MarkupPath $page
     */
    public static function addImage(array &$ldJson, MarkupPath $page)
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
            Mime::BMP,
            Mime::GIF,
            Mime::JPEG,
            Mime::PNG,
            Mime::WEBP,
            Mime::SVG,
        ];
        $imagesSet = $page->getImagesForTheFollowingUsages([PageImageUsage::ALL, PageImageUsage::SOCIAL, PageImageUsage::GOOGLE]);
        $schemaImages = array();
        foreach ($imagesSet as $pageImage) {

            try {
                $pageImagePath = $pageImage->getSourcePath()->toWikiPath();
            } catch (ExceptionCast $e) {
                LogUtility::internalError("The page image should come from a wiki path", self::CANONICAL, $e);
                continue;
            }
            try {
                $mime = $pageImagePath->getMime()->toString();
            } catch (ExceptionNotFound $e) {
                // should not happen
                LogUtility::internalError("The page image mime could not be determined. Error:" . $e->getMessage(), self::CANONICAL, $e);
                $mime = "unknown";
            }
            if (in_array($mime, $supportedMime)) {
                if (FileSystems::exists($pageImagePath)) {
                    try {
                        $fetcherPageImage = IFetcherLocalImage::createImageFetchFromPath($pageImagePath);
                    } catch (ExceptionBadArgument|ExceptionBadSyntax|ExceptionNotExists $e) {
                        LogUtility::error("The image ($pageImagePath) could not be added as page image. Error: {$e->getMessage()}");
                        continue;
                    }
                    $imageObjectSchema = array(
                        "@type" => "ImageObject",
                        "url" => $fetcherPageImage->getFetchUrl()->toAbsoluteUrlString()
                    );
                    if (!empty($fetcherPageImage->getIntrinsicWidth())) {
                        $imageObjectSchema["width"] = $fetcherPageImage->getIntrinsicWidth();
                    }
                    if (!empty($fetcherPageImage->getIntrinsicHeight())) {
                        $imageObjectSchema["height"] = $fetcherPageImage->getIntrinsicHeight();
                    }
                    $schemaImages[] = $imageObjectSchema;
                } else {
                    LogUtility::msg("The image ($pageImagePath) does not exist and was not added to the google ld-json", LogUtility::LVL_MSG_ERROR, action_plugin_combo_metagoogle::CANONICAL);
                }
            }
        }

        if (!empty($schemaImages)) {
            $ldJson["image"] = $schemaImages;
        }
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    static public function getPersistenceType(): string
    {
        return MetadataDokuWikiStore::PERSISTENT_DOKUWIKI_KEY;
    }

    static public function getCanonical(): string
    {
        return action_plugin_combo_metagoogle::CANONICAL;
    }


    static public function getDescription(): string
    {
        return "Advanced Page metadata definition with the json-ld format";
    }

    static public function getLabel(): string
    {
        return "Json-ld";
    }

    static public function getTab(): string
    {
        return MetaManagerForm::TAB_TYPE_VALUE;
    }


    static public function isMutable(): bool
    {
        return true;
    }

    public function getDefaultValue(): ?string
    {

        $ldJson = $this->mergeWithDefaultValueAndGet();
        if ($ldJson === null) {
            return null;
        }

        /**
         * Return
         */
        return Json::createFromArray($ldJson)->toPrettyJsonString();

    }

    public function setFromStoreValueWithoutException($value): Metadata
    {

        if ($value === null) {
            $resourceCombo = $this->getResource();
            if (($resourceCombo instanceof MarkupPath)) {
                /**
                 * Deprecated, old organization syntax
                 * We could add this predicate
                 *
                 * but we don't want to lose any data
                 * (ie if the page was set to no be an organization table,
                 * the frontmatter would not take it)
                 */
                $store = $this->getReadStore();
                $metadata = $store->getFromName(self::OLD_ORGANIZATION_PROPERTY);
                if ($metadata !== null) {
                    $organization = array(
                        "organization" => $metadata
                    );
                    $ldJsonOrganization = $this->mergeWithDefaultValueAndGet($organization);
                    $value = Json::createFromArray($ldJsonOrganization)->toPrettyJsonString();
                }
            }
        }
        parent::setFromStoreValueWithoutException($value);
        return $this;


    }

    /**
     * The ldJson value
     * @return false|string|null
     */
    public function getLdJsonMergedWithDefault()
    {

        try {
            $value = $this->getValue();
            try {
                $actualValueAsArray = Json::createFromString($value)->toArray();
            } catch (ExceptionCompile $e) {
                LogUtility::error("The string value is not a valid Json. Value: $value", self::CANONICAL);
                return $value;
            }
        } catch (ExceptionNotFound $e) {
            $actualValueAsArray = [];
        }
        $actualValueAsArray = $this->mergeWithDefaultValueAndGet($actualValueAsArray);
        return Json::createFromArray($actualValueAsArray)->toPrettyJsonString();
    }


    private function mergeWithDefaultValueAndGet($actualValue = null): ?array
    {
        $page = $this->getResource();
        if (!($page instanceof MarkupPath)) {
            return $actualValue;
        }

        $readStore = $this->getReadStore();
        $type = PageType::createForPage($page)
            ->setReadStore(MetadataDokuWikiStore::class)
            ->getValueOrDefault();
        if (!($readStore instanceof MetadataDokuWikiStore)) {
            /**
             * Edge case we set the readstore because in a frontmatter,
             * the type may have been set
             */
            try {
                $type = PageType::createForPage($page)
                    ->setReadStore($readStore)
                    ->getValue();
            } catch (ExceptionNotFound $e) {
                // ok
            }
        }
        switch (strtolower($type)) {
            case PageType::WEBSITE_TYPE:

                /**
                 * https://schema.org/WebSite
                 * https://developers.google.com/search/docs/data-types/sitelinks-searchbox
                 */
                $ldJson = array(
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'url' => Site::getBaseUrl(),
                    'name' => Site::getTitle()
                );

                if ($page->isRootHomePage()) {

                    $ldJson['potentialAction'] = array(
                        '@type' => 'SearchAction',
                        'target' => Site::getBaseUrl() . DOKU_SCRIPT . '?do=search&amp;id={search_term_string}',
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

            case PageType::ORGANIZATION_TYPE:

                /**
                 * Organization + Logo
                 * https://developers.google.com/search/docs/data-types/logo
                 */
                $ldJson = array(
                    "@context" => "https://schema.org",
                    "@type" => "Organization",
                    "url" => Site::getBaseUrl(),
                    "logo" => Site::getLogoUrlAsPng()
                );

                break;

            case PageType::ARTICLE_TYPE:
            case PageType::NEWS_TYPE:
            case PageType::BLOG_TYPE:
            case self::NEWSARTICLE_SCHEMA_ORG_LOWERCASE:
            case self::BLOGPOSTING_SCHEMA_ORG_LOWERCASE:
            case PageType::HOME_TYPE:
            case PageType::WEB_PAGE_TYPE:

                switch (strtolower($type)) {
                    case PageType::NEWS_TYPE:
                    case self::NEWSARTICLE_SCHEMA_ORG_LOWERCASE:
                        $schemaType = "NewsArticle";
                        break;
                    case PageType::BLOG_TYPE:
                    case self::BLOGPOSTING_SCHEMA_ORG_LOWERCASE:
                        $schemaType = "BlogPosting";
                        break;
                    case PageType::HOME_TYPE:
                    case PageType::WEB_PAGE_TYPE:
                        // https://schema.org/WebPage
                        $schemaType = "WebPage";
                        break;
                    case PageType::ARTICLE_TYPE:
                    default:
                        $schemaType = "Article";
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
                    'url' => $page->getAbsoluteCanonicalUrl()->toString(),
                    "headline" => $page->getTitleOrDefault(),

                );

                try {
                    $ldJson[self::DATE_PUBLISHED_KEY] = $page
                        ->getPublishedElseCreationTime()
                        ->format(Iso8601Date::getFormat());
                } catch (ExceptionNotFound $e) {
                    // Internal error, the page should exist
                    LogUtility::error("Internal Error: We were unable to define the publication date for the page ($page). Error: {$e->getMessage()}", self::CANONICAL);
                }

                /**
                 * Modified Time
                 */
                try {
                    $modifiedTime = $page->getModifiedTimeOrDefault();
                    $ldJson[self::DATE_MODIFIED_KEY] = $modifiedTime->format(Iso8601Date::getFormat());
                } catch (ExceptionNotFound $e) {
                    // Internal error, the page should exist
                    LogUtility::error("Internal Error: We were unable to define the modification date for the page ($page)", self::CANONICAL);
                }

                /**
                 * Publisher info
                 */
                $publisher = array(
                    "@type" => "Organization",
                    "name" => Site::getName()
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

            case PageType::EVENT_TYPE:
                // https://developers.google.com/search/docs/advanced/structured-data/event
                $ldJson = array(
                    "@context" => "https://schema.org",
                    "@type" => "Event");
                try {
                    $eventName = $page->getName();
                    $ldJson["name"] = $eventName;
                } catch (ExceptionNotFound $e) {
                    LogUtility::msg("The name metadata is mandatory for a event page", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    return null;
                }

                try {
                    $eventDescription = $page->getDescription();
                } catch (ExceptionNotFound $e) {
                    LogUtility::msg("The description metadata is mandatory for a event page", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    return null;
                }

                $ldJson["description"] = $eventDescription;
                try {
                    $startDate = $page->getStartDate();
                } catch (ExceptionNotFound $e) {
                    LogUtility::msg("The date_start metadata is mandatory for a event page", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    return null;
                }
                $ldJson["startDate"] = $startDate->format(Iso8601Date::getFormat());

                try {
                    $endDate = $page->getEndDate();
                } catch (ExceptionNotFound $e) {
                    LogUtility::msg("The date_end metadata is mandatory for a event page", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    return null;
                }
                $ldJson["endDate"] = $endDate->format(Iso8601Date::getFormat());


                self::addImage($ldJson, $page);
                break;


            default:

                // May be added manually by the user itself
                $ldJson = array(
                    '@context' => 'https://schema.org',
                    '@type' => $type,
                    'url' => $page->getAbsoluteCanonicalUrl()->toString()
                );
                break;
        }


        /**
         * https://developers.google.com/search/docs/data-types/speakable
         */
        $speakableXpath = array();
        $speakableXpath[] = "/html/head/title";
        try {
            PageDescription::createForPage($page)
                ->getValue();
            /**
             * Only the description written otherwise this is not speakable
             * you can have link and other strangeness
             */
            $speakableXpath[] = "/html/head/meta[@name='description']/@content";
        } catch (ExceptionNotFound $e) {
            // ok, no description
        }
        $ldJson[self::SPEAKABLE] = array(
            "@type" => "SpeakableSpecification",
            "xpath" => $speakableXpath
        );

        /**
         * merge with the extra
         */
        if ($actualValue !== null) {
            return array_merge($ldJson, $actualValue);
        }
        return $ldJson;
    }


    static public function isOnForm(): bool
    {
        return true;
    }


}
