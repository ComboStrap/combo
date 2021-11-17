<?php

namespace ComboStrap;


use action_plugin_combo_qualitymessage;
use DateTime;
use dokuwiki\Cache\CacheInstructions;
use dokuwiki\Cache\CacheRenderer;
use dokuwiki\Extension\Event;
use dokuwiki\Extension\SyntaxPlugin;
use Exception;
use renderer_plugin_combo_analytics;
use RuntimeException;
use syntax_plugin_combo_frontmatter;


/**
 * Page
 */
require_once(__DIR__ . '/PluginUtility.php');

/**
 *
 * Class Page
 * @package ComboStrap
 *
 * This is just a wrapper around a file with the mime Dokuwiki
 * that has a doku path (ie with the `:` separator)
 */
class Page extends DokuPath
{
    const CANONICAL_PROPERTY = 'canonical';
    const TITLE_META_PROPERTY = 'title';

    const CONF_DISABLE_FIRST_IMAGE_AS_PAGE_IMAGE = "disableFirstImageAsPageImage";

    const FIRST_IMAGE_META_RELATION = "firstimage";

    const NOT_MODIFIABLE_METAS = [
        "date",
        "user",
        "last_change",
        "creator",
        "contributor"
    ];

    /**
     * An indicator in the meta
     * that set a boolean to true or false
     * to categorize a page as low quality
     * It can be set manually via the {@link \syntax_plugin_combo_frontmatter front matter}
     * otherwise the {@link \renderer_plugin_combo_analytics}
     * will do it
     */
    const LOW_QUALITY_PAGE_INDICATOR = 'low_quality_page';

    /**
     * @link https://ogp.me/#types Facebook ogp
     * @link https://www.dublincore.org/specifications/dublin-core/dcmi-terms/#http://purl.org/dc/elements/1.1/type Dublin Core
     */
    const TYPE_META_PROPERTY = "type";
    const WEBSITE_TYPE = "website";
    const ARTICLE_TYPE = "article";
    const EVENT_TYPE = "event";
    const ORGANIZATION_TYPE = "organization";
    const NEWS_TYPE = "news";
    const BLOG_TYPE = "blog";
    const HOME_TYPE = "home";
    const WEB_PAGE_TYPE = "webpage";
    const OTHER_TYPE = "other";

    const NAME_PROPERTY = "name";
    const DESCRIPTION_PROPERTY = "description";
    /**
     * Default page type configuration
     */
    const CONF_DEFAULT_PAGE_TYPE = "defaultPageType";
    const CONF_DEFAULT_PAGE_TYPE_DEFAULT = self::ARTICLE_TYPE;

    /**
     * The scope is the namespace used to store the cache
     *
     * It can be set by a component via the {@link p_set_metadata()}
     * in a {@link SyntaxPlugin::handle()} function
     *
     * This is mostly used on side slots to
     * have several output of a list {@link \syntax_plugin_combo_pageexplorer navigation pane}
     * for different namespace (ie there is one cache by namespace)
     *
     * The special value current means the namespace of the requested page
     */
    const SCOPE_KEY = "scope";
    /**
     * The special scope value current means the namespace of the requested page
     * The real scope value is then calculated before retrieving the cache
     */
    const SCOPE_CURRENT_VALUE = "current";


    const IMAGE_META_PROPERTY = 'image';
    const REGION_META_PROPERTY = "region";
    const LANG_META_PROPERTY = "lang";
    public const SLUG_ATTRIBUTE = "slug";
    const LAYOUT_PROPERTY = "layout";
    const PAGE_ID_ATTRIBUTE = "page_id";
    const PAGE_ID_ABBR_ATTRIBUTE = "page_id_abbr";
    const DOKUWIKI_ID_ATTRIBUTE = "id";
    const KEYWORDS_ATTRIBUTE = "keywords";

    public const HOLY_LAYOUT_VALUE = "holy";
    public const LANDING_LAYOUT_VALUE = "landing";
    public const MEDIAN_LAYOUT_VALUE = "median";
    const LOW_QUALITY_INDICATOR_CALCULATED = "low_quality_indicator_calculated";

    const OLD_REGION_PROPERTY = "country";
    const ALIAS_ATTRIBUTE = "alias";
    // Length to get the same probability than uuid v4
    const PAGE_ID_LENGTH = 21;
    // The page id abbreviation is used in the url
    // to make them unique.
    //
    // A website is not git but an abbreviation of 7
    // is enough for a website.
    //
    // 7 is also the initial length of the git has abbreviation
    //
    // It gives a probability of collision of 1 percent
    // for 24 pages creation by day over a period of 100 year
    // (You need to create 876k pages).
    // with the 36 alphabet
    // https://datacadamia.com/crypto/hash/collision
    const PAGE_ID_ABBREV_LENGTH = 7;
    // No separator, no uppercase to be consistent on the whole url
    const PAGE_ID_ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyz';

    /**
     * The canonical for the canonical url
     */
    const CANONICAL_CANONICAL_URL = "canonical-url";
    public const CONF_CANONICAL_URL_TYPE_DEFAULT = self::CONF_CANONICAL_URL_TYPE_VALUE_PAGE_PATH;
    public const CONF_CANONICAL_URL_TYPE_VALUE_SLUG = "slug";
    public const CONF_CANONICAL_URL_TYPE = "pageUrlType";
    public const CONF_CANONICAL_URL_TYPE_VALUE_HIERARCHICAL_SLUG = "hierarchical slug";
    public const CONF_CANONICAL_URL_TYPE_VALUE_HOMED_SLUG = "homed slug";
    public const CONF_CANONICAL_URL_TYPE_VALUE_PERMANENT_CANONICAL_PATH = "permanent canonical path";
    public const CONF_CANONICAL_URL_TYPE_VALUE_PERMANENT_PAGE_PATH = "permanent page path";
    public const CONF_CANONICAL_URL_TYPE_VALUE_CANONICAL_PATH = "canonical path";
    public const CONF_CANONICAL_URL_TYPE_VALUE_PAGE_PATH = "page path";
    public const CONF_CANONICAL_URL_TYPE_VALUES = [
        Page::CONF_CANONICAL_URL_TYPE_VALUE_PAGE_PATH,
        Page::CONF_CANONICAL_URL_TYPE_VALUE_PERMANENT_PAGE_PATH,
        Page::CONF_CANONICAL_URL_TYPE_VALUE_CANONICAL_PATH,
        Page::CONF_CANONICAL_URL_TYPE_VALUE_PERMANENT_CANONICAL_PATH,
        Page::CONF_CANONICAL_URL_TYPE_VALUE_SLUG,
        Page::CONF_CANONICAL_URL_TYPE_VALUE_HOMED_SLUG,
        Page::CONF_CANONICAL_URL_TYPE_VALUE_HIERARCHICAL_SLUG
    ];


    /**
     *
     * The page id is separated in the URL with a "-"
     * and not the standard "/"
     * because in the devtool or any other tool, they takes
     * the last part of the path as name.
     *
     * The name would be the short page id `22h2s2j4`
     * and would have therefore no signification
     *
     * Instead the name is `metadata-manager-22h2s2j4`
     * we can see then a page description, even order on it
     */
    const PAGE_ID_URL_SEPARATOR = "-";

    /**
     * When the value of a metadata has changed
     */
    const PAGE_METADATA_MUTATION_EVENT = "PAGE_METADATA_MUTATION_EVENT";
    const DESCRIPTION_DOKUWIKI_ORIGIN = "dokuwiki";


    /**
     * @var array|array[]
     */
    private $metadatas;
    /**
     * @var string|null - the description (the origin is in the $descriptionOrigin)
     */
    private $description;
    /**
     * @var string - the dokuwiki
     */
    private $descriptionOrigin;


    /**
     * @var bool Indicator to say if this is a sidebar (or sidekick bar)
     */
    private $isSideSlot = false;

    /**
     * The id requested (ie the main page)
     * The page may be a slot
     * @var string
     */
    private $requestedId;
    /**
     * @var DatabasePage
     */
    private $databasePage;
    private $canonical;
    private $h1;
    private $pageName;
    private $type;
    private $title;
    private $author;
    private $authorId;
    private $lowQualityIndicator;
    private $region;
    private $lang;
    /**
     * @var string
     */
    private $pageId;
    /**
     * @var boolean|null
     */
    private $isLowQualityIndicator;
    /**
     * @var boolean|null
     */
    private $defaultLowQuality;
    private $layout;
    /**
     * @var Alias[]
     */
    private $aliases;
    /**
     * @var a slug path
     */
    private $slug;
    /**
     * @var string the generated description from the content
     */
    private $descriptionDefault;

    /**
     * The scope of the page
     * (used mostly in side slot, to see if the content
     * is for the current requested namespace or not)
     * @var string|null
     */
    private $scope;
    private $dynamicQualityIndicator;

    /**
     * @var string the alias used to build this page
     */
    private $buildAliasPath;

    /**
     * Page constructor.
     * @param $absolutePath - the qualified path (may be not relative)
     *
     */
    public function __construct($absolutePath)
    {

        /**
         * Bars have a logical reasoning (ie such as a virtual, alias)
         * They are logically located in the same namespace
         * but the file may be located on the parent
         *
         * This block of code is processing this case
         */
        global $conf;
        $sidebars = array($conf['sidebar']);
        $strapTemplateName = 'strap';
        if ($conf['template'] === $strapTemplateName) {
            $sidebars[] = $conf['tpl'][$strapTemplateName]['sidekickbar'];
        }
        $lastPathPart = DokuPath::getLastPart($absolutePath);
        if (in_array($lastPathPart, $sidebars)) {

            $this->isSideSlot = true;

            /**
             * Find the first physical file
             * Don't use ACL otherwise the ACL protection event 'AUTH_ACL_CHECK' will kick in
             * and we got then a recursive problem
             * with the {@link \action_plugin_combo_pageprotection}
             */
            $useAcl = false;
            $id = page_findnearest($lastPathPart, $useAcl);
            if ($id !== false) {
                $absolutePath = DokuPath::PATH_SEPARATOR . $id;
            }

        }

        global $ID;
        $this->requestedId = $ID;


        parent::__construct($absolutePath, DokuPath::PAGE_TYPE);

        /**
         * After the parent construction because we need the id
         * and it's set in the {@link DokuPath}
         * When the Page will be os file system based
         * and not dokuwiki file system based we may change that
         */
        $this->buildPropertiesFromFileSystem();

    }

    public static function createPageFromGlobalDokuwikiId(): Page
    {
        global $ID;
        return self::createPageFromId($ID);
    }

    public static function createPageFromId($id): Page
    {
        return new Page(DokuPath::PATH_SEPARATOR . $id);
    }

    public static function createPageFromNonQualifiedPath($pathOrId): Page
    {
        global $ID;
        $qualifiedId = $pathOrId;
        resolve_pageid(getNS($ID), $qualifiedId, $exists);
        /**
         * Root correction
         * yeah no root functionality in the {@link resolve_pageid resolution}
         * meaning that we get an empty string
         * they correct it in the link creation {@link wl()}
         */
        if ($qualifiedId === '') {
            global $conf;
            $qualifiedId = $conf['start'];
        }
        return Page::createPageFromId($qualifiedId);

    }

    public static function getShortEncodedPageIdFromUrlId($lastPartName)
    {
        $lastPosition = strrpos($lastPartName, Page::PAGE_ID_URL_SEPARATOR);
        if ($lastPosition === false) {
            return null;
        }
        return substr($lastPartName, $lastPosition + 1);
    }

    /**
     * @return Page - the requested page
     */
    public static function createPageFromRequestedPage(): Page
    {
        $pageId = PluginUtility::getMainPageDokuwikiId();
        if ($pageId != null) {
            return Page::createPageFromId($pageId);
        } else {
            LogUtility::msg("We were unable to determine the page from the variables environment", LogUtility::LVL_MSG_ERROR);
            return Page::createPageFromId("unknown-requested-page");
        }
    }


    /**
     * @param string $pageId
     * @return string|null - the checksum letter or null if this is not a page id
     */
    public static function getPageIdChecksumCharacter(string $pageId): ?string
    {
        $total = 0;
        for ($i = 0; $i < strlen($pageId); $i++) {
            $letter = $pageId[$i];
            $pos = strpos(self::PAGE_ID_ALPHABET, $letter);
            if ($pos === false) {
                return null;
            }
            $total += $pos;
        }
        $checkSum = $total % strlen(self::PAGE_ID_ALPHABET);
        return self::PAGE_ID_ALPHABET[$checkSum];
    }

    /**
     * Add a checksum character to the page id
     * to check if it's a page id that we get in the url
     * @param string $pageId
     * @return string
     */
    public static function encodePageId(string $pageId): string
    {
        return self::getPageIdChecksumCharacter($pageId) . $pageId;
    }

    /**
     * @param string $encodedPageId
     * @return string|null return the decoded page id or null if it's not an encoded page id
     */
    public static function decodePageId(string $encodedPageId): ?string
    {
        if (empty($encodedPageId)) return null;
        $checkSum = $encodedPageId[0];
        $extractedEncodedPageId = substr($encodedPageId, 1);
        $calculatedCheckSum = self::getPageIdChecksumCharacter($extractedEncodedPageId);
        if ($calculatedCheckSum == null) return null;
        if ($calculatedCheckSum != $checkSum) return null;
        return $extractedEncodedPageId;
    }


    public static function getHomePageFromNamespace(string $namespacePath): Page
    {
        global $conf;

        if ($namespacePath != ":") {
            $namespacePath = $namespacePath . ":";
        }

        $startPageName = $conf['start'];
        if (page_exists($namespacePath . $startPageName)) {
            // start page inside namespace
            return self::createPageFromId($namespacePath . $startPageName);
        } elseif (page_exists($namespacePath . noNS(cleanID($namespacePath)))) {
            // page named like the NS inside the NS
            return self::createPageFromId($namespacePath . noNS(cleanID($namespacePath)));
        } elseif (page_exists($namespacePath)) {
            // page like namespace exists
            return self::createPageFromId(substr($namespacePath, 0, -1));
        } else {
            // Does not exist but can be used by hierarchical function
            return self::createPageFromId($namespacePath . $startPageName);
        }
    }

    /**
     * @param string $pageId
     * @return Page|null - a page or null, if the page id does not exist in the database
     */
    public static function getPageFromPageId(string $pageId): ?Page
    {
        $databasePage = DatabasePage::createFromPageId($pageId);
        return $databasePage->getPage();
    }


    /**
     * @var string the logical id is used with slots.
     *
     * A slot may exist in several node of the file system tree
     * but they can be rendered for a page in a lowest level
     * listing the page of the current namespace
     *
     * The slot is physically stored in one place but is equivalent
     * physically to the same slot in all sub-node.
     *
     * This logical id does take into account this aspect.
     *
     * This is used also to store the HTML output in the cache
     * If this is not a slot the logical id is the {@link DokuPath::getDokuwikiId()}
     */
    public
    function getLogicalId()
    {
        /**
         * Delete the first separator
         */
        return substr($this->getLogicalPath(), 1);
    }

    public function getLogicalPath(): string
    {

        /**
         * Set the logical id
         * When no $ID is set (for instance, test),
         * the logical id is the id
         *
         * The logical id depends on the namespace attribute of the {@link \syntax_plugin_combo_pageexplorer}
         * stored in the `scope` metadata.
         */
        $scopePath = $this->getScope();
        if ($scopePath !== null) {

            if ($scopePath == Page::SCOPE_CURRENT_VALUE) {
                $requestPage = Page::createPageFromRequestedPage();
                $scopePath = $requestPage->getNamespacePath();
            }

            if ($scopePath !== ":") {
                return $scopePath . DokuPath::PATH_SEPARATOR . $this->getDokuPathLastName();
            } else {
                return DokuPath::PATH_SEPARATOR . $this->getDokuPathLastName();
            }


        } else {

            return $this->getAbsolutePath();

        }


    }


    static function createPageFromQualifiedPath($qualifiedPath)
    {
        return new Page($qualifiedPath);
    }


    public function setCanonical($canonical): Page
    {
        if ($canonical === "") {
            $canonical = null;
        } else {
            $canonical = DokuPath::toValidAbsolutePath($canonical);
        }
        $this->canonical = $canonical;
        $this->setMetadata(Page::CANONICAL_PROPERTY, $this->canonical);
        return $this;
    }


    public
    function isSlot(): bool
    {
        global $conf;
        $barsName = array($conf['sidebar']);
        $strapTemplateName = 'strap';
        if ($conf['template'] === $strapTemplateName) {
            $loaded = PluginUtility::loadStrapUtilityTemplateIfPresentAndSameVersion();
            if ($loaded) {
                $barsName[] = TplUtility::getHeaderSlotPageName();
                $barsName[] = TplUtility::getFooterSlotPageName();
                $barsName[] = TplUtility::getSideKickSlotPageName();
            }
        }
        return in_array($this->getDokuPathLastName(), $barsName);
    }

    public
    function isStrapSideSlot()
    {

        return $this->isSideSlot && Site::isStrapTemplate();

    }


    public
    function isStartPage()
    {
        global $conf;
        return $this->getDokuPathLastName() == $conf['start'];
    }

    /**
     * Return a canonical if set
     * otherwise derive it from the id
     * by taking the last two parts
     *
     * @return string
     */
    public
    function getCanonicalOrDefault(): ?string
    {

        $canonical = $this->getCanonical();
        if (empty($canonical)) {
            $canonical = $this->getDefaultCanonical();
        }
        return $canonical;

    }

    /**
     * Return the metadata stored in the file system
     * @return array|array[]
     */
    public
    function getMetadatas(): array
    {

        return $this->metadatas;

    }

    /**
     * Refresh from disk
     * @return $this
     */
    public
    function refresh(): Page
    {
        $this->buildPropertiesFromFileSystem();
        return $this;
    }

    /**
     *
     * @return Page[] the internal links or null
     */
    public
    function getInternalReferencedPages(): array
    {
        $metadata = $this->getMetadatas();
        if (key_exists(Metadata::CURRENT_METADATA, $metadata)) {
            $current = $metadata[Metadata::CURRENT_METADATA];
            if (key_exists('relation', $current)) {
                $relation = $current['relation'];
                if (is_array($relation)) {
                    if (key_exists('references', $relation)) {
                        $pages = [];
                        foreach (array_keys($relation['references']) as $referencePageId) {
                            $pages[$referencePageId] = Page::createPageFromId($referencePageId);
                        }
                        return $pages;
                    }
                }
            }
        }
        return [];
    }


    /**
     * @param string $mode delete the cache for the format XHTML and {@link renderer_plugin_combo_analytics::RENDERER_NAME_MODE}
     */
    public
    function deleteCache($mode = "xhtml")
    {

        if ($this->exists()) {

            $cache = $this->getInstructionsCache();
            $cache->removeCache();
            $this->deleteRenderCache($mode);

        }
    }

    public
    function deleteRenderCache($mode = "xhtml")
    {

        if ($this->exists()) {

            $cache = $this->getRenderCache($mode);
            $cache->removeCache();

        }

    }


    /**
     *
     * @return string - the full path to the meta file
     */
    public
    function getMetaFile()
    {
        return metaFN($this->getDokuwikiId(), '.meta');
    }

    /**
     * Set the page quality
     * @param boolean $value true if this is a low quality page rank false otherwise
     */
    public
    function setLowQualityIndicator(bool $value): Page
    {
        $this->lowQualityIndicator = $value;
        return $this->setQualityIndicatorAndDeleteCacheIfNeeded(self::LOW_QUALITY_PAGE_INDICATOR, $value);
    }

    /**
     * @return Page[] the backlinks
     * Duplicate of related
     *
     * Same as {@link DokuPath::getReferencedBy()} ?
     */
    public
    function getBacklinks(): array
    {
        $backlinks = array();
        /**
         * Same as
         * idx_get_indexer()->lookupKey('relation_references', $ID);
         */
        foreach (ft_backlinks($this->getDokuwikiId()) as $backlinkId) {
            $backlinks[$backlinkId] = Page::createPageFromId($backlinkId);
        }
        return $backlinks;
    }


    /**
     * Low page quality
     * @return bool true if this is a low internal page rank
     */
    function isLowQualityPage(): bool
    {

        $lowQualityIndicator = $this->getLowQualityIndicator();
        if ($lowQualityIndicator == null) {
            return $this->getDefaultLowQualityIndicator() === true;
        }
        return $lowQualityIndicator === true;


    }


    public
    function getLowQualityIndicator(): ?bool
    {

        return $this->lowQualityIndicator;

    }


    public
    function getH1()
    {

        $heading = $this->h1;
        if (!blank($heading)) {
            return $heading;
        } else {
            return null;
        }

    }

    /**
     * Return the Title
     */
    public
    function getTitle()
    {

        return $this->title;

    }

    /**
     * If true, the page is quality monitored (a note is shown to the writer)
     * @return null|bool
     */
    public
    function getDynamicQualityIndicator(): ?bool
    {
        return $this->dynamicQualityIndicator;
    }

    /**
     * @return string the title, or h1 if empty or the id if empty
     */
    public
    function getTitleNotEmpty(): string
    {
        $pageTitle = $this->getTitle();
        if ($pageTitle === null) {
            return $this->getDefaultTitle();
        } else {
            return $pageTitle;
        }

    }

    public
    function getH1NotEmpty()
    {

        $h1Title = $this->getH1();
        if ($h1Title == null) {
            return $this->getDefaultH1();
        } else {
            return $h1Title;
        }

    }

    public
    function getDescription(): ?string
    {

        return $this->description;

    }


    /**
     * @return string - the description or the dokuwiki generated description
     */
    public
    function getDescriptionOrElseDokuWiki(): ?string
    {
        if ($this->description == null) {
            return $this->getDefaultDescription();
        }
        return $this->description;
    }


    public
    function getContent()
    {
        /**
         * use {@link io_readWikiPage(wikiFN($id, $rev), $id, $rev)};
         */
        return rawWiki($this->getDokuwikiId());
    }


    public
    function isInIndex()
    {
        $Indexer = idx_get_indexer();
        $pages = $Indexer->getPages();
        $return = array_search($this->getDokuwikiId(), $pages, true);
        return $return !== false;
    }


    public
    function upsertContent($content, $summary = "Default"): Page
    {
        saveWikiText($this->getDokuwikiId(), $content, $summary);
        return $this;
    }

    public
    function addToIndex()
    {
        idx_addPage($this->getDokuwikiId());
    }

    public
    function getTypeNotEmpty()
    {
        $type = $this->getPersistentMetadata(self::TYPE_META_PROPERTY);
        if (isset($type)) {
            return $type;
        } else {
            return $this->getDefaultType();
        }
    }


    public
    function getFirstImage()
    {

        $relation = $this->getCurrentMetadata('relation');
        if (isset($relation[Page::FIRST_IMAGE_META_RELATION])) {
            $firstImageId = $relation[Page::FIRST_IMAGE_META_RELATION];
            if (empty($firstImageId)) {
                return null;
            } else {
                // The  metadata store the Id or the url
                // We transform them to a path id
                $pathId = $firstImageId;
                if (!media_isexternal($firstImageId)) {
                    $pathId = DokuPath::PATH_SEPARATOR . $firstImageId;
                }
                return Image::createImageFromDokuwikiAbsolutePath($pathId);
            }
        }
        return null;

    }

    /**
     * Return the media found in the index
     *
     * They are saved via the function {@link \Doku_Renderer_metadata::_recordMediaUsage()}
     * called by the {@link \Doku_Renderer_metadata::internalmedia()}
     *
     *
     * {@link \Doku_Renderer_metadata::externalmedia()} does not save them
     */
    public
    function getExistingInternalMediaIdFromTheIndex()
    {

        $medias = [];
        $relation = $this->getCurrentMetadata('relation');
        if (isset($relation['media'])) {
            /**
             * The relation is
             * $this->meta['relation']['media'][$src] = $exists;
             *
             */
            foreach ($relation['media'] as $src => $exists) {
                if ($exists) {
                    $medias[] = $src;
                }
            }
        }
        return $medias;

    }

    /**
     * An array of local/internal images that represents the same image
     * but in different dimension and ratio
     * (may be empty)
     * @return Image[]
     */
    public
    function getPageImagesAsImageOrDefault(): array
    {

        /**
         * Google accepts several images dimension and ratios
         * for the same image
         * We may get an array then
         */
        $pageImages = $this->getPageImagesObject();
        if (empty($pageImages)) {
            $defaultPageImage = $this->getDefaultPageImageObject();
            if ($defaultPageImage != null) {
                return [$defaultPageImage->getImage()];
            } else {
                return [];
            }
        } else {
            return array_map(
                function ($a) {
                    return $a->getImage();
                },
                $pageImages
            );
        }

    }


    /**
     * @return Image
     */
    public
    function getImage(): ?Image
    {

        $images = $this->getPageImagesAsImageOrDefault();
        if (sizeof($images) >= 1) {
            return $images[0];
        } else {
            return null;
        }

    }

    /**
     * Get author name
     *
     * @return string
     */
    public function getAuthor(): ?string
    {
        return $this->author;
    }

    /**
     * Get author ID
     *
     * @return string
     */
    public function getAuthorID(): ?string
    {

        return $this->authorId;
    }


    /**
     * @param $key
     * @return mixed|null
     */
    private
    function getPersistentMetadata($key)
    {
        $key = $this->getMetadatas()[Metadata::PERSISTENT_METADATA][$key];
        /**
         * Empty string return null
         * because Dokuwiki does not allow to delete keys
         * {@link p_set_metadata()}
         */
        return ($key ?: null);
    }

    public
    function getPersistentMetadatas(): array
    {
        return $this->getMetadatas()['persistent'];
    }

    /**
     * The modified date is the last modification date
     * the first time, this is the creation date
     * @return string|null
     */
    public
    function getModifiedDateAsString()
    {
        $modified = $this->getModifiedTime();
        return $modified != null ? $modified->format(Iso8601Date::getFormat()) : null;

    }

    private
    function getCurrentMetadata($key)
    {
        $key = $this->getMetadatas()[Metadata::CURRENT_METADATA][$key];
        /**
         * Empty string return null
         * because Dokuwiki does not allow to delete keys
         * {@link p_set_metadata()}
         */
        return ($key ?: null);
    }

    /**
     * Get the create date of page
     *
     * @return DateTime
     */
    public
    function getCreatedTime(): ?DateTime
    {
        $createdMeta = $this->getPersistentMetadata('date')['created'];
        if (empty($createdMeta)) {
            return null;
        } else {
            $datetime = new DateTime();
            $datetime->setTimestamp($createdMeta);
            return $datetime;
        }
    }

    /**
     * Get the modified date of page
     *
     * The modified date is the last modification date
     * the first time, this is the creation date
     *
     * @return DateTime
     */
    public
    function getModifiedTime(): \DateTime
    {
        $modified = $this->getCurrentMetadata('date')['modified'];
        if (empty($modified)) {
            return parent::getModifiedTime();
        } else {
            $datetime = new DateTime();
            $datetime->setTimestamp($modified);
            return $datetime;
        }
    }

    /**
     * Creation date can not be null
     * @return null|string
     */
    public
    function getCreatedDateAsString()
    {

        $created = $this->getCreatedTime();
        return $created != null ? $created->format(Iso8601Date::getFormat()) : null;

    }

    /**
     * Refresh the metadata (used only in test)
     *
     * Trigger a:
     *  a {@link p_render_metadata() metadata render}
     *  a {@link p_save_metadata() metadata save}
     *
     * Note that {@link p_get_metadata()} uses a strange recursion
     * There is a metadata recursion logic to avoid rendering
     * that is not easy to grasp
     * and therefore you may get no metadata and no backlinks
     */
    public
    function renderAndFlushMetadata(): Page
    {

        if (!$this->exists()) {
            return $this;
        }

        /**
         * Read/render the metadata from the file
         * with parsing
         */
        $this->metadatas = p_render_metadata($this->getDokuwikiId(), $this->metadatas);

        $this->flushMeta();

        $this->buildPropertiesFromFileSystem();

        /**
         * Return
         */
        return $this;

    }

    public
    function getLocaleRegion()
    {
        return $this->region;
    }

    public
    function getRegionOrDefault()
    {

        $region = $this->getLocaleRegion();
        if (!empty($region)) {
            return $region;
        } else {
            return Site::getLanguageRegion();
        }

    }

    public
    function getLang()
    {
        return $this->lang;
    }

    public
    function getLangOrDefault()
    {
        $lang = $this->getLang();
        if (empty($lang)) {
            return Site::getLang();
        }
        return $lang;
    }

    /**
     * Adapted from {@link FsWikiUtility::getHomePagePath()}
     * @return bool
     */
    public
    function isHomePage(): bool
    {
        global $conf;
        $startPageName = $conf['start'];
        if ($this->getDokuPathLastName() == $startPageName) {
            return true;
        } else {
            $namespaceName = noNS(cleanID($this->getNamespacePath()));
            if ($namespaceName == $this->getDokuPathLastName()) {
                /**
                 * page named like the NS inside the NS
                 * ie ns:ns
                 */
                $startPage = Page::createPageFromId(DokuPath::toDokuwikiId($this->getNamespacePath()) . DokuPath::PATH_SEPARATOR . $startPageName);
                if (!$startPage->exists()) {
                    return true;
                }
            }
        }
        return false;
    }


    public
    function getMetadata($key, $default = null)
    {
        $persistentMetadata = $this->getPersistentMetadata($key);
        if (empty($persistentMetadata)) {
            $persistentMetadata = $this->getCurrentMetadata($key);
        }
        if ($persistentMetadata == null) {
            return $default;
        } else {
            return $persistentMetadata;
        }
    }

    public
    function getPublishedTime(): ?DateTime
    {
        $property = Publication::DATE_PUBLISHED;
        $persistentMetadata = $this->getPersistentMetadata($property);
        if (empty($persistentMetadata)) {
            /**
             * Old metadata key
             */
            $persistentMetadata = $this->getPersistentMetadata("published");
            if (empty($persistentMetadata)) {
                return null;
            }
        }
        // Ms level parsing
        try {
            $dateTime = Iso8601Date::createFromString($persistentMetadata)->getDateTime();
        } catch (\Exception $e) {
            /**
             * Should not happen as the data is validate in entry
             * at the {@link \syntax_plugin_combo_frontmatter}
             */
            LogUtility::msg("The published date property ($property) of the page ($this) has a value  ($persistentMetadata) that is not valid.", LogUtility::LVL_MSG_ERROR, Iso8601Date::CANONICAL);
            return null;
        }
        return $dateTime;
    }


    /**
     * @return DateTime
     */
    public
    function getPublishedElseCreationTime(): ?DateTime
    {
        $publishedDate = $this->getPublishedTime();
        if (empty($publishedDate)) {
            $publishedDate = $this->getCreatedTime();
        }
        return $publishedDate;
    }


    public
    function isLatePublication()
    {
        return $this->getPublishedElseCreationTime() > new DateTime('now');
    }

    /**
     * The Url used:
     *   * in the link
     *   * in the canonical ref
     *   * in the site map
     * @param array $urlParameters
     * @return string|null
     */
    public function getCanonicalUrl(array $urlParameters = []): ?string
    {

        /**
         * Dokuwiki Methodology Taken from {@link tpl_metaheaders()}
         */
        if ($this->isRootHomePage()) {
            return DOKU_URL;
        }

        /**
         * We are not honoring the below configuration
         * https://www.dokuwiki.org/config:canonical
         * that could make the url relative
         */
        return wl($this->getUrlId(), $urlParameters, true, '&');


    }


    /**
     *
     * @return string|null - the locale facebook way
     */
    public
    function getLocale($default = null): ?string
    {
        $lang = $this->getLangOrDefault();
        if (!empty($lang)) {

            $country = $this->getRegionOrDefault();
            if (empty($country)) {
                $country = $lang;
            }
            return $lang . "_" . strtoupper($country);
        }
        return $default;
    }

    /**
     * @return array|null[] - a tuple of value for the description and description default
     */
    private function buildGetDescriptionAndDefault(): array
    {


        $this->descriptionOrigin = null;
        $this->description = null;

        $descriptionArray = $this->getMetadata(Page::DESCRIPTION_PROPERTY);
        if (empty($descriptionArray)) {
            return [null, null];
        }
        if (!array_key_exists('abstract', $descriptionArray)) {
            return [null, null];
        }

        $description = $descriptionArray['abstract'];
        $this->descriptionOrigin = self::DESCRIPTION_DOKUWIKI_ORIGIN;
        if (array_key_exists('origin', $descriptionArray)) {
            $this->descriptionOrigin = $descriptionArray['origin'];
            if ($this->descriptionOrigin !== self::DESCRIPTION_DOKUWIKI_ORIGIN) {
                return [$description, ""];
            }
        }

        // suppress the carriage return
        $description = str_replace("\n", " ", $descriptionArray['abstract']);
        // suppress the h1
        $description = str_replace($this->getH1NotEmpty(), "", $description);
        // Suppress the star, the tab, About
        $description = preg_replace('/(\*|\t|About)/im', "", $description);
        // Suppress all double space and trim
        $description = trim(preg_replace('/  /m', " ", $description));
        return [null, $description];


    }

    public
    function hasXhtmlCache(): bool
    {

        $renderCache = $this->getRenderCache("xhtml");
        return file_exists($renderCache->cache);

    }

    public
    function hasInstructionCache(): bool
    {

        $instructionCache = $this->getInstructionsCache();
        /**
         * $cache->cache is the file
         */
        return file_exists($instructionCache->cache);

    }

    public
    function toXhtml(): string
    {

        if (!$this->isStrapSideSlot()) {
            $template = Site::getTemplate();
            LogUtility::msg("This function renders only sidebar for the " . PluginUtility::getDocumentationHyperLink("strap", "strap template") . ". (Actual page: $this, actual template: $template)", LogUtility::LVL_MSG_ERROR);
            return "";
        }


        /**
         * Global ID is the ID of the HTTP request
         * (ie the page id)
         * We change it for the run
         * And restore it at the end
         */
        global $ID;
        $keep = $ID;
        $ID = $this->getDokuwikiId();

        /**
         * The code below is adapted from {@link p_cached_output()}
         * $ret = p_cached_output($file, 'xhtml', $pageid);
         *
         * We don't use {@link CacheRenderer}
         * because the cache key is the physical file
         */
        global $conf;
        $format = 'xhtml';

        $renderCache = $this->getRenderCache($format);
        if ($renderCache->useCache()) {
            $xhtml = $renderCache->retrieveCache(false);
            if (($conf['allowdebug'] || PluginUtility::isDevOrTest()) && $format == 'xhtml') {
                $logicalId = $this->getLogicalId();
                $scope = $this->getScope();
                $xhtml = "<div id=\"{$this->getCacheHtmlId()}\" style=\"display:none;\" data-logical-Id=\"$logicalId\" data-scope=\"$scope\" data-cache-op=\"hit\" data-cache-file=\"{$renderCache->cache}\"></div>" . $xhtml;
            }
        } else {

            /**
             * Get the instructions
             * Adapted from {@link p_cached_instructions()}
             */
            $instructionsCache = $this->getInstructionsCache();
            if ($instructionsCache->useCache()) {
                $instructions = $instructionsCache->retrieveCache();
            } else {
                // no cache - do some work
                $instructions = p_get_instructions($this->getContent());
                if (!$instructionsCache->storeCache($instructions)) {
                    $message = 'Unable to save cache file. Hint: disk full; file permissions; safe_mode setting ?';
                    msg($message, -1);
                    // close restore ID
                    $ID = $keep;
                    return "<div class=\"text-warning\">$message</div>";
                }
            }

            /**
             * Due to the instructions parsing, they may have been changed
             * by a component
             */
            $logicalId = $this->getLogicalId();
            $scope = $this->getScope();

            /**
             * Render
             */
            $xhtml = p_render($format, $instructions, $info);
            if ($info['cache'] && $renderCache->storeCache($xhtml)) {
                if (($conf['allowdebug'] || PluginUtility::isDevOrTest()) && $format == 'xhtml') {
                    $xhtml = "<div id=\"{$this->getCacheHtmlId()}\" style=\"display:none;\" data-logical-Id=\"$logicalId\" data-scope=\"$scope\" data-cache-op=\"created\" data-cache-file=\"{$renderCache->cache}\"></div>" . $xhtml;
                }
            } else {
                $renderCache->removeCache();   //   try to delete cachefile
                if (($conf['allowdebug'] || PluginUtility::isDevOrTest()) && $format == 'xhtml') {
                    $xhtml = "<div id=\"{$this->getCacheHtmlId()}\" style=\"display:none;\" data-logical-Id=\"$logicalId\" data-scope=\"$scope\" data-cache-op=\"forbidden\"></div>" . $xhtml;
                }
            }
        }

        // restore ID
        $ID = $keep;
        return $xhtml;

    }

    /**
     * @param string $outputFormat For instance, "xhtml" or {@links Analytics::RENDERER_NAME_MODE}
     * @return \dokuwiki\Cache\Cache the cache of the page
     *
     * Output of {@link DokuWiki_Syntax_Plugin::render()}
     *
     */
    public
    function getRenderCache(string $outputFormat)
    {

        if ($this->isStrapSideSlot()) {

            /**
             * Logical cache based on scope (ie logical id) is the scope and part of the key
             */
            return new CacheByLogicalKey($this, $outputFormat);

        } else {

            return new CacheRenderer($this->getDokuwikiId(), $this->getAbsoluteFileSystemPath(), $outputFormat);

        }
    }

    /**
     * @return CacheInstructions
     * The cache of the {@link CallStack call stack} (ie list of output of {@link DokuWiki_Syntax_Plugin::handle})
     */
    public
    function getInstructionsCache()
    {

        if ($this->isStrapSideSlot()) {

            /**
             * @noinspection PhpIncompatibleReturnTypeInspection
             * No inspection because this is not the same object interface
             * because we can't overide the constructor of {@link CacheInstructions}
             * but they should used the same interface (ie manipulate array data)
             */
            return new CacheInstructionsByLogicalKey($this);

        } else {

            return new CacheInstructions($this->getDokuwikiId(), $this->getAbsoluteFileSystemPath());

        }

    }

    public
    function deleteXhtmlCache()
    {
        $this->deleteCache("xhtml");
    }

    public
    function getAnchorLink(): string
    {
        $url = $this->getCanonicalUrl();
        $title = $this->getTitle();
        return "<a href=\"$url\">$title</a>";
    }


    /**
     * Without the `:` at the end
     * @return string
     */
    public
    function getNamespacePath(): string
    {
        $ns = getNS($this->getDokuwikiId());
        /**
         * False means root namespace
         */
        if ($ns == false) {
            return ":";
        } else {
            return ":$ns";
        }
    }


    public
    function getScope()
    {
        /**
         * The scope may change
         * during a run, we then read the metadata file
         * each time
         */
        if (isset(p_read_metadata($this->getDokuwikiId())["persistent"][Page::SCOPE_KEY])) {
            return p_read_metadata($this->getDokuwikiId())["persistent"][Page::SCOPE_KEY];
        } else {
            return null;
        }
    }

    /**
     * Return the id of the div HTML
     * element that is added for cache debugging
     */
    public
    function getCacheHtmlId()
    {
        return "cache-" . str_replace(":", "-", $this->getDokuwikiId());
    }

    public
    function deleteMetadatasAndFlush(): Page
    {
        $meta = [Metadata::CURRENT_METADATA => [], Metadata::PERSISTENT_METADATA => []];
        p_save_metadata($this->getDokuwikiId(), $meta);
        return $this;
    }

    public
    function getPageName()
    {
        return $this->pageName;

    }

    public
    function getPageNameNotEmpty(): string
    {
        $name = $this->getPageName();
        if (!blank($name)) {
            return $name;
        } else {
            return $this->getDefaultPageName();
        }
    }

    /**
     * @param $property
     */
    public
    function unsetMetadata($property)
    {
        $meta = p_read_metadata($this->getDokuwikiId());
        if (isset($meta['persistent'][$property])) {
            unset($meta['persistent'][$property]);
        }
        p_save_metadata($this->getDokuwikiId(), $meta);

    }

    /**
     * @return array - return the standard / generated metadata
     * used in templating
     */
    public
    function getMetadataForRendering()
    {


        /**
         * The title/h1 should never be null
         * otherwise a template link such as [[$path|$title]] will return a link without an description
         * and therefore will be not visible
         * We render at least the id
         */
        $array[Analytics::H1] = $this->getH1NotEmpty();
        $title = $this->getTitleNotEmpty();
        /**
         * Hack: Replace every " by a ' to be able to detect/parse the title/h1 on a pipeline
         * @see {@link \syntax_plugin_combo_pipeline}
         */
        $title = str_replace('"', "'", $title);
        $array[Analytics::TITLE] = $title;
        $array[Page::PAGE_ID_ATTRIBUTE] = $this->getPageId();
        $array[Page::CANONICAL_PROPERTY] = $this->getCanonicalOrDefault();
        $array[Analytics::PATH] = $this->getAbsolutePath();
        $array[Analytics::DESCRIPTION] = $this->getDescriptionOrElseDokuWiki();
        $array[Analytics::NAME] = $this->getPageNameNotEmpty();
        $array["url"] = $this->getCanonicalUrl();
        $array[self::TYPE_META_PROPERTY] = $this->getTypeNotEmpty() !== null ? $this->getTypeNotEmpty() : "";

        /**
         * When creating a page, the file
         * may not be saved, causing a
         * filemtime(): stat failed for pages/test.txt in lib\plugins\combo\ComboStrap\File.php on line 62
         *
         */
        if ($this->exists()) {
            $array[Analytics::DATE_CREATED] = $this->getCreatedDateAsString();
            $array[Analytics::DATE_MODIFIED] = $this->getModifiedDateAsString();
        }

        $array[Publication::DATE_PUBLISHED] = $this->getPublishedTimeAsString();
        $array[Analytics::DATE_START] = $this->getStartDateAsString();
        $array[Analytics::DATE_END] = $this->getStartDateAsString();
        $array[Page::LAYOUT_PROPERTY] = $this->getMetadata(Page::LAYOUT_PROPERTY);

        return $array;

    }

    public
    function __toString()
    {
        return $this->getDokuwikiId();
    }

    /**
     * Change a meta on file
     * and triggers the {@link Page::PAGE_METADATA_MUTATION_EVENT} event
     *
     * @param $key
     * @param $value
     */
    public
    function setMetadata($key, $value)
    {

        $oldValue = $this->metadatas[Metadata::PERSISTENT_METADATA][$key];
        if (is_bool($value)) {
            $oldValue = Boolean::toBoolean($value);
        }
        if ($oldValue !== $value) {

            if ($value !== null) {
                $this->metadatas['persistent'][$key] = $value;
            } else {
                unset($this->metadatas['persistent'][$key]);
            }
            /**
             * Metadata in Dokuwiki is fucked up.
             *
             * You can't remove a metadata,
             * You need to known if this is a rendering or not
             *
             * See just how fucked {@link p_set_metadata()} is
             *
             * Also don't change the type of the value to a string
             * otherwise dokuwiki will not see a change
             * between true and a string and will not persist the value
             */
            p_set_metadata($this->getDokuwikiId(),
                [
                    $key => $value
                ]
            );
            /**
             * Event
             */
            $data = [
                "name" => $key,
                "new_value" => $value,
                "old_value" => $oldValue
            ];
            Event::createAndTrigger(Page::PAGE_METADATA_MUTATION_EVENT, $data);
        }

    }

    public
    function getPublishedTimeAsString(): ?string
    {
        return $this->getPublishedTime() !== null ? $this->getPublishedTime()->format(Iso8601Date::getFormat()) : null;
    }

    public
    function getEndDateAsString(): ?string
    {
        return $this->getEndDate() !== null ? $this->getEndDate()->format(Iso8601Date::getFormat()) : null;
    }

    public
    function getEndDate(): ?DateTime
    {
        $dateEndProperty = Analytics::DATE_END;
        $persistentMetadata = $this->getPersistentMetadata($dateEndProperty);
        if (empty($persistentMetadata)) {
            return null;
        }

        // Ms level parsing
        // Ms level parsing
        try {
            $dateTime = Iso8601Date::createFromString($persistentMetadata)->getDateTime();
        } catch (\Exception $e) {
            /**
             * Should not happen as the data is validate in entry
             * at the {@link \syntax_plugin_combo_frontmatter}
             */
            LogUtility::msg("The property $dateEndProperty of the page ($this) has a value ($persistentMetadata) that is not valid.", LogUtility::LVL_MSG_ERROR, Iso8601Date::CANONICAL);
            return null;
        }
        return $dateTime;
    }

    public
    function getStartDateAsString(): ?string
    {
        return $this->getStartDate() !== null ? $this->getStartDate()->format(Iso8601Date::getFormat()) : null;
    }

    public
    function getStartDate(): ?DateTime
    {
        $dateStartProperty = Analytics::DATE_START;
        $persistentMetadata = $this->getPersistentMetadata($dateStartProperty);
        if (empty($persistentMetadata)) {
            return null;
        }

        // Ms level parsing
        try {
            $dateTime = Iso8601Date::createFromString($persistentMetadata)->getDateTime();
        } catch (\Exception $e) {
            /**
             * Should not happen as the data is validate in entry
             * at the {@link \syntax_plugin_combo_frontmatter}
             */
            LogUtility::msg("The start date property $dateStartProperty of the page ($this) has a value ($persistentMetadata) that is not valid.", LogUtility::LVL_MSG_ERROR, Iso8601Date::CANONICAL);
            return null;
        }
        return $dateTime;
    }

    /**
     * A page id or null if the page does not exists
     * @return string|null
     */
    public
    function getPageId(): ?string
    {

        return $this->pageId;

    }


    public
    function getAnalytics(): Analytics
    {
        return new Analytics($this);
    }

    public
    function getDatabasePage(): DatabasePage
    {
        if ($this->databasePage == null) {
            $this->databasePage = DatabasePage::createFromPageObject($this);
        }
        return $this->databasePage;
    }

    public
    function canBeUpdatedByCurrentUser(): bool
    {
        return Identity::isWriter($this->getDokuwikiId());
    }

    /**
     * Frontmatter / Manager Metadata Update
     * @param $attributes
     * @param boolean|false $persistOnlyKnownAttributes - if strict, unknown parameter will not be added and return an error message
     * @return Message[] array - all messages (error, info, ..)
     */
    public function upsertMetadataFromAssociativeArray($attributes, bool $persistOnlyKnownAttributes = false): array
    {

        /**
         * Attribute to set
         * The set function modify the value to be valid
         * or does not store them at all
         */
        $messages = [];
        foreach ($attributes as $key => $value) {

            $lowerKey = trim(strtolower($key));
            if (in_array($lowerKey, self::NOT_MODIFIABLE_METAS)) {
                $messages[] = Message::createWarningMessage("The metadata ($lowerKey) is a protected metadata and cannot be modified")
                    ->setCanonical(Metadata::CANONICAL);
                continue;
            }
            try {
                switch ($lowerKey) {
                    case self::CANONICAL_PROPERTY:
                        $this->setCanonical($value);
                        continue 2;
                    case Analytics::DATE_END:
                        $this->setEndDate($value);
                        continue 2;
                    case Page::TYPE_META_PROPERTY:
                        $this->setPageType($value);
                        continue 2;
                    case Analytics::DATE_START:
                        $this->setStartDate($value);
                        continue 2;
                    case Publication::DATE_PUBLISHED:
                        $this->setPublishedDate($value);
                        continue 2;
                    case Page::DESCRIPTION_PROPERTY:
                        $this->setDescription($value);
                        continue 2;
                    case Page::NAME_PROPERTY:
                        $this->setPageName($value);
                        continue 2;
                    case Page::TITLE_META_PROPERTY:
                        $this->setTitle($value);
                        continue 2;
                    case Analytics::H1:
                        $this->setH1($value);
                        continue 2;
                    case \action_plugin_combo_metagoogle::JSON_LD_META_PROPERTY:
                        $this->setJsonLd($value);
                        continue 2;
                    case Page::REGION_META_PROPERTY:
                        $this->setRegion($value);
                        continue 2;
                    case Page::LANG_META_PROPERTY:
                        $this->setLang($value);
                        continue 2;
                    case Page::LAYOUT_PROPERTY:
                        $this->setLayout($value);
                        continue 2;
                    case Page::ALIAS_ATTRIBUTE:
                        $aliases = Alias::toAliasArray($value, $this);
                        $this->setAliases($aliases);
                        continue 2;
                    case Page::PAGE_ID_ATTRIBUTE:
                        if ($this->getPageId() === null) {
                            $this->setPageId($value);
                        } else {
                            if ($this->getPageId() !== $value) {
                                $messages[] = Message::createErrorMessage("The page id is a managed id and cannot be changed, this page has the id ({$this->getPageId()}) that has not the same value than in the frontmatter ({$value})")
                                    ->setCanonical(Page::PAGE_ID_ATTRIBUTE);
                            }
                        }
                        continue 2;
                    case Page::LOW_QUALITY_PAGE_INDICATOR:
                        $this->setLowQualityIndicator(Boolean::toBoolean($value));
                        continue 2;
                    case PAGE::IMAGE_META_PROPERTY:
                        $this->setPageImage($value);
                        continue 2;
                    case action_plugin_combo_qualitymessage::DYNAMIC_QUALITY_MONITORING_INDICATOR:
                        $this->setMonitoringQualityIndicator(Boolean::toBoolean($value));
                        continue 2;
                    case PAGE::KEYWORDS_ATTRIBUTE:
                        $this->setMetadata($key, $value);
                        continue 2;
                    case PAGE::SLUG_ATTRIBUTE:
                        $this->setSlug($value);
                        continue 2;
                    default:
                        if (!$persistOnlyKnownAttributes) {
                            $messages[] = Message::createInfoMessage("The metadata ($lowerKey) is unknown but was saved with the value ($value)")
                                ->setCanonical(Metadata::CANONICAL);
                            $this->setMetadata($key, $value);
                        } else {
                            $messages[] = Message::createErrorMessage("The metadata ($lowerKey) is unknown and was not saved")
                                ->setCanonical(Metadata::CANONICAL);
                        }
                        continue 2;
                }
            } catch (Exception $e) {
                $message = Message::createErrorMessage($e->getMessage());
                if ($e instanceof ExceptionCombo) {
                    $message->setCanonical($e->getCanonical());
                }
                $messages[] = $message;
            }


        }

        /**
         * Database update
         */
        try {
            $this->getDatabasePage()->replicateMetaAttributes();
        } catch (Exception $e) {
            $message = Message::createErrorMessage($e->getMessage());
            if ($e instanceof ExceptionCombo) {
                $message->setCanonical($e->getCanonical());
            }
            $messages[] = $message;
        }

        return $messages;

    }


    public
    function isRootHomePage(): bool
    {
        global $conf;
        $startPageName = $conf['start'];
        return $this->getPath() === ":$startPageName";

    }

    /**
     * Used when the page is moved to take the Page Id of the source
     * @param string|null $pageId
     * @return Page
     */
    public
    function setPageId(?string $pageId): Page
    {
        if ($pageId == null) {
            LogUtility::msg("A page id can not null when setting it (Page: $this)", LogUtility::LVL_MSG_ERROR);
            return $this;
        }
        $this->pageId = $pageId;
        $this->setMetadata(Page::PAGE_ID_ATTRIBUTE, $pageId);
        return $this;

    }

    public
    function getType()
    {
        return $this->type;
    }

    public
    function getCanonical()
    {
        return $this->canonical;
    }

    /**
     * Create a canonical from the last page path part.
     *
     * @return string|null
     */
    public
    function getDefaultCanonical(): ?string
    {
        /**
         * The last part of the id as canonical
         */
        // How many last parts are taken into account in the canonical processing (2 by default)
        $canonicalLastNamesCount = PluginUtility::getConfValue(\action_plugin_combo_canonical::CONF_CANONICAL_LAST_NAMES_COUNT);
        if (empty($this->getCanonical()) && $canonicalLastNamesCount > 0) {
            /**
             * Takes the last names part
             */
            $namesOriginal = $this->getDokuNames();
            /**
             * Delete the identical names at the end
             * To resolve this problem
             * The page (viz:viz) and the page (data:viz:viz) have the same canonical.
             * The page (viz:viz) will get the canonical viz
             * The page (data:viz) will get the canonical  data:viz
             */
            $i = sizeof($namesOriginal) - 1;
            $names = $namesOriginal;
            while ($namesOriginal[$i] == $namesOriginal[$i - 1]) {
                unset($names[$i]);
                $i--;
                if ($i <= 0) {
                    break;
                }
            }
            /**
             * Minimal length check
             */
            $namesLength = sizeof($names);
            if ($namesLength > $canonicalLastNamesCount) {
                $names = array_slice($names, $namesLength - $canonicalLastNamesCount);
            }
            /**
             * If this is a start page, delete the name
             * ie javascript:start will become javascript
             */
            if ($this->isStartPage()) {
                $names = array_slice($names, 0, $namesLength - 1);
            }
            return implode(":", $names);
        }
        return null;
    }

    public
    function getLayout()
    {
        return $this->getMetadata(Page::LAYOUT_PROPERTY);
    }

    public
    function getDefaultPageName(): string
    {
        $pathName = $this->getDokuPathLastName();
        /**
         * If this is a home page, the default
         * is the parent path name
         */
        if ($pathName === Site::getHomePageName()) {
            $names = $this->getDokuNames();
            $namesCount = sizeof($names);
            if ($namesCount >= 2) {
                $pathName = $names[$namesCount - 2];
            }
        }
        $words = preg_split("/\s/", preg_replace("/-|_/", " ", $pathName));
        $wordsUc = [];
        foreach ($words as $word) {
            $wordsUc[] = ucfirst($word);
        }
        return implode(" ", $wordsUc);
    }

    public
    function getDefaultTitle(): ?string
    {
        if ($this->isRootHomePage() && !empty(Site::getTagLine())) {
            return Site::getTagLine();
        }
        if (!empty($this->getH1())) {
            return $this->getH1();
        }
        return $this->getPageNameNotEmpty();

    }

    public
    function getDefaultH1()
    {
        $h1Parsed = $this->getMetadata(Analytics::H1_PARSED);
        if (!empty($h1Parsed)) {
            return $h1Parsed;
        }

        if (!empty($this->getTitle())) {
            return $this->getTitle();
        } else {
            return $this->getPageNameNotEmpty();
        }
    }

    public
    function getDefaultType()
    {
        if ($this->isRootHomePage()) {
            return self::WEBSITE_TYPE;
        } else if ($this->isHomePage()) {
            return self::HOME_TYPE;
        } else {
            $defaultPageTypeConf = PluginUtility::getConfValue(self::CONF_DEFAULT_PAGE_TYPE, self::CONF_DEFAULT_PAGE_TYPE_DEFAULT);
            if (!empty($defaultPageTypeConf)) {
                return $defaultPageTypeConf;
            } else {
                return null;
            }
        }
    }

    public
    function getDefaultLayout(): string
    {
        return "holy";
    }

    public
    function getTypeValues(): array
    {
        $types = [Page::ORGANIZATION_TYPE, Page::ARTICLE_TYPE, Page::NEWS_TYPE, Page::BLOG_TYPE, Page::WEBSITE_TYPE, Page::EVENT_TYPE, Page::HOME_TYPE, Page::WEB_PAGE_TYPE, Page::OTHER_TYPE];
        sort($types);
        return $types;
    }

    public
    function getLayoutValues(): array
    {
        return [Page::HOLY_LAYOUT_VALUE, Page::MEDIAN_LAYOUT_VALUE, Page::LANDING_LAYOUT_VALUE];
    }

    public
    function setDefaultLowQualityIndicator($bool): Page
    {
        $this->defaultLowQuality = $bool;
        return $this->setQualityIndicatorAndDeleteCacheIfNeeded(self::LOW_QUALITY_INDICATOR_CALCULATED, $bool);
    }


    public
    function getMetadataAsBoolean(string $key): ?bool
    {
        return Boolean::toBoolean($this->getMetadata($key));
    }

    /**
     * Change the quality indicator
     * and if the quality level has become low
     * and that the protection is on, delete the cache
     * @param string $lowQualityAttributeName
     * @param $value
     * @return Page
     */
    private
    function setQualityIndicatorAndDeleteCacheIfNeeded(string $lowQualityAttributeName, $value): Page
    {
        $actualValue = $this->getMetadataAsBoolean($lowQualityAttributeName);
        if ($actualValue === null || $value !== $actualValue) {
            $beforeLowQualityPage = $this->isLowQualityPage();
            $this->setMetadata($lowQualityAttributeName, $value);
            $afterLowQualityPage = $this->isLowQualityPage();
            if ($beforeLowQualityPage !== $afterLowQualityPage) {
                /**
                 * Delete the cache to rewrite the links
                 * if the protection is on
                 */
                if (Site::isLowQualityProtectionEnable()) {
                    foreach ($this->getBacklinks() as $backlink) {
                        $backlink->deleteXhtmlCache();
                    }
                }
            }
        }
        return $this;
    }


    public
    function getDefaultLowQualityIndicator()
    {
        /**
         * By default, if a file has not been through
         * a {@link \renderer_plugin_combo_analytics}
         * analysis, this is a low page with protection enable
         * or not if not
         */
        $value = $this->getMetadataAsBoolean(self::LOW_QUALITY_INDICATOR_CALCULATED);
        if ($value !== null) return $value;

        /**
         * Migration code
         * The indicator {@link Page::LOW_QUALITY_INDICATOR_CALCULATED} is new
         * but if the analytics was done, we can get it
         */
        if ($this->getAnalytics()->exists()) {
            $value = $this->getAnalytics()->getData()->toArray()[Analytics::QUALITY][Analytics::LOW];
            if ($value !== null) return $value;
        }

        if (Site::isLowQualityProtectionEnable()) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * @param string|null $tag
     * @return PageImage[]|null
     */
    public
    function getPageImagesObject($tag = null): array
    {
        $pagesImages = $this->getMetadata(self::IMAGE_META_PROPERTY);
        if ($pagesImages === null) {
            return [];
        } else {
            if (is_array($pagesImages)) {
                $images = [];
                foreach ($pagesImages as $key => $value) {

                    $usage = PageImage::getDefaultUsage();
                    if (is_numeric($key)) {
                        $imagePath = $value;
                    } else {
                        $imagePath = $key;
                        if (is_array($value) && isset($value[PageImage::USAGE_ATTRIBUTE])) {
                            $usage = $value[PageImage::USAGE_ATTRIBUTE];
                            if (!is_array($usage)) {
                                $usage = [$usage];
                            }
                        }
                    }
                    DokuPath::addRootSeparatorIfNotPresent($imagePath);
                    $images[$imagePath] = PageImage::create($imagePath)
                        ->setUsage($usage);
                }
                return $images;
            } else {
                /**
                 * A single image
                 */
                DokuPath::addRootSeparatorIfNotPresent($pagesImages);
                return [$pagesImages => PageImage::create($pagesImages)];
            }
        }

    }

    /**
     * @param string|array $pageImageData
     * @return Page
     */
    public
    function setPageImage($pageImageData): Page
    {
        $this->setMetadata(self::IMAGE_META_PROPERTY, $pageImageData);
        return $this;
    }

    public
    function getLdJson()
    {
        $ldJson = $this->getMetadata(\action_plugin_combo_metagoogle::JSON_LD_META_PROPERTY);
        if (empty($ldJson) && $this->getTypeNotEmpty() === "organization") {
            // deprecated, old syntax
            $metadata = $this->getMetadata("organization");
            if (!empty($metadata)) {
                return ["organization" => $metadata];
            }
        }
        return $ldJson;
    }

    /**
     * @param array|string $jsonLd
     * @return $this
     */
    public
    function setJsonLd($jsonLd): Page
    {
        if (is_string($jsonLd)) {
            $jsonLdArray = json_decode($jsonLd, true);
            if ($jsonLdArray === false) {
                throw new ExceptionCombo("The json ld is not in a json format. " . Json::getValidationLink($jsonLd), \action_plugin_combo_metagoogle::CANONICAL);
            }
        } elseif (is_array($jsonLd)) {
            $jsonLdArray = $jsonLd;
        } else {
            throw new ExceptionCombo("The json ld value should be a string or an array", \action_plugin_combo_metagoogle::CANONICAL);
        }
        $this->setMetadata(\action_plugin_combo_metagoogle::JSON_LD_META_PROPERTY, $jsonLdArray);
        return $this;
    }

    public
    function setPageType(string $string): Page
    {
        $this->type = $string;
        $this->setMetadata(Page::TYPE_META_PROPERTY, $string);
        return $this;
    }

    public
    function getDefaultPageImageObject(): ?PageImage
    {
        if (!PluginUtility::getConfValue(self::CONF_DISABLE_FIRST_IMAGE_AS_PAGE_IMAGE)) {
            $firstImage = $this->getFirstImage();
            if ($firstImage != null) {
                if ($firstImage->getDokuPath()->getScheme() == DokuPath::LOCAL_SCHEME) {
                    return PageImage::create($firstImage);
                }
            }
        }
        return null;
    }

    public
    function addAndGetAlias($aliasPath, $aliasType): Alias
    {
        $aliases = $this->getAliases();
        $newAlias = Alias::create($this, $aliasPath);
        if (!blank($aliasType)) {
            $newAlias->setType($aliasType);
        }
        $aliases[$aliasPath] = $newAlias;
        $this->setMetadata(self::ALIAS_ATTRIBUTE, Alias::toMetadataArray($aliases));
        return $newAlias;
    }


    /**
     * @return Alias[]
     */
    public
    function getAliases(): array
    {
        /**
         * We don't do that on build because
         * we are using a set a metadata method that creates
         * a cycle via the {@link Page::PAGE_METADATA_MUTATION_EVENT}
         */
        if ($this->aliases === null) {
            $aliases = $this->getAndDeleteDeprecatedAlias();
            /**
             * To validate the migration we set a value
             * (the array may be empty)
             */
            $this->setAliases($aliases);
        }
        return $this->aliases;
    }

    /**
     * @return string|null
     *
     */
    public
    function getSlugOrDefault(): ?string
    {

        if ($this->getSlug() !== null) {
            return $this->getSlug();
        }
        return $this->getDefaultSlug();
    }

    /**
     *
     * @return string|null
     *
     */
    public function getDefaultSlug(): ?string
    {
        return DokuPath::toSlugPath($this->getTitleNotEmpty());
    }

    public
    function getParentPage(): ?Page
    {

        $names = $this->getDokuNames();
        if (sizeof($names) == 0) {
            return null;
        }
        $parentNames = array_slice($names, 0, sizeof($names) - 1);
        $parentNamespaceId = implode($parentNames, DokuPath::PATH_SEPARATOR);
        return self::getHomePageFromNamespace($parentNamespaceId);

    }

    public
    function setDescription($description): Page
    {

        if ($description === "") {
            $description = null;
        }

        /**
         * Dokuwiki has already a description
         * We use it to be conform
         */
        $this->description = $description;

        /**
         * Bug: We have passed an array and not the key
         * We have created therefore a description property below the description array
         * We delete it
         */
        $descriptionArray = $this->metadatas[Metadata::PERSISTENT_METADATA][Page::DESCRIPTION_PROPERTY];
        if ($descriptionArray != null && array_key_exists(Page::DESCRIPTION_PROPERTY, $descriptionArray)) {
            unset($this->metadatas[Metadata::PERSISTENT_METADATA][Page::DESCRIPTION_PROPERTY][Page::DESCRIPTION_PROPERTY]);
            $this->flushMeta();
        }

        /**
         *
         */
        if ($description !== null) {
            $this->setMetadata(Page::DESCRIPTION_PROPERTY,
                array(
                    "abstract" => $description,
                    "origin" => syntax_plugin_combo_frontmatter::CANONICAL
                ));
        } else {
            /**
             * It should not happen often
             * (Use default on the next page rendering unfortunately)
             */
            $this->setMetadata(Page::DESCRIPTION_PROPERTY,
                array(
                    "abstract" => "",
                    "origin" => Page::DESCRIPTION_DOKUWIKI_ORIGIN
                ));
        }
        return $this;
    }

    public
    function setEndDate($value)
    {
        if (Iso8601Date::isValid($value)) {
            $this->setMetadata(Analytics::DATE_END, $value);
        } else {
            LogUtility::msg("The end date value ($value) is not a valid date.", LogUtility::LVL_MSG_ERROR, Iso8601Date::CANONICAL);
        }
    }

    public
    function setStartDate($value)
    {
        if (Iso8601Date::isValid($value)) {
            $this->setMetadata(Analytics::DATE_START, $value);
        } else {
            LogUtility::msg("The start date value ($value) is not a valid date.", LogUtility::LVL_MSG_ERROR, Iso8601Date::CANONICAL);
        }
    }

    /**
     * @throws Exception
     */
    public
    function setPublishedDate($value)
    {
        if (Iso8601Date::isValid($value)) {
            $this->setMetadata(Publication::DATE_PUBLISHED, $value);
        } else {
            throw new ExceptionCombo("The published date value ($value) is not a valid date.", Iso8601Date::CANONICAL);
        }
    }

    public
    function setPageName($value): Page
    {
        if ($value === "") {
            $value = null;
        }
        $this->pageName = $value;
        $this->setMetadata(Page::NAME_PROPERTY, $value);
        return $this;

    }

    public
    function setTitle($value): Page
    {
        $this->title = $value;
        $this->setMetadata(Page::TITLE_META_PROPERTY, $value);
        return $this;
    }

    public
    function setH1($value): Page
    {
        $this->h1 = $value;
        $this->setMetadata(Analytics::H1, $value);
        return $this;
    }

    /**
     * @throws Exception
     */
    public
    function setRegion($value): Page
    {
        if ($value === "") {
            $value = null;
        } else {
            if (!StringUtility::match($value, "^[a-zA-Z]{2}$")) {
                throw new ExceptionCombo("The region value ($value) for the page ($this) does not have two letters (ISO 3166 alpha-2 region code)","region");
            }
        }
        $this->region = $value;
        $this->setMetadata(Page::REGION_META_PROPERTY, $value);
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public
    function setLang($value): Page
    {
        if ($value === "") {
            $value = null;
        } else {
            if (!StringUtility::match($value, "^[a-zA-Z]{2}$")) {
                throw new ExceptionCombo("The lang value ($value) for the page ($this) does not have two letters","lang");
            }
        }
        $this->lang = $value;
        $this->setMetadata(Page::LANG_META_PROPERTY, $value);
        return $this;
    }

    public
    function setLayout($value): Page
    {
        $this->layout = $value;
        $this->setMetadata(Page::LAYOUT_PROPERTY, $value);
        return $this;
    }

    /**
     * @param Alias[] $aliases
     */
    private
    function setAliases(array $aliases): Page
    {
        $this->aliases = $aliases;
        $this->setMetadata(self::ALIAS_ATTRIBUTE, Alias::toMetadataArray($aliases));
        return $this;
    }

    /**
     *
     * We manage the properties by setter and getter
     *
     * Why ?
     *   * Because we can capture the updates
     *   * Because setter are the entry point to good quality data
     *   * Because dokuwiki may cache the metadata (see below)
     *
     * Note all properties have been migrated
     * but they should be initialized below
     *
     * Dokuwiki cache: the data may be cached without our consent
     * The method {@link p_get_metadata()} does it with this logic
     * ```
     * $cache = ($ID == $id);
     * $meta = p_read_metadata($id, $cache);
     * ```
     */
    private
    function buildPropertiesFromFileSystem()
    {

        if (!$this->exists()) {
            $this->metadatas = [];
            return;
        }

        /**
         * Updating the metadata must happen first
         * All meta function depends on it
         *
         * Read / not {@link p_get_metadata()}
         * because it can trigger a rendering of the meta again)
         *
         * This is not a {@link Page::renderAndFlushMetadata()}
         */
        $this->metadatas = p_read_metadata($this->getDokuwikiId());

        $this->pageId = $this->getMetadata(self::PAGE_ID_ATTRIBUTE);
        $this->pageName = $this->getMetadata(self::NAME_PROPERTY);
        [$this->description, $this->descriptionDefault] = $this->buildGetDescriptionAndDefault();
        $this->h1 = $this->getMetadata(Analytics::H1);
        $this->canonical = $this->getMetadata(Page::CANONICAL_PROPERTY);
        $this->type = $this->getMetadata(self::TYPE_META_PROPERTY);
        /**
         * `title` is created by DokuWiki
         * in current but not persistent
         * and hold the heading 1, see {@link p_get_first_heading}
         */
        $this->title = $this->getMetadata(Analytics::TITLE);
        $this->author = $this->getMetadata('creator');
        $this->authorId = $this->getMetadata('user');

        $this->lowQualityIndicator = $this->getMetadataAsBoolean(self::LOW_QUALITY_PAGE_INDICATOR);

        $this->region = $this->getMetadata(self::REGION_META_PROPERTY);
        $this->lang = $this->getMetadata(self::LANG_META_PROPERTY);

        $this->isLowQualityIndicator = Boolean::toBoolean($this->getMetadata(self::LOW_QUALITY_PAGE_INDICATOR));
        $this->defaultLowQuality = Boolean::toBoolean($this->getMetadata(self::LOW_QUALITY_INDICATOR_CALCULATED));

        $this->layout = $this->getMetadata(self::LAYOUT_PROPERTY);

        try {
            $this->aliases = Alias::toAliasArray($this->getMetadata(self::ALIAS_ATTRIBUTE), $this);
        } catch (Exception $e) {
            LogUtility::msg("The key of the frontmatter alias should not be empty as it's the alias path", LogUtility::LVL_MSG_ERROR, Alias::CANONICAL);
        }
        $this->slug = $this->getMetadata(self::SLUG_ATTRIBUTE);

        $this->scope = $this->getMetadata(self::SCOPE_KEY);
        $this->dynamicQualityIndicator = Boolean::toBoolean($this->getMetadata(action_plugin_combo_qualitymessage::DYNAMIC_QUALITY_MONITORING_INDICATOR));

    }

    public
    function flushMeta(): Page
    {
        p_save_metadata($this->getDokuwikiId(), $this->metadatas);
        return $this;
    }

    /**
     * Code refactoring
     * This method is not in the database page
     * because it would create a cycle
     *
     * The old data was saved in the database
     * but should have been saved on the file system
     *
     * Once
     * @return Alias[]
     * @deprecated 2021-10-31
     */
    private
    function getAndDeleteDeprecatedAlias(): array
    {
        $sqlite = Sqlite::getSqlite();
        if ($sqlite === null) return [];

        $canonicalOrDefault = $this->getCanonicalOrDefault();
        $res = $sqlite->query("select ALIAS from DEPRECATED_PAGES_ALIAS where CANONICAL = ?", $canonicalOrDefault);
        if (!$res) {
            LogUtility::msg("An exception has occurred with the deprecated alias selection query", LogUtility::LVL_MSG_ERROR);
            return [];
        }
        $deprecatedAliasInDb = $sqlite->res2arr($res);
        $sqlite->res_close($res);
        $deprecatedAliases = [];
        array_map(
            function ($row) use ($deprecatedAliases) {
                $alias = $row['ALIAS'];
                $deprecatedAliases[$alias] = Alias::create($this, $alias)
                    ->setType(Alias::REDIRECT);
            },
            $deprecatedAliasInDb
        );

        /**
         * Delete them
         */
        try {
            if (sizeof($deprecatedAliasInDb) > 0) {
                $res = $sqlite->query("delete from DEPRECATED_PAGE_ALIASES where CANONICAL = ?", $canonicalOrDefault);
                if (!$res) {
                    LogUtility::msg("An exception has occurred with the delete deprecated alias statement", LogUtility::LVL_MSG_ERROR);
                }
                $sqlite->res_close($res);
            }
        } catch (\Exception $e) {
            LogUtility::msg("An exception has occurred with the deletion of deprecated aliases. Message: {$e->getMessage()}", LogUtility::LVL_MSG_ERROR);
        }

        /**
         * Return
         */
        return $deprecatedAliases;

    }

    function getPageIdAbbr()
    {

        if ($this->getPageId() === null) return null;
        return substr($this->getPageId(), 0, Page::PAGE_ID_ABBREV_LENGTH);

    }

    public
    function setDatabasePage(DatabasePage $databasePage): Page
    {
        $this->databasePage = $databasePage;
        return $this;
    }

    /**
     * The path (ie id attribute in the url) in a absolute format (ie with root)
     *
     * url path: name for ns + slug (title) + page id
     * or
     * url path: canonical path + page id
     * or
     * url path: page path + page id
     *
     *
     *   - slug
     *   - hierarchical slug
     *   - permanent canonical path (page id)
     *   - canonical path
     *   - permanent page path (page id)
     *   - page path
     */
    public
    function getUrlPath(): string
    {

        /**
         * Type of Url
         */
        if (!$this->exists()) {
            $urlType = Page::CONF_CANONICAL_URL_TYPE_VALUE_PAGE_PATH;
        } else {
            $confCanonicalType = self::CONF_CANONICAL_URL_TYPE;
            $confDefaultValue = self::CONF_CANONICAL_URL_TYPE_DEFAULT;
            $urlType = PluginUtility::getConfValue($confCanonicalType, $confDefaultValue);
            if (!in_array($urlType, self::CONF_CANONICAL_URL_TYPE_VALUES)) {
                $urlType = $confDefaultValue;
                LogUtility::msg("The canonical configuration ($confCanonicalType) value ($urlType) is unknown and was set to the default one", LogUtility::LVL_MSG_ERROR, self::CANONICAL_CANONICAL_URL);
            }

            // Not yet sync with the database
            // No permanent canonical url
            if ($this->getPageIdAbbr() === null) {
                if ($urlType === Page::CONF_CANONICAL_URL_TYPE_VALUE_PERMANENT_CANONICAL_PATH) {
                    $urlType = Page::CONF_CANONICAL_URL_TYPE_VALUE_CANONICAL_PATH;
                } else {
                    $urlType = Page::CONF_CANONICAL_URL_TYPE_VALUE_PAGE_PATH;
                }
            }
        }

        $path = $this->getPath();
        switch ($urlType) {
            case Page::CONF_CANONICAL_URL_TYPE_VALUE_PAGE_PATH:
                $path = $this->getPath();
                break;
            case Page::CONF_CANONICAL_URL_TYPE_VALUE_PERMANENT_PAGE_PATH:
                $path = $this->toPermanentUrlPath($this->getPath());
                break;
            case Page::CONF_CANONICAL_URL_TYPE_VALUE_CANONICAL_PATH:
                $path = $this->getCanonicalOrDefault();
                break;
            case Page::CONF_CANONICAL_URL_TYPE_VALUE_PERMANENT_CANONICAL_PATH:
                $path = $this->toPermanentUrlPath($this->getCanonicalOrDefault());
                break;
            case Page::CONF_CANONICAL_URL_TYPE_VALUE_SLUG:
                $path = $this->toPermanentUrlPath($this->getSlugOrDefault());
                break;
            case Page::CONF_CANONICAL_URL_TYPE_VALUE_HIERARCHICAL_SLUG:
                $path = $this->getSlugOrDefault();
                while (($parent = $this->getParentPage()) != null) {
                    $path = DokuPath::toSlugPath($parent->getPageNameNotEmpty()) . $path;
                }
                $path = $this->toPermanentUrlPath($path);
                break;
            case Page::CONF_CANONICAL_URL_TYPE_VALUE_HOMED_SLUG:
                $path = $this->getSlugOrDefault();
                if (($parent = $this->getParentPage()) != null) {
                    $path = DokuPath::toSlugPath($parent->getPageNameNotEmpty()) . $path;
                }
                $path = $this->toPermanentUrlPath($path);
                break;
            default:
                LogUtility::msg("The url type ($urlType) is unknown and was unexpected", LogUtility::LVL_MSG_ERROR, self::CANONICAL_CANONICAL_URL);

        }
        return $path;

    }

    /**
     * Add a one letter checksum
     * to verify that this is a page id abbr
     * ( and not to hit the index for nothing )
     * @return string
     */
    public
    function getPageIdAbbrUrlEncoded(): ?string
    {
        if ($this->getPageIdAbbr() == null) return null;
        $abbr = $this->getPageIdAbbr();
        return self::encodePageId($abbr);
    }

    /**
     * @return string|null
     *
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }


    public
    function setSlug($slug): Page
    {
        if ($slug === "") {
            $slug = null;
        } else {
            $slug = DokuPath::toSlugPath($slug);
        }
        $this->slug = $slug;
        $this->setMetadata(Page::SLUG_ATTRIBUTE, $slug);
        return $this;
    }

    private
    function toPermanentUrlPath(string $id): string
    {
        return $id . self::PAGE_ID_URL_SEPARATOR . $this->getPageIdAbbrUrlEncoded();
    }

    public
    function getUrlId()
    {
        return DokuPath::toDokuwikiId($this->getUrlPath());
    }

    private
    function getDefaultDescription()
    {
        return $this->descriptionDefault;
    }

    /**
     * @param string $scope {@link Page::SCOPE_CURRENT_VALUE} or a namespace...
     */
    public
    function setScope(string $scope): Page
    {
        $this->scope = $scope;
        $this->setMetadata(Page::SCOPE_KEY, $scope);
        return $this;
    }

    public function setMonitoringQualityIndicator($boolean): Page
    {
        $this->dynamicQualityIndicator = $boolean;
        $this->setMetadata(action_plugin_combo_qualitymessage::DYNAMIC_QUALITY_MONITORING_INDICATOR, $boolean);
        return $this;
    }

    /**
     * @param $aliasPath - the alias used to build this page
     */
    public function setBuildAliasPath($aliasPath)
    {
        $this->buildAliasPath = $aliasPath;
    }

    public function getBuildAlias(): ?Alias
    {
        if ($this->buildAliasPath === null) return null;
        foreach ($this->getAliases() as $alias) {
            if ($alias->getPath() === $this->buildAliasPath) {
                return $alias;
            }
        }
        return null;
    }

    public function isDynamicQualityMonitored(): bool
    {
        if (PluginUtility::getConfValue(action_plugin_combo_qualitymessage::CONF_DISABLE_QUALITY_MONITORING) === 1) {
            return false;
        }
        return $this->getDynamicQualityIndicatorOrDefault();
    }

    public function getDynamicQualityIndicatorOrDefault(): bool
    {
        if ($this->getDynamicQualityIndicator() !== null) {
            return $this->getDynamicQualityIndicator();
        }
        return true;
    }


}
