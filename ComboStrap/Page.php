<?php

namespace ComboStrap;


use action_plugin_combo_metadescription;
use action_plugin_combo_metagoogle;
use action_plugin_combo_qualitymessage;
use DateTime;
use Exception;
use ModificationDate;
use Slug;
use syntax_plugin_combo_disqus;


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


    const PAGE_ID_ABBR_ATTRIBUTE = "page_id_abbr";

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
     * When the value of a metadata has changed
     */
    const PAGE_METADATA_MUTATION_EVENT = "PAGE_METADATA_MUTATION_EVENT";
    const RESOURCE_TYPE = "page";


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
     * @var ResourceName
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

    /**
     * @var LowQualityPageOverwrite
     */
    private $canBeOfLowQuality;
    /**
     * @var Region
     */
    private $region;
    /**
     * @var Lang
     */
    private $lang;
    /**
     * @var PageId
     */
    private $pageId;

    /**
     * @var LowQualityCalculatedIndicator
     */
    private $lowQualityIndicatorCalculated;

    /**
     * @var PageLayout
     */
    private $layout;
    /**
     * @var Aliases
     */
    private $aliases;
    /**
     * @var Slug a slug path
     */
    private $slug;


    /**
     * @var PageScope
     */
    private $scope;
    /**
     * @var QualityDynamicMonitoringOverwrite
     */
    private $qualityMonitoringIndicator;

    /**
     * @var string the alias used to build this page
     */
    private $buildAliasPath;
    /**
     * @var PagePublicationDate
     */
    private $publishedDate;
    /**
     * @var StartDate
     */
    private $startDate;
    /**
     * @var EndDate
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
     * @var Locale
     */
    private $locale;
    /**
     * @var ModificationDate
     */
    private $modifiedTime;
    /**
     * @var PageUrlPath
     */
    private $pageUrlPath;
    /**
     * @var MetadataStore
     */
    private $store;

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

        $this->dokuPath = DokuPath::createPagePathFromPath($absolutePath);

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
        if ($ID === null) {
            LogUtility::msg("The global wiki ID is null, unable to instantiate a page");
        }
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

    /**
     * @return Page - the requested page
     */
    public static function createPageFromRequestedPage(): Page
    {
        $pageId = PluginUtility::getMainPageDokuwikiId();
        if ($pageId !== null) {
            return Page::createPageFromId($pageId);
        } else {
            LogUtility::msg("We were unable to determine the page from the variables environment", LogUtility::LVL_MSG_ERROR);
            return Page::createPageFromId("unknown-requested-page");
        }
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

            if ($scopePath == PageScope::SCOPE_CURRENT_VALUE) {
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


    static function createPageFromQualifiedPath($qualifiedPath): Page
    {
        return new Page($qualifiedPath);
    }


    /**
     *
     * @throws ExceptionCombo
     */
    public function setCanonical($canonical): Page
    {
        $this->canonical
            ->setValue($canonical)
            ->sendToStore();
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
        return $this->getPath()->getLastName() === $startPageName;
    }

    /**
     * Return a canonical if set
     * otherwise derive it from the id
     * by taking the last two parts
     *
     * @return string
     * @deprecated for {@link Canonical::getValueOrDefault()}
     */
    public function getCanonicalOrDefault(): ?string
    {
        return $this->canonical->getValueFromStoreOrDefault();

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
     * @return Page[]|null the internal links or null
     */
    public
    function getForwardLinks(): ?array
    {
        $store = $this->getStoreOrDefault();
        if (!($store instanceof MetadataDokuWikiStore)) {
            return null;
        }
        $metadata = $store->getFromResourceAndName($this, 'relation');
        if ($metadata === null) {
            /**
             * Happens when no rendering has been made
             */
            return null;
        }
        if (!key_exists('references', $metadata)) {
            return null;
        }

        $pages = [];
        foreach (array_keys($metadata['references']) as $referencePageId) {
            $pages[$referencePageId] = Page::createPageFromId($referencePageId);
        }
        return $pages;

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
     * Set the page quality
     * @param boolean $value true if this is a low quality page rank false otherwise
     * @throws ExceptionCombo
     */
    public
    function setCanBeOfLowQuality(bool $value): Page
    {
        return $this->setQualityIndicatorAndDeleteCacheIfNeeded($this->canBeOfLowQuality, $value);
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

        return $this->canBeOfLowQuality->getValue();

    }


    public function getH1(): ?string
    {

        return $this->h1->getValueFromStore();

    }

    /**
     * Return the Title
     * @deprecated for {@link PageTitle::getValue()}
     */
    public function getTitle(): ?string
    {
        return $this->title->getValueFromStore();
    }

    /**
     * If true, the page is quality monitored (a note is shown to the writer)
     * @return null|bool
     */
    public
    function getQualityMonitoringIndicator(): ?bool
    {
        return $this->qualityMonitoringIndicator->getValueFromStore();
    }

    /**
     * @return string the title, or h1 if empty or the id if empty
     * @deprecated for {@link PageTitle::getValueOrDefault()}
     */
    public
    function getTitleOrDefault(): ?string
    {
        return $this->title->getValueFromStoreOrDefault();
    }

    /**
     * @return mixed
     * @deprecated for {@link PageH1::getValueOrDefault()}
     */
    public
    function getH1OrDefault()
    {

        return $this->h1->getValueFromStoreOrDefault();


    }

    /**
     * @return mixed
     */
    public
    function getDescription(): ?string
    {
        return $this->description->getValueFromStore();
    }


    /**
     * @return string - the description or the dokuwiki generated description
     */
    public
    function getDescriptionOrElseDokuWiki(): ?string
    {
        return $this->description->getValueFromStoreOrDefault();
    }


    /**
     * @return string
     * @deprecated for {@link FileSystems::getContent()} with {@link DokuPath}
     */
    public
    function getTextContent(): string
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

    /**
     * @return mixed
     */
    public function getTypeOrDefault()
    {
        return $this->type->getValueFromStoreOrDefault();
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

        $store = $this->getStoreOrDefault();
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
        $store = $this->getStoreOrDefault();
        if (!($store instanceof MetadataDokuWikiStore)) {
            return null;
        }

        return $store->getFromResourceAndName($this, 'creator');
    }

    /**
     * Get author ID
     *
     * @return string
     */
    public function getAuthorID(): ?string
    {

        $store = $this->getStoreOrDefault();
        if (!($store instanceof MetadataDokuWikiStore)) {
            return null;
        }

        return $store->getFromResourceAndName($this, 'user');

    }


    /**
     * Get the create date of page
     *
     * @return DateTime
     */
    public
    function getCreatedTime(): ?DateTime
    {
        return $this->creationTime->getValueFromStore();
    }


    /**
     *
     * @return DateTime
     */
    public
    function getModifiedTime(): \DateTime
    {
        return $this->modifiedTime->getValueFromStore();
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
        $metadataStore = $this->getStoreOrDefault();
        $metadataStore
            ->renderAndPersistForPage($this)
            ->persist();

        /**
         * Return
         */
        return $this;

    }

    /**
     * @return string|null
     * @deprecated for {@link Region}
     */
    public function getLocaleRegion(): ?string
    {
        return $this->region->getValueFromStore();
    }

    public
    function getRegionOrDefault()
    {

        return $this->region->getValueFromStoreOrDefault();

    }

    public
    function getLang(): ?string
    {
        return $this->lang->getValueFromStore();
    }

    public
    function getLangOrDefault()
    {

        return $this->lang->getValueFromStoreOrDefault();
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
        return $this->publishedDate->getValueFromStore();
    }


    /**
     * @return DateTime
     */
    public
    function getPublishedElseCreationTime(): ?DateTime
    {
        return $this->publishedDate->getValueFromStoreOrDefault();
    }


    public
    function isLatePublication(): bool
    {
        $dateTime = $this->getPublishedElseCreationTime();
        return $dateTime > new DateTime('now');
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
        if ($urlType === PageUrlType::CONF_VALUE_PAGE_PATH) {
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
     * @deprecated for {@link Locale}
     */
    public
    function getLocale($default = null): ?string
    {
        $value = $this->locale->getValueFromStore();
        if ($value === null) {
            return $default;
        }
        return $value;
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
         * Note that the scope may change
         * during a run, we then re-read the metadata
         * each time
         */
        return $this->scope->getValueFromStore();

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

    /**
     * @return $this
     */
    public function deleteMetadatasAndFlush(): Page
    {
        $meta = [MetadataDokuWikiStore::CURRENT_METADATA => [], MetadataDokuWikiStore::PERSISTENT_METADATA => []];
        p_save_metadata($this->getPath()->getDokuwikiId(), $meta);
        return $this;
    }

    public function getPageName(): ?string
    {

        return $this->pageName->getValueFromStore();

    }

    public
    function getPageNameOrDefault(): string
    {
        return $this->pageName->getValueFromStoreOrDefault();
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
     * used in templating with the value or default
     * TODO: should move in the templating class
     */
    public
    function getMetadataForRendering(): array
    {

        $metadataNames = [
            PageH1::H1_PROPERTY,
            PageTitle::TITLE,
            PageId::PAGE_ID_ATTRIBUTE,
            Canonical::CANONICAL_PROPERTY,
            PagePath::PATH_ATTRIBUTE,
            PageDescription::DESCRIPTION,
            ResourceName::NAME_PROPERTY,
            PageType::TYPE_META_PROPERTY,
            Slug::SLUG_ATTRIBUTE,
            PageCreationDate::DATE_CREATED_PROPERTY,
            ModificationDate::DATE_MODIFIED_PROPERTY,
            PagePublicationDate::DATE_PUBLISHED,
            StartDate::DATE_START,
            EndDate::DATE_END,
            PageLayout::LAYOUT_PROPERTY,
            // Dokuwiki id is deprecated for path, no more advertised
            DokuwikiId::DOKUWIKI_ID_ATTRIBUTE
        ];

        foreach ($metadataNames as $metadataName) {
            $metadata = Metadata::getForName($metadataName);
            if ($metadata === null) {
                LogUtility::msg("The metadata ($metadata) should be defined");
                continue;
            }
            /**
             * The Value or Default is returned
             *
             * Because the title/h1 should never be null
             * otherwise a template link such as [[$path|$title]] will return a link without an description
             * and therefore will be not visible
             *
             * ToStoreValue to get the string format of date/boolean in the {@link PipelineUtility}
             * If we want the native value, we need to change the pipeline
             */
            $value = $metadata
                ->setResource($this)
                ->toStoreValueOrDefault();
            if ($metadata->getDataType() === DataType::TEXT_TYPE_VALUE) {

                /**
                 * Hack: Replace every " by a ' to be able to detect/parse the title/h1 on a pipeline
                 * @see {@link \syntax_plugin_combo_pipeline}
                 */
                $value = str_replace('"', "'", $value);
            }
            $array[$metadataName] = $value;
        }
        $array["url"] = $this->getCanonicalUrl();
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
        return $this->endDate->getValueFromStore();
    }

    public
    function getStartDateAsString(): ?string
    {
        return $this->getStartDate() !== null ? $this->getStartDate()->format(Iso8601Date::getFormat()) : null;
    }

    public
    function getStartDate(): ?DateTime
    {
        return $this->startDate->getValueFromStore();
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
            if (in_array($lowerKey, Metadata::NOT_MODIFIABLE_METAS)) {
                $messages[] = Message::createWarningMessage("The metadata ($lowerKey) is a protected metadata and cannot be modified")
                    ->setCanonical(Metadata::CANONICAL_PROPERTY);
                continue;
            }
            try {
                switch ($lowerKey) {
                    case Canonical::CANONICAL_PROPERTY:
                        $this->setCanonical($value);
                        continue 2;
                    case EndDate::DATE_END:
                        $this->setEndDate($value);
                        continue 2;
                    case PageType::TYPE_META_PROPERTY:
                        $this->setPageType($value);
                        continue 2;
                    case StartDate::DATE_START:
                        $this->setStartDate($value);
                        continue 2;
                    case PagePublicationDate::DATE_PUBLISHED:
                        $this->setPublishedDate($value);
                        continue 2;
                    case PageDescription::DESCRIPTION_PROPERTY:
                        $this->setDescription($value);
                        continue 2;
                    case ResourceName::NAME_PROPERTY:
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
                    case Region::REGION_META_PROPERTY:
                        $this->setRegion($value);
                        continue 2;
                    case Lang::LANG_ATTRIBUTES:
                        $this->setLang($value);
                        continue 2;
                    case PageLayout::LAYOUT_PROPERTY:
                        $this->setLayout($value);
                        continue 2;
                    case Aliases::ALIAS_ATTRIBUTE:
                        $this->aliases->setFromStoreValue($value);
                        continue 2;
                    case PageId::PAGE_ID_ATTRIBUTE:
                        $this->pageId->setValue($value);
                        continue 2;
                    case LowQualityPageOverwrite::CAN_BE_LOW_QUALITY_PAGE_INDICATOR:
                        $this->setCanBeOfLowQuality(Boolean::toBoolean($value));
                        continue 2;
                    case PageImages::IMAGE_META_PROPERTY:
                        $this->pageImages
                            ->setFromStoreValue($value);
                        continue 2;
                    case QualityDynamicMonitoringOverwrite::EXECUTE_DYNAMIC_QUALITY_MONITORING_INDICATOR:
                        $this->setQualityMonitoringIndicator(Boolean::toBoolean($value));
                        continue 2;
                    case PageKeywords::KEYWORDS_ATTRIBUTE:
                        $this->setKeywords($value);
                        continue 2;
                    case Slug::SLUG_ATTRIBUTE:
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
        return $this->getPath()->toString() === ":$startPageName";

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
    function getPageType(): ?string
    {
        return $this->type->getValueFromStore();
    }

    public function getCanonical(): ?string
    {
        return $this->canonical->getValueFromStore();
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
        return $this->layout->getValueFromStore();
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
        return $this->layout->getDefaultValue();
    }


    /**
     * @throws ExceptionCombo
     */
    public function setLowQualityIndicatorCalculation($bool): Page
    {

        return $this->setQualityIndicatorAndDeleteCacheIfNeeded($this->lowQualityIndicatorCalculated, $bool);
    }


    /**
     * Change the quality indicator
     * and if the quality level has become low
     * and that the protection is on, delete the cache
     * @param MetadataBoolean $lowQualityAttributeName
     * @param bool $value
     * @return Page
     * @throws ExceptionCombo
     */
    private
    function setQualityIndicatorAndDeleteCacheIfNeeded(MetadataBoolean $lowQualityAttributeName, bool $value): Page
    {
        $actualValue = $lowQualityAttributeName->getValue();
        if ($actualValue === null || $value !== $actualValue) {
            $beforeLowQualityPage = $this->isLowQualityPage();
            $lowQualityAttributeName
                ->setValue($value)
                ->sendToStore();
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

        return $this->lowQualityIndicatorCalculated->getValueOrDefault();

    }

    /**
     * @return PageImage[]
     */
    public
    function getPageImages(): array
    {
        return $this->pageImages->getValues();
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
        $this->ldJson
            ->setValue($jsonLd)
            ->sendToStore();
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public
    function setPageType(string $value): Page
    {
        $this->type
            ->setValue($value)
            ->sendToStore();
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
        return $this->slug->getDefaultValue();
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

        $this->description
            ->setValue($description)
            ->sendToStore();
        return $this;
    }

    /**
     * @throws ExceptionCombo
     * @deprecated uses {@link EndDate} instead
     */
    public
    function setEndDate($value): Page
    {
        $this->endDate
            ->setFromStoreValue($value)
            ->sendToStore();
        return $this;
    }

    /**
     * @throws ExceptionCombo
     * @deprecated uses {@link StartDate} instead
     */
    public
    function setStartDate($value): Page
    {
        $this->startDate
            ->setFromStoreValue($value)
            ->sendToStore();
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public function setPublishedDate($value): Page
    {
        $this->publishedDate
            ->setFromStoreValue($value)
            ->sendToStore();
        return $this;
    }

    /**
     * Utility to {@link ResourceName::setValue()}
     * Used mostly to create page in test
     * @throws ExceptionCombo
     */
    public
    function setPageName($value): Page
    {
        $this->pageName
            ->setValue($value)
            ->sendToStore();
        return $this;
    }


    /**
     * @throws ExceptionCombo
     */
    public
    function setTitle($value): Page
    {
        $this->title
            ->setValue($value)
            ->sendToStore();
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public
    function setH1($value): Page
    {
        $this->h1
            ->setValue($value)
            ->sendToStore();
        return $this;
    }

    /**
     * @throws Exception
     */
    public
    function setRegion($value): Page
    {
        $this->region
            ->setFromStoreValue($value)
            ->sendToStore();
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public
    function setLang($value): Page
    {

        $this->lang
            ->setFromStoreValue($value)
            ->sendToStore();
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public
    function setLayout($value): Page
    {
        $this->layout
            ->setValue($value)
            ->sendToStore();
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
        $this->pageName = ResourceName::createForResource($this);
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
        $this->publishedDate = PagePublicationDate::createFromPage($this);
        $this->startDate = StartDate::createFromPage($this);
        $this->endDate = EndDate::createFromPage($this);
        $this->locale = Locale::createForPage($this);
        $this->lang = Lang::createForPage($this);
        $this->region = Region::createForPage($this);
        $this->slug = Slug::createForPage($this);
        $this->canBeOfLowQuality = LowQualityPageOverwrite::createForPage($this);
        $this->lowQualityIndicatorCalculated = LowQualityCalculatedIndicator::createFromPage($this);
        $this->qualityMonitoringIndicator = QualityDynamicMonitoringOverwrite::createFromPage($this);
        $this->modifiedTime = ModificationDate::createForPage($this);
        $this->pageUrlPath = PageUrlPath::createForPage($this);
        $this->layout = PageLayout::createFromPage($this);
        $this->scope = PageScope::createFromPage($this);

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

        return $this->pageUrlPath->getValueOrDefault();

    }


    /**
     * @return string|null
     *
     */
    public function getSlug(): ?string
    {
        return $this->slug->getValue();
    }


    /**
     * @throws ExceptionCombo
     */
    public
    function setSlug($slug): Page
    {
        $this->slug
            ->setFromStoreValue($slug)
            ->sendToStore();
        return $this;
    }


    public
    function getUrlId()
    {
        return DokuPath::toDokuwikiId($this->getUrlPath());
    }

    private
    function getDefaultDescription(): ?string
    {
        return $this->description->getDefaultValue();
    }

    /**
     * @param string $scope {@link PageScope::SCOPE_CURRENT_VALUE} or a namespace...
     * @throws ExceptionCombo
     */
    public
    function setScope(string $scope): Page
    {
        $this->scope
            ->setFromStoreValue($scope)
            ->sendToStore();
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public function setQualityMonitoringIndicator($boolean): Page
    {
        $this->qualityMonitoringIndicator
            ->setFromStoreValue($boolean)
            ->sendToStore();
        return $this;
    }

    /**
     *
     * @param $aliasPath - third information - the alias used to build this page
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

    public function setStore(MetadataStore $store): Page
    {
        $this->store = $store;
        return $this;
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
                    if (!in_array($this->getPageType(), [$this->getDefaultType(), null])) {
                        $nonDefaultMetadatas[PageType::TYPE_META_PROPERTY] = $this->getPageType();
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
                case Region::REGION_META_PROPERTY:
                    if (!in_array($this->getLocaleRegion(), [$this->getDefaultRegion(), null])) {
                        $nonDefaultMetadatas[Region::REGION_META_PROPERTY] = $this->getLocaleRegion();
                    }
                    break;
                case Lang::LANG_ATTRIBUTES:
                    if (!in_array($this->getLang(), [$this->getDefaultLang(), null])) {
                        $nonDefaultMetadatas[Lang::LANG_ATTRIBUTES] = $this->getLang();
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
                case PagePublicationDate::OLD_META_KEY:
                case PagePublicationDate::DATE_PUBLISHED:
                    if ($this->getPublishedTime() !== null) {
                        $nonDefaultMetadatas[PagePublicationDate::DATE_PUBLISHED] = $this->getPublishedTimeAsString();
                    }
                    break;
                case ResourceName::NAME_PROPERTY:
                    if (!in_array($this->getPageName(), [$this->getDefaultPageName(), null])) {
                        $nonDefaultMetadatas[ResourceName::NAME_PROPERTY] = $this->getPageName();
                    }
                    break;
                case LdJson::OLD_ORGANIZATION_PROPERTY:
                case LdJson::JSON_LD_META_PROPERTY:
                    if ($this->getLdJson() !== null) {
                        $nonDefaultMetadatas[LdJson::JSON_LD_META_PROPERTY] = $this->getLdJson();
                    }
                    break;
                case PageLayout::LAYOUT_PROPERTY:
                    if (!in_array($this->getLayout(), [$this->getDefaultLayout(), null])) {
                        $nonDefaultMetadatas[PageLayout::LAYOUT_PROPERTY] = $this->getLayout();
                    }
                    break;
                case StartDate::DATE_START:
                    if ($this->getStartDate() !== null) {
                        $nonDefaultMetadatas[StartDate::DATE_START] = $this->getStartDateAsString();
                    }
                    break;
                case EndDate::DATE_END:
                    if ($this->getEndDate() !== null) {
                        $nonDefaultMetadatas[EndDate::DATE_END] = $this->getEndDateAsString();
                    }
                    break;
                case action_plugin_combo_metadescription::DESCRIPTION_META_KEY:
                    if (!in_array($this->getDescription(), [$this->getDefaultDescription(), null])) {
                        $nonDefaultMetadatas[action_plugin_combo_metadescription::DESCRIPTION_META_KEY] = $this->getDescription();
                    }
                    break;
                case Slug::SLUG_ATTRIBUTE:
                    if (!in_array($this->getSlug(), [$this->getDefaultSlug(), null])) {
                        $nonDefaultMetadatas[Slug::SLUG_ATTRIBUTE] = $this->getSlug();
                    }
                    break;
                case QualityDynamicMonitoringOverwrite::EXECUTE_DYNAMIC_QUALITY_MONITORING_INDICATOR:
                    if (!in_array($this->getQualityMonitoringIndicator(), [$this->getDefaultQualityMonitoring(), null])) {
                        $nonDefaultMetadatas[QualityDynamicMonitoringOverwrite::EXECUTE_DYNAMIC_QUALITY_MONITORING_INDICATOR] = $this->getQualityMonitoringIndicator();
                    }
                    break;
                case LowQualityPageOverwrite::CAN_BE_LOW_QUALITY_PAGE_INDICATOR:
                    if (!in_array($this->getCanBeOfLowQuality(), [true, null])) {
                        $nonDefaultMetadatas[LowQualityPageOverwrite::CAN_BE_LOW_QUALITY_PAGE_INDICATOR] = $this->getCanBeOfLowQuality();
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


    public function getKeywords(): PageKeywords
    {
        return $this->keywords;
    }

    public function getKeywordsOrDefault(): array
    {
        return $this->keywords->getValueOrDefaults();
    }


    /**
     * @throws ExceptionCombo
     */
    public function setKeywords($value): Page
    {
        $this->keywords
            ->setFromStoreValue($value)
            ->sendToStore();
        return $this;
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
     * @param DateTime $cacheExpirationDate
     * @return $this
     * @throws ExceptionCombo
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


    public function getStoreOrDefault(): MetadataStore
    {
        if ($this->store === null) {
            return MetadataDokuWikiStore::getOrCreate();
        }
        return $this->store;
    }

    /**
     * @return DokuPath
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

    public function getUid(): MetadataScalar
    {
        return $this->pageId;
    }

    public function getPageIdOrGenerate(): string
    {
        return $this->pageId->getPageIdOrGenerate();
    }

    public function getAbsolutePath(): string
    {
        return DokuPath::PATH_SEPARATOR . $this->getDokuwikiId();
    }

    function getResourceType(): string
    {
        return self::RESOURCE_TYPE;
    }

}
