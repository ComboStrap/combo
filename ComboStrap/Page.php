<?php

namespace ComboStrap;


use action_plugin_combo_metadescription;
use action_plugin_combo_metagoogle;
use action_plugin_combo_qualitymessage;
use DateTime;
use dokuwiki\Extension\Event;
use dokuwiki\Extension\SyntaxPlugin;
use Exception;
use syntax_plugin_combo_disqus;
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
class Page extends ResourceComboAbs
{

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
    const CAN_BE_LOW_QUALITY_PAGE_INDICATOR = 'low_quality_page';
    const CAN_BE_LOW_QUALITY_PAGE_DEFAULT = true;

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


    const REGION_META_PROPERTY = "region";
    const LANG_META_PROPERTY = "lang";
    public const SLUG_ATTRIBUTE = "slug";
    const LAYOUT_PROPERTY = "layout";
    const PAGE_ID_ABBR_ATTRIBUTE = "page_id_abbr";

    public const HOLY_LAYOUT_VALUE = "holy";
    public const LANDING_LAYOUT_VALUE = "landing";
    public const MEDIAN_LAYOUT_VALUE = "median";
    const LOW_QUALITY_INDICATOR_CALCULATED = "low_quality_indicator_calculated";

    const OLD_REGION_PROPERTY = "country";

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
    /**
     * @var Canonical
     */
    private $canonical;
    /**
     * @var PageH1
     */
    private $h1;
    /**
     * @var PageName
     */
    private $pageName;
    /**
     * @var PageType
     */
    private $type;
    /**
     * @var PageTitle $title
     */
    private $title;
    private $author;
    private $authorId;
    private $canBeOfLowQuality;
    private $region;
    private $lang;
    /**
     * @var PageId
     */
    private $pageId;

    /**
     * @var boolean|null
     */
    private $lowQualityIndicatorCalculated;
    private $layout;
    /**
     * @var Aliases
     */
    private $aliases;
    /**
     * @var string a slug path
     */
    private $slug;


    /**
     * The scope of the page
     * (used mostly in side slot, to see if the content
     * is for the current requested namespace or not)
     * @var string|null
     */
    private $scope;
    private $qualityMonitoringIndicator;

    /**
     * @var string the alias used to build this page
     */
    private $buildAliasPath;
    /**
     * @var DateTime|null
     */
    private $publishedDate;
    /**
     * @var DateTime|null
     */
    private $startDate;
    /**
     * @var DateTime|null
     */
    private $endDate;
    /**
     * @var PageImages
     */
    private $pageImages;
    /**
     * @var PageKeywords
     */
    private $keywords;
    /**
     * @var CacheExpirationFrequency
     */
    private $cacheExpirationFrequency;
    /**
     * @var CacheExpirationDate
     */
    private $cacheExpirationDate;
    /**
     *
     * @var LdJson
     */
    private $ldJson;
    /**
     * @var HtmlDocument
     */
    private $htmlDocument;
    /**
     * @var InstructionsDocument
     */
    private $instructionsDocument;

    private $dokuPath;
    /**
     * @var PageDescription $description
     */
    private $description;
    /**
     * @var PageCreationDate
     */
    private $creationTime;

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

        $this->dokuPath = DokuPath::createPagePathFromPath($absolutePath, DokuPath::PAGE_TYPE);

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
        DokuPath::addRootSeparatorIfNotPresent($id);
        return new Page($id);
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
            $pos = strpos(PageId::PAGE_ID_ALPHABET, $letter);
            if ($pos === false) {
                return null;
            }
            $total += $pos;
        }
        $checkSum = $total % strlen(PageId::PAGE_ID_ALPHABET);
        return PageId::PAGE_ID_ALPHABET[$checkSum];
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
                return $scopePath . DokuPath::PATH_SEPARATOR . $this->getPath()->getLastName();
            } else {
                return DokuPath::PATH_SEPARATOR . $this->getPath()->getLastName()();
            }


        } else {

            return $this->dokuPath->toAbsolutePath()->toString();

        }


    }


    static function createPageFromQualifiedPath($qualifiedPath)
    {
        return new Page($qualifiedPath);
    }


    /**
     *
     * @throws ExceptionCombo
     */
    public function setCanonical($canonical): Page
    {
        $this->canonical->setValue($canonical);
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
        return in_array($this->getPath()->getLastName(), $barsName);
    }

    public
    function isStrapSideSlot()
    {

        return $this->isSideSlot && Site::isStrapTemplate();

    }


    /**
     *
     * @return bool
     * Used to delete the part path of a page for default name or canonical value
     */
    public
    function isStartPage(): bool
    {
        $startPageName = Site::getHomePageName();
        return $this->getDokuPathLastName() === $startPageName;
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

        return $this->canonical->getValueOrDefault();

    }


    /**
     * Rebuild the page
     * (refresh from disk, reset object to null)
     * @return $this
     */
    public
    function rebuild(): Page
    {
        $this->buildPropertiesFromFileSystem();
        $this->databasePage = null;
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
        if (key_exists(MetadataDokuWikiStore::CURRENT_METADATA, $metadata)) {
            $current = $metadata[MetadataDokuWikiStore::CURRENT_METADATA];
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


    public
    function deleteCache()
    {

        if ($this->exists()) {

            $this->getInstructionsDocument()->deleteIfExists();
            $this->getHtmlDocument()->deleteIfExists();
            $this->getAnalyticsDocument()->deleteIfExists();

        }
    }

    public function getHtmlDocument(): HtmlDocument
    {
        if ($this->htmlDocument === null) {
            $this->htmlDocument = new HtmlDocument($this);
        }
        return $this->htmlDocument;

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
    function setCanBeOfLowQuality(bool $value): Page
    {
        $this->canBeOfLowQuality = $value;
        return $this->setQualityIndicatorAndDeleteCacheIfNeeded(self::CAN_BE_LOW_QUALITY_PAGE_INDICATOR, $value);
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
        $ft_backlinks = ft_backlinks($this->getDokuwikiId());
        foreach ($ft_backlinks as $backlinkId) {
            $backlinks[$backlinkId] = Page::createPageFromId($backlinkId);
        }
        return $backlinks;
    }


    /**
     * Low page quality
     * @return bool true if this is a low quality page
     */
    function isLowQualityPage(): bool
    {

        $canBeOfLowQuality = $this->getCanBeOfLowQuality();
        if ($canBeOfLowQuality === false) {
            return false;
        }
        if (!Site::isLowQualityProtectionEnable()) {
            return false;
        }
        return $this->getLowQualityIndicatorCalculated();


    }


    public function getCanBeOfLowQuality(): ?bool
    {

        return $this->canBeOfLowQuality;

    }


    public function getH1(): ?string
    {

        return $this->h1->getValue();

    }

    /**
     * Return the Title
     */
    public
    function getTitle(): ?string
    {
        return $this->title->getValue();
    }

    /**
     * If true, the page is quality monitored (a note is shown to the writer)
     * @return null|bool
     */
    public
    function getQualityMonitoringIndicator(): ?bool
    {
        return $this->qualityMonitoringIndicator;
    }

    /**
     * @return string the title, or h1 if empty or the id if empty
     */
    public
    function getTitleOrDefault(): ?string
    {
        return $this->title->getValueOrDefault();
    }

    public
    function getH1OrDefault()
    {

        return $this->h1->getValueOrDefault();


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
    function getTextContent()
    {
        /**
         *
         * use {@link io_readWikiPage(wikiFN($id, $rev), $id, $rev)};
         */
        return rawWiki($this->getPath()->getDokuwikiId());
    }


    public
    function isInIndex()
    {
        $Indexer = idx_get_indexer();
        $pages = $Indexer->getPages();
        $return = array_search($this->getPath()->getDokuwikiId(), $pages, true);
        return $return !== false;
    }


    public
    function upsertContent($content, $summary = "Default"): Page
    {
        saveWikiText($this->getPath()->getDokuwikiId(), $content, $summary);
        return $this;
    }

    public
    function addToIndex()
    {
        idx_addPage($this->getPath()->getDokuwikiId());
    }

    public
    function getTypeNotEmpty()
    {
        return $this->type->getValueOrDefault();
    }


    public
    function getFirstImage()
    {
        return $this->pageImages->getFirstImage();
    }

    /**
     * Return the media stored during parsing
     *
     * They are saved via the function {@link \Doku_Renderer_metadata::_recordMediaUsage()}
     * called by the {@link \Doku_Renderer_metadata::internalmedia()}
     *
     *
     * {@link \Doku_Renderer_metadata::externalmedia()} does not save them
     */
    public function getMediasMetadata(): ?array
    {

        $store = $this->getDefaultMetadataStore();
        if (!($store instanceof MetadataDokuWikiStore)) {
            return null;
        }
        $medias = [];

        $relation = $store->getFromResourceAndName($this, 'relation');
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
     * @return PageImage[]
     */
    public
    function getPageImagesOrDefault(): array
    {

        /**
         * Google accepts several images dimension and ratios
         * for the same image
         * We may get an array then
         */
        $pageImages = $this->getPageImages();
        if (empty($pageImages)) {
            $defaultPageImage = $this->getDefaultPageImageObject();
            if ($defaultPageImage != null) {
                return [$defaultPageImage];
            } else {
                return [];
            }
        }
        return $pageImages;


    }


    /**
     * @return Image
     */
    public
    function getImage(): ?Image
    {

        $images = $this->getPageImagesOrDefault();
        if (sizeof($images) >= 1) {
            return $images[0]->getImage();
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


    /**
     * Get the create date of page
     *
     * @return DateTime
     */
    public
    function getCreatedTime(): ?DateTime
    {
        return $this->creationTime->getValue();
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
            return FileSystems::getModifiedTime($this->getPath());
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
    function renderMetadataAndFlush(): Page
    {

        if (!$this->exists()) {
            if (PluginUtility::isDevOrTest()) {
                LogUtility::msg("You can't render the metadata of a page that does not exist");
            }
            return $this;
        }

        /**
         * @var MetadataDokuWikiStore $metadataStore
         */
        $metadataStore = $this->getDefaultMetadataStore();
        $metadataStore
            ->renderForPage($this)
            ->persist();

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
            return $this->getDefaultRegion();
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
            return $this->getDefaultLang();
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
        if ($this->getPath()->getLastName() == $startPageName) {
            return true;
        } else {
            $namespace = $this->dokuPath->getParent();
            if ($namespace === null) {
                return false;
            }
            if ($namespace->getLastName() === $this->getPath()->getLastName()) {
                /**
                 * page named like the NS inside the NS
                 * ie ns:ns
                 */
                $startPage = Page::createPageFromId($namespace->getDokuwikiId() . DokuPath::PATH_SEPARATOR . $startPageName);
                if (!$startPage->exists()) {
                    return true;
                }
            }
        }
        return false;
    }


    public
    function getPublishedTime(): ?DateTime
    {
        return $this->publishedDate;
    }


    /**
     * @return DateTime
     */
    public
    function getPublishedElseCreationTime(): ?DateTime
    {
        $publishedDate = $this->getPublishedTime();
        if ($publishedDate === null) {
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
     * The unique page Url (also known as Canonical URL) used:
     *   * in the link
     *   * in the canonical ref
     *   * in the site map
     * @param array $urlParameters
     * @param bool $absoluteUrl - by default, dokuwiki allows the canonical to be relative but it's mandatory to be absolute for the HTML meta
     * @return string|null
     */
    public function getCanonicalUrl(array $urlParameters = [], bool $absoluteUrl = false): ?string
    {

        /**
         * Conf
         */
        $urlType = PageUrlType::getOrCreateForPage($this)->getValue();
        if ($urlType === PageUrlType::PAGE_PATH) {
            $absolutePath = Site::getCanonicalConfForRelativeVsAsboluteUrl();
            if ($absolutePath === 1) {
                $absoluteUrl = true;
            }
        }

        /**
         * Dokuwiki Methodology Taken from {@link tpl_metaheaders()}
         */
        if ($absoluteUrl && $this->isRootHomePage()) {
            return DOKU_URL;
        }

        return wl($this->getUrlId(), $urlParameters, $absoluteUrl, '&');


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


    public
    function toXhtml(): string
    {

        return $this->getHtmlDocument()->getOrProcessContent();

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
     * @deprecated for {@link DokuPath::getParent()}
     */
    public
    function getNamespacePath(): string
    {
        return $this->dokuPath->getParent()->toString();
    }


    public function getScope()
    {
        /**
         * The scope may change
         * during a run, we then read the metadata file
         * each time
         */
        if (isset(p_read_metadata($this->getPath()->getDokuwikiId())["persistent"][Page::SCOPE_KEY])) {
            return p_read_metadata($this->getPath()->getDokuwikiId())["persistent"][Page::SCOPE_KEY];
        } else {
            return null;
        }
    }

    /**
     * Return the id of the div HTML
     * element that is added for cache debugging
     */
    public
    function getCacheHtmlId(): string
    {
        return "cache-" . str_replace(":", "-", $this->getPath()->getDokuwikiId());
    }

    public
    function deleteMetadatasAndFlush(): Page
    {
        $meta = [MetadataDokuWikiStore::CURRENT_METADATA => [], MetadataDokuWikiStore::PERSISTENT_METADATA => []];
        p_save_metadata($this->getPath()->getDokuwikiId(), $meta);
        return $this;
    }

    public function getPageName(): ?string
    {

        return $this->pageName->getValue();

    }

    public
    function getPageNameNotEmpty(): string
    {
        return $this->pageName->getValueOrDefault();
    }

    /**
     * @param $property
     */
    public
    function unsetMetadata($property)
    {
        $meta = p_read_metadata($this->getPath()->getDokuwikiId());
        if (isset($meta['persistent'][$property])) {
            unset($meta['persistent'][$property]);
        }
        p_save_metadata($this->getPath()->getDokuwikiId(), $meta);

    }

    /**
     * @return array - return the standard / generated metadata
     * used in templating
     */
    public
    function getMetadataForRendering(): array
    {


        /**
         * The title/h1 should never be null
         * otherwise a template link such as [[$path|$title]] will return a link without an description
         * and therefore will be not visible
         * We render at least the id
         */
        $array[PageH1::H1_PROPERTY] = $this->getH1OrDefault();
        $title = $this->getTitleOrDefault();
        /**
         * Hack: Replace every " by a ' to be able to detect/parse the title/h1 on a pipeline
         * @see {@link \syntax_plugin_combo_pipeline}
         */
        $title = str_replace('"', "'", $title);
        $array[PageTitle::TITLE] = $title;
        $array[PageId::PAGE_ID_ATTRIBUTE] = $this->getPageId();
        $array[Canonical::CANONICAL_PROPERTY] = $this->getCanonicalOrDefault();
        $array[Path::PATH_ATTRIBUTE] = $this->getPath()->toAbsolutePath()->toString();
        $array[PageDescription::DESCRIPTION] = $this->getDescriptionOrElseDokuWiki();
        $array[PageName::NAME_PROPERTY] = $this->getPageNameNotEmpty();
        $array["url"] = $this->getCanonicalUrl();
        $array[PageType::TYPE_META_PROPERTY] = $this->getTypeNotEmpty() !== null ? $this->getTypeNotEmpty() : "";
        $array[Page::SLUG_ATTRIBUTE] = $this->getSlugOrDefault();

        /**
         * When creating a page, the file
         * may not be saved, causing a
         * filemtime(): stat failed for pages/test.txt in lib\plugins\combo\ComboStrap\File.php on line 62
         *
         */
        if ($this->exists()) {
            $array[PageCreationDate::DATE_CREATED] = $this->getCreatedDateAsString();
            $array[AnalyticsDocument::DATE_MODIFIED] = $this->getModifiedDateAsString();
        }

        $array[Publication::DATE_PUBLISHED] = $this->getPublishedTimeAsString();
        $array[AnalyticsDocument::DATE_START] = $this->getStartDateAsString();
        $array[AnalyticsDocument::DATE_END] = $this->getStartDateAsString();
        $array[Page::LAYOUT_PROPERTY] = $this->getMetadata(Page::LAYOUT_PROPERTY);

        return $array;

    }

    public
    function __toString()
    {
        return $this->dokuPath->toUriString();
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
        $dateEndProperty = AnalyticsDocument::DATE_END;
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
        $dateStartProperty = AnalyticsDocument::DATE_START;
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
     * A page id or null if the page id does not exists
     * @return string|null
     */
    public
    function getPageId(): ?string
    {

        return $this->pageId->getValue();

    }


    public
    function getAnalyticsDocument(): AnalyticsDocument
    {
        return new AnalyticsDocument($this);
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
                    ->setCanonical(Metadata::CANONICAL_PROPERTY);
                continue;
            }
            try {
                switch ($lowerKey) {
                    case Canonical::CANONICAL_PROPERTY:
                        $this->setCanonical($value);
                        continue 2;
                    case AnalyticsDocument::DATE_END:
                        $this->setEndDate($value);
                        continue 2;
                    case PageType::TYPE_META_PROPERTY:
                        $this->setPageType($value);
                        continue 2;
                    case AnalyticsDocument::DATE_START:
                        $this->setStartDate($value);
                        continue 2;
                    case Publication::DATE_PUBLISHED:
                        $this->setPublishedDate($value);
                        continue 2;
                    case PageDescription::DESCRIPTION_PROPERTY:
                        $this->setDescription($value);
                        continue 2;
                    case PageName::NAME_PROPERTY:
                        $this->pageName->setFromStoreValue($value);
                        continue 2;
                    case PageTitle::TITLE_META_PROPERTY:
                        $this->title->setFromStoreValue($value);
                        continue 2;
                    case PageH1::H1_PROPERTY:
                        $this->setH1($value);
                        continue 2;
                    case LdJson::JSON_LD_META_PROPERTY:
                        $this->ldJson->setFromStoreValue($value);
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
                    case Aliases::ALIAS_ATTRIBUTE:
                        $this->aliases->setFromStoreValue($value);
                        continue 2;
                    case PageId::PAGE_ID_ATTRIBUTE:
                        $this->pageId
                            ->setValue($value)
                            ->sendToStore();
                        continue 2;
                    case Page::CAN_BE_LOW_QUALITY_PAGE_INDICATOR:
                        $this->setCanBeOfLowQuality(Boolean::toBoolean($value));
                        continue 2;
                    case PageImages::IMAGE_META_PROPERTY:
                        $this->pageImages
                            ->setFromStoreValue($value);
                        continue 2;
                    case action_plugin_combo_qualitymessage::EXECUTE_DYNAMIC_QUALITY_MONITORING_INDICATOR:
                        $this->setQualityMonitoringIndicator(Boolean::toBoolean($value));
                        continue 2;
                    case PageKeywords::KEYWORDS_ATTRIBUTE:
                        $this->setKeywords($value);
                        continue 2;
                    case PAGE::SLUG_ATTRIBUTE:
                        $this->setSlug($value);
                        continue 2;
                    case CacheExpirationFrequency::META_CACHE_EXPIRATION_FREQUENCY_NAME:
                        $this->cacheExpirationFrequency->setFromStoreValue($value);
                        continue 2;
                    default:
                        if (!$persistOnlyKnownAttributes) {
                            $messages[] = Message::createInfoMessage("The metadata ($lowerKey) is unknown but was saved with the value ($value)")
                                ->setCanonical(Metadata::CANONICAL_PROPERTY);
                            $this->setMetadata($key, $value);
                        } else {
                            $messages[] = Message::createErrorMessage("The metadata ($lowerKey) is unknown and was not saved")
                                ->setCanonical(Metadata::CANONICAL_PROPERTY);
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
        $this->persist();


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
     * @throws ExceptionCombo
     */
    public
    function setPageId(?string $pageId): Page
    {

        $this->pageId
            ->setValue($pageId)
            ->sendToStore();
        return $this;

    }

    public
    function getType()
    {
        return $this->type->getValue();
    }

    public function getCanonical(): ?string
    {
        return $this->canonical->getValue();
    }

    /**
     * Create a canonical from the last page path part.
     *
     * @return string|null
     */
    public
    function getDefaultCanonical(): ?string
    {
        return $this->canonical->getDefaultValue();
    }

    public
    function getLayout()
    {
        return $this->getMetadata(Page::LAYOUT_PROPERTY);
    }

    public
    function getDefaultPageName(): string
    {
        return $this->pageName->getDefaultValue();
    }

    public
    function getDefaultTitle(): ?string
    {
        return $this->title->getDefaultValue();
    }

    public
    function getDefaultH1()
    {
        return $this->h1->getValueOrDefault();
    }

    public
    function getDefaultType(): string
    {
        return $this->type->getDefaultValue();
    }

    public
    function getDefaultLayout(): string
    {
        return "holy";
    }


    public
    function getLayoutValues(): array
    {
        return [Page::HOLY_LAYOUT_VALUE, Page::MEDIAN_LAYOUT_VALUE, Page::LANDING_LAYOUT_VALUE];
    }

    public
    function setLowQualityIndicatorCalculation($bool): Page
    {

        $this->lowQualityIndicatorCalculated = $bool;
        /**
         * It's a calculated metadata, we don't need it to be persistent
         */
        $type = MetadataDokuWikiStore::CURRENT_METADATA;
        return $this->setQualityIndicatorAndDeleteCacheIfNeeded(self::LOW_QUALITY_INDICATOR_CALCULATED, $bool, $type);
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
     * @param string $type
     * @return Page
     */
    private
    function setQualityIndicatorAndDeleteCacheIfNeeded(string $lowQualityAttributeName, $value, string $type = MetadataDokuWikiStore::PERSISTENT_METADATA): Page
    {
        $actualValue = $this->getMetadataAsBoolean($lowQualityAttributeName);
        if ($actualValue === null || $value !== $actualValue) {
            $beforeLowQualityPage = $this->isLowQualityPage();
            $this->setMetadata($lowQualityAttributeName, $value, null, $type);
            $afterLowQualityPage = $this->isLowQualityPage();
            if ($beforeLowQualityPage !== $afterLowQualityPage) {
                /**
                 * Delete the html document cache to rewrite the links
                 * if the protection is on
                 */
                if (Site::isLowQualityProtectionEnable()) {
                    foreach ($this->getBacklinks() as $backlink) {
                        $backlink->getHtmlDocument()->deleteIfExists();
                    }
                }
            }
        }
        return $this;
    }


    public
    function getLowQualityIndicatorCalculated()
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
        if ($this->getAnalyticsDocument()->getCachePath()->exists()) {
            $value = $this->getAnalyticsDocument()->getJson()->toArray()[AnalyticsDocument::QUALITY][AnalyticsDocument::LOW];
            if ($value !== null) return $value;
        }

        if (!Site::isLowQualityProtectionEnable()) {
            return false;
        } else {
            return true;
        }

    }

    /**
     * @return PageImage[]
     */
    public
    function getPageImages(): array
    {
        return $this->pageImages->getAll();
    }


    /**
     * @return array|null
     * @deprecated for {@link LdJson}
     */
    public
    function getLdJson(): ?array
    {
        return $this->ldJson->getValue();

    }

    /**
     * @param array|string $jsonLd
     * @return $this
     * @throws ExceptionCombo
     * @deprecated for {@link LdJson}
     */
    public
    function setJsonLd($jsonLd): Page
    {
        $this->ldJson->setValue($jsonLd);
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public
    function setPageType(string $value): Page
    {
        $this->type->setValue($value);
        return $this;
    }

    public
    function getDefaultPageImageObject(): ?PageImage
    {
        if (!PluginUtility::getConfValue(PageImages::CONF_DISABLE_FIRST_IMAGE_AS_PAGE_IMAGE)) {
            $firstImage = $this->getFirstImage();
            if ($firstImage != null) {
                if ($firstImage->getPath()->getScheme() == DokuFs::SCHEME) {
                    return PageImage::create($firstImage, $this);
                }
            }
        }
        return null;
    }

    /**
     * @param $aliasPath
     * @param string $aliasType
     * @return Alias
     * @deprecated for {@link Aliases}
     */
    public
    function addAndGetAlias($aliasPath, string $aliasType = Alias::REDIRECT): Alias
    {

        return $this->aliases->addAndGetAlias($aliasPath, $aliasType);

    }


    /**
     * @return Alias[]
     * @deprecated for {@link Aliases}
     */
    public
    function getAliases(): array
    {
        return $this->aliases->getAll();
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
        return DokuPath::toSlugPath($this->getTitleOrDefault());
    }

    public
    function getParentPage(): ?Page
    {

        $names = $this->getPath()->getNames();
        if (sizeof($names) == 0) {
            return null;
        }
        $slice = 1;
        if ($this->isHomePage()) {
            /**
             * The parent of a home page
             * is in the parent directory
             */
            $slice = 2;
        }
        /**
         * Delete the last or the two last parts
         */
        if (sizeof($names) < $slice) {
            return null;
        }
        /**
         * Get the actual directory for a page
         * or the parent directory for a home page
         */
        $parentNames = array_slice($names, 0, sizeof($names) - $slice);
        /**
         * Create the parent namespace id
         */
        $parentNamespaceId = implode($parentNames, DokuPath::PATH_SEPARATOR);
        return self::getHomePageFromNamespace($parentNamespaceId);

    }

    /**
     * @throws ExceptionCombo
     */
    public
    function setDescription($description): Page
    {

        $this->description->setValue($description);
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public
    function setEndDate($value)
    {
        $this->setDateAttribute(AnalyticsDocument::DATE_END, $this->endDate, $value);
    }

    /**
     * @throws ExceptionCombo
     */
    public
    function setStartDate($value)
    {
        $this->setDateAttribute(AnalyticsDocument::DATE_START, $this->startDate, $value);
    }

    /**
     * @throws ExceptionCombo
     */
    public
    function setPublishedDate($value)
    {
        $this->setDateAttribute(Publication::DATE_PUBLISHED, $this->publishedDate, $value);
    }

    /**
     * Utility to {@link PageName::setValue()}
     * Used mostly to create page in test
     * @throws ExceptionCombo
     */
    public
    function setPageName($value): Page
    {
        $this->pageName
            ->setValue($value);
        return $this;
    }


    /**
     * @throws ExceptionCombo
     */
    public
    function setTitle($value): Page
    {
        $this->title->setValue($value);
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public
    function setH1($value): Page
    {
        $this->h1->setValue($value);
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
                throw new ExceptionCombo("The region value ($value) for the page ($this) does not have two letters (ISO 3166 alpha-2 region code)", "region");
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
                throw new ExceptionCombo("The lang value ($value) for the page ($this) does not have two letters", "lang");
            }
        }
        $this->lang = $value;
        $this->setMetadata(Page::LANG_META_PROPERTY, $value);
        return $this;
    }

    public
    function setLayout($value): Page
    {
        if ($value === "") {
            $value = null;
        }
        $this->layout = $value;
        $this->setMetadata(Page::LAYOUT_PROPERTY, $value);
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

        /**
         * New meta system
         * Even if it does not exist, the metadata object should be instantiated
         * otherwise, there is a null exception
         */
        $this->cacheExpirationDate = CacheExpirationDate::createForPage($this);
        $this->aliases = Aliases::createForPage($this);
        $this->pageImages = PageImages::createForPage($this);
        $this->pageName = PageName::createForPage($this);
        $this->cacheExpirationFrequency = CacheExpirationFrequency::createForPage($this);
        $this->ldJson = LdJson::createForPage($this);
        $this->canonical = Canonical::createForPage($this);
        $this->pageId = PageId::createForPage($this);
        $this->description = PageDescription::createForPage($this);
        $this->h1 = PageH1::createForPage($this);
        $this->type = PageType::createForPage($this);
        $this->creationTime = PageCreationDate::createForPage($this);
        $this->title = PageTitle::createForPage($this);
        $this->keywords = PageKeywords::createForPage($this);

        /**
         * Old system
         *
         * Updating the metadata must happen first
         * All meta function depends on it
         *
         *
         * This is not a {@link Page::renderMetadataAndFlush()}
         *
         * Metadata may be created even if the file does not exist
         * (when the page is rendered for the first time for instance)
         */


        $this->author = $this->getMetadata('creator');
        $this->authorId = $this->getMetadata('user');

        $this->region = $this->getMetadata(self::REGION_META_PROPERTY);
        $this->lang = $this->getMetadata(self::LANG_META_PROPERTY);

        $this->canBeOfLowQuality = Boolean::toBoolean(
            $this->getMetadata(self::CAN_BE_LOW_QUALITY_PAGE_INDICATOR,
                self::CAN_BE_LOW_QUALITY_PAGE_DEFAULT
            )
        );
        $this->lowQualityIndicatorCalculated = Boolean::toBoolean($this->getMetadata(self::LOW_QUALITY_INDICATOR_CALCULATED));

        $this->layout = $this->getMetadata(self::LAYOUT_PROPERTY);


        $this->slug = $this->getMetadata(self::SLUG_ATTRIBUTE);

        $this->scope = $this->getMetadata(self::SCOPE_KEY);
        /**
         * A boolean is never null
         */
        $this->qualityMonitoringIndicator = Boolean::toBoolean(
            $this->getMetadata(
                action_plugin_combo_qualitymessage::EXECUTE_DYNAMIC_QUALITY_MONITORING_INDICATOR,
                action_plugin_combo_qualitymessage::EXECUTE_DYNAMIC_QUALITY_MONITORING_DEFAULT
            ));

        $publishedString = $this->getMetadata(Publication::DATE_PUBLISHED);
        if ($publishedString === null) {
            /**
             * Old metadata key
             */
            $publishedString = $this->getPersistentMetadata(Publication::OLD_META_KEY);
        }
        if ($publishedString !== null) {
            try {
                $this->publishedDate = Iso8601Date::createFromString($publishedString)->getDateTime();
            } catch (ExceptionCombo $e) {
                LogUtility::msg("The published date property of the page ($this) has a value  ($publishedString) that is not valid.", LogUtility::LVL_MSG_ERROR, Iso8601Date::CANONICAL);
            }
        } else {
            $this->publishedDate = null;
        }

        $this->startDate = $this->getMetadataAsDate(AnalyticsDocument::DATE_START);
        $this->endDate = $this->getMetadataAsDate(AnalyticsDocument::DATE_END);


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
     *
     * This is not the URL of the page but of the generated HTML web page with all pages (slots)
     * TODO: Move to {@link HtmlDocument} ?
     */
    public
    function getUrlPath(): string
    {

        /**
         * Type of Url
         */
        $urlType = PageUrlType::getOrCreateForPage($this)->getValue();

        $path = $this->getPath();
        switch ($urlType) {
            case PageUrlType::PAGE_PATH:
                $path = $this->getPath();
                break;
            case PageUrlType::CONF_CANONICAL_URL_TYPE_VALUE_PERMANENT_PAGE_PATH:
                $path = $this->toPermanentUrlPath($this->getPath());
                break;
            case PageUrlType::CONF_CANONICAL_URL_TYPE_VALUE_CANONICAL_PATH:
                $path = $this->getCanonicalOrDefault();
                break;
            case PageUrlType::CONF_CANONICAL_URL_TYPE_VALUE_PERMANENT_CANONICAL_PATH:
                $path = $this->toPermanentUrlPath($this->getCanonicalOrDefault());
                break;
            case PageUrlType::CONF_CANONICAL_URL_TYPE_VALUE_SLUG:
                $path = $this->toPermanentUrlPath($this->getSlugOrDefault());
                break;
            case PageUrlType::CONF_CANONICAL_URL_TYPE_VALUE_HIERARCHICAL_SLUG:
                $path = $this->getSlugOrDefault();
                while (($parent = $this->getParentPage()) != null) {
                    $path = DokuPath::toSlugPath($parent->getPageNameNotEmpty()) . $path;
                }
                $path = $this->toPermanentUrlPath($path);
                break;
            case PageUrlType::CONF_CANONICAL_URL_TYPE_VALUE_HOMED_SLUG:
                $path = $this->getSlugOrDefault();
                if (($parent = $this->getParentPage()) != null) {
                    $path = DokuPath::toSlugPath($parent->getPageNameNotEmpty()) . $path;
                }
                $path = $this->toPermanentUrlPath($path);
                break;
            default:
                LogUtility::msg("The url type ($urlType) is unknown and was unexpected", LogUtility::LVL_MSG_ERROR, PageUrlType::CANONICAL_PROPERTY);

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
    function getDefaultDescription(): ?string
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

    public function setQualityMonitoringIndicator($boolean): Page
    {
        $this->qualityMonitoringIndicator = $boolean;
        $this->setMetadata(action_plugin_combo_qualitymessage::EXECUTE_DYNAMIC_QUALITY_MONITORING_INDICATOR, $boolean, action_plugin_combo_qualitymessage::EXECUTE_DYNAMIC_QUALITY_MONITORING_DEFAULT);
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
        if ($this->getQualityMonitoringIndicator() !== null) {
            return $this->getQualityMonitoringIndicator();
        }
        return $this->getDefaultQualityMonitoring();
    }

    public function getDefaultQualityMonitoring(): bool
    {
        if (PluginUtility::getConfValue(action_plugin_combo_qualitymessage::CONF_DISABLE_QUALITY_MONITORING) === 1) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * TODO ? Put it in the {@link Page::setMetadata()} function
     * @throws ExceptionCombo
     */
    private function setDateAttribute(string $name, &$dateValue, $value, $type = MetadataDokuWikiStore::PERSISTENT_METADATA)
    {
        if ($value === "") {
            $stringValue = null;
            $dateValue = null;
        } else {
            if (!is_string($value)) {
                throw new ExceptionCombo("The $name value ($value) should be in a string format.", Iso8601Date::CANONICAL);
            }
            $stringValue = $value;
            try {
                $dateValue = Iso8601Date::createFromString($value)->getDateTime();
            } catch (ExceptionCombo $e) {
                throw new ExceptionCombo("The $name value ($value) is not a valid date.", Iso8601Date::CANONICAL);
            }
        }
        $this->setMetadata($name, $stringValue, null, $type);
    }

    /**
     * @param array $usages
     * @return Image[]
     */
    public function getImagesOrDefaultForTheFollowingUsages(array $usages): array
    {
        $usages = array_merge($usages, [PageImage::ALL]);
        $images = [];
        foreach ($this->getPageImagesOrDefault() as $pageImage) {
            foreach ($usages as $usage) {
                if (in_array($usage, $pageImage->getUsages())) {
                    $images[] = $pageImage->getImage();
                    continue 2;
                }
            }
        }
        return $images;

    }

    public function getNonDefaultMetadatasValuesInStorageFormat(): array
    {
        $nonDefaultMetadatas = [];

        foreach (Metadata::MUTABLE_METADATA as $metaKey) {
            switch ($metaKey) {
                case Canonical::CANONICAL_PROPERTY:
                    if (!in_array($this->getCanonical(), [$this->getDefaultCanonical(), null])) {
                        $nonDefaultMetadatas[Canonical::CANONICAL_PROPERTY] = $this->getCanonical();
                    }
                    break;
                case
                PageType::TYPE_META_PROPERTY:
                    if (!in_array($this->getType(), [$this->getDefaultType(), null])) {
                        $nonDefaultMetadatas[PageType::TYPE_META_PROPERTY] = $this->getType();
                    }
                    break;
                case PageH1::H1_PROPERTY:
                    if (!in_array($this->getH1(), [$this->getDefaultH1(), null])) {
                        $nonDefaultMetadatas[PageH1::H1_PROPERTY] = $this->getH1();
                    }
                    break;
                case Aliases::ALIAS_ATTRIBUTE:

                    if ($this->aliases->getSize() !== 0) {
                        $nonDefaultMetadatas[Aliases::ALIAS_ATTRIBUTE] = $this->aliases->toStoreValue();
                    }
                    break;
                case PageImages::IMAGE_META_PROPERTY:
                    $images = $this->getPageImages();
                    if (sizeof($images) !== 0) {
                        $nonDefaultMetadatas[PageImages::IMAGE_META_PROPERTY] = $this->pageImages->toStoreValue();
                    }
                    break;
                case Page::REGION_META_PROPERTY:
                    if (!in_array($this->getLocaleRegion(), [$this->getDefaultRegion(), null])) {
                        $nonDefaultMetadatas[Page::REGION_META_PROPERTY] = $this->getLocaleRegion();
                    }
                    break;
                case Page::LANG_META_PROPERTY:
                    if (!in_array($this->getLang(), [$this->getDefaultLang(), null])) {
                        $nonDefaultMetadatas[Page::LANG_META_PROPERTY] = $this->getLang();
                    }
                    break;
                case PageTitle::TITLE:
                    if (!in_array($this->getTitle(), [$this->getDefaultTitle(), null])) {
                        $nonDefaultMetadatas[PageTitle::TITLE] = $this->getTitle();
                    }
                    break;
                case syntax_plugin_combo_disqus::META_DISQUS_IDENTIFIER:
                    /**
                     * @deprecated
                     */
                    $disqus = $this->getMetadata(syntax_plugin_combo_disqus::META_DISQUS_IDENTIFIER);
                    if ($disqus !== null) {
                        $nonDefaultMetadatas[syntax_plugin_combo_disqus::META_DISQUS_IDENTIFIER] = $disqus;
                    }
                    break;
                case Publication::OLD_META_KEY:
                case Publication::DATE_PUBLISHED:
                    if ($this->getPublishedTime() !== null) {
                        $nonDefaultMetadatas[Publication::DATE_PUBLISHED] = $this->getPublishedTimeAsString();
                    }
                    break;
                case PageName::NAME_PROPERTY:
                    if (!in_array($this->getPageName(), [$this->getDefaultPageName(), null])) {
                        $nonDefaultMetadatas[PageName::NAME_PROPERTY] = $this->getPageName();
                    }
                    break;
                case action_plugin_combo_metagoogle::OLD_ORGANIZATION_PROPERTY:
                case LdJson::JSON_LD_META_PROPERTY:
                    if ($this->getLdJson() !== null) {
                        $nonDefaultMetadatas[LdJson::JSON_LD_META_PROPERTY] = $this->getLdJson();
                    }
                    break;
                case Page::LAYOUT_PROPERTY:
                    if (!in_array($this->getLayout(), [$this->getDefaultLayout(), null])) {
                        $nonDefaultMetadatas[Page::LAYOUT_PROPERTY] = $this->getLayout();
                    }
                    break;
                case AnalyticsDocument::DATE_START:
                    if ($this->getStartDate() !== null) {
                        $nonDefaultMetadatas[AnalyticsDocument::DATE_START] = $this->getStartDateAsString();
                    }
                    break;
                case AnalyticsDocument::DATE_END:
                    if ($this->getEndDate() !== null) {
                        $nonDefaultMetadatas[AnalyticsDocument::DATE_END] = $this->getEndDateAsString();
                    }
                    break;
                case action_plugin_combo_metadescription::DESCRIPTION_META_KEY:
                    if (!in_array($this->getDescription(), [$this->getDefaultDescription(), null])) {
                        $nonDefaultMetadatas[action_plugin_combo_metadescription::DESCRIPTION_META_KEY] = $this->getDescription();
                    }
                    break;
                case Page::SLUG_ATTRIBUTE:
                    if (!in_array($this->getSlug(), [$this->getDefaultSlug(), null])) {
                        $nonDefaultMetadatas[Page::SLUG_ATTRIBUTE] = $this->getSlug();
                    }
                    break;
                case action_plugin_combo_qualitymessage::EXECUTE_DYNAMIC_QUALITY_MONITORING_INDICATOR:
                    if (!in_array($this->getQualityMonitoringIndicator(), [$this->getDefaultQualityMonitoring(), null])) {
                        $nonDefaultMetadatas[action_plugin_combo_qualitymessage::EXECUTE_DYNAMIC_QUALITY_MONITORING_INDICATOR] = $this->getQualityMonitoringIndicator();
                    }
                    break;
                case Page::CAN_BE_LOW_QUALITY_PAGE_INDICATOR:
                    if (!in_array($this->getCanBeOfLowQuality(), [true, null])) {
                        $nonDefaultMetadatas[Page::CAN_BE_LOW_QUALITY_PAGE_INDICATOR] = $this->getCanBeOfLowQuality();
                    }
                    break;
                case CacheExpirationFrequency::META_CACHE_EXPIRATION_FREQUENCY_NAME:
                    if ($this->getCacheExpirationFrequency() !== null) {
                        $nonDefaultMetadatas[CacheExpirationFrequency::META_CACHE_EXPIRATION_FREQUENCY_NAME] = $this->getCacheExpirationFrequency();
                    }
                    break;
                case PageKeywords::KEYWORDS_ATTRIBUTE:
                    if ($this->getKeywords() !== null && sizeof($this->getKeywords()) !== 0) {
                        $nonDefaultMetadatas[PageKeywords::KEYWORDS_ATTRIBUTE] = implode(",", $this->getKeywords());
                    }
                    break;
                default:
                    LogUtility::msg("The managed metadata ($metaKey) is not taken into account in the non default metadata computation.", LogUtility::LVL_MSG_ERROR);
            }

        }
        return $nonDefaultMetadatas;
    }

    private function getDefaultRegion()
    {
        return Site::getLanguageRegion();
    }

    private function getDefaultLang()
    {
        return Site::getLang();
    }

    public function getDescriptionOrigin(): string
    {
        return $this->descriptionOrigin;
    }

    /**
     * @param string $key
     * @param string $value
     * @return Page
     * Works only in the render function of the syntax plugin
     */
    public function setRuntimeMetadata(string $key, string $value): Page
    {
        $this->setMetadata($key, $value, null, MetadataDokuWikiStore::CURRENT_METADATA);
        return $this;
    }

    public function getKeywords()
    {
        return $this->keywords;
    }

    public function getKeywordsOrDefault()
    {
        $keyWords = $this->getKeywords();
        if ($keyWords === null) {
            return $this->getDefaultKeywords();
        }
        return $keyWords;
    }

    /**
     * The default of dokuwiki is the parts of the {@link Page::getDokuwikiId() dokuwiki id}
     * @return null|string[]
     */
    public function getDefaultKeywords(): ?array
    {
        return $this->keywords->getDefaultValues();

    }

    /**
     * @throws ExceptionCombo
     */
    public function setKeywords($value): Page
    {

        $this->keywords->setFromStoreValue($value);
    }

    /**
     * @return DateTime|null
     * @deprecated for {@link CacheExpirationDate}
     */
    public function getCacheExpirationDate(): ?DateTime
    {
        return $this->cacheExpirationDate->getValue();
    }

    /**
     * @return DateTime|null
     * @deprecated for {@link CacheExpirationDate}
     */
    public function getDefaultCacheExpirationDate(): ?DateTime
    {
        return $this->cacheExpirationDate->getDefaultValue();
    }

    /**
     * @return string|null
     * @deprecated for {@link CacheExpirationFrequency}
     */
    public function getCacheExpirationFrequency(): ?string
    {
        return $this->cacheExpirationFrequency->getValue();
    }


    /**
     * @param string $metaName
     * @return DateTime|false|mixed|null
     * @deprecated for a metadata that extends {@link MetadataDateTime}
     */
    public function getMetadataAsDate(string $metaName)
    {
        $date = $this->getMetadata($metaName);
        if ($date === null) {
            return null;
        }
        try {
            $dateTime = Iso8601Date::createFromString($date)->getDateTime();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("The meta ($metaName) has a value ($date) that is not a valid date format", Iso8601Date::CANONICAL);
            return null;
        }
        return $dateTime;
    }

    /**
     * @param DateTime $cacheExpirationDate
     * @return $this
     * @deprecated for {@link CacheExpirationDate}
     */
    public function setCacheExpirationDate(DateTime $cacheExpirationDate): Page
    {
        $this->cacheExpirationDate->setValue($cacheExpirationDate);
        return $this;
    }

    /**
     * @return bool - true if the page has changed
     * @deprecated use {@link Page::getInstructionsDocument()} instead
     */
    public function isParseCacheUsable(): bool
    {
        return $this->getInstructionsDocument()->shouldProcess() === false;
    }

    /**
     * @return $this
     * @deprecated use {@link Page::getInstructionsDocument()} instead
     * Parse a page and put the instructions in the cache
     */
    public function parse(): Page
    {

        $this->getInstructionsDocument()
            ->process();

        return $this;

    }

    public function getInstructionsDocument(): InstructionsDocument
    {
        if ($this->instructionsDocument === null) {
            $this->instructionsDocument = new InstructionsDocument($this);
        }
        return $this->instructionsDocument;

    }

    public function delete()
    {

        Index::getOrCreate()->deletePage($this);
        saveWikiText($this->getDokuwikiId(), "", "Delete");

    }

    /**
     * @return string|null -the absolute canonical url
     */
    public function getAbsoluteCanonicalUrl(): ?string
    {
        return $this->getCanonicalUrl([], true);
    }


    public function getDefaultMetadataStore(): MetadataStore
    {
        return MetadataDokuWikiStore::create();
    }

    /**
     * @return Path
     */
    public function getPath(): Path
    {
        return $this->dokuPath;
    }


    /**
     * A shortcut for {@link Page::getPath()::getDokuwikiId()}
     */
    public function getDokuwikiId()
    {
        return $this->getPath()->getDokuwikiId();
    }

    public function getUid(): ?string
    {
        return $this->getPageId();
    }

    public function getPageIdOrGenerate(): string
    {
        return $this->pageId->getPageIdOrGenerate();
    }

    public function getAbsolutePath(): string
    {
        return DokuPath::PATH_SEPARATOR . $this->getDokuwikiId();
    }
}
