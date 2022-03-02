<?php

namespace ComboStrap;


use action_plugin_combo_qualitymessage;
use DateTime;
use Exception;
use ModificationDate;
use Slug;


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


    const TYPE = "page";


    /**
     * The id requested (ie the main page)
     * The page may be a slot
     * @var string
     */
    private $requestedId;
    /**
     * @var DatabasePageRow
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
     * @var MetadataStore|string
     */
    private $readStore;

    /**
     * Page constructor.
     * @param $absolutePath - the qualified path (may be not relative)
     *
     */
    public function __construct($absolutePath)
    {

        $this->dokuPath = DokuPath::createPagePathFromPath($absolutePath);

        if ($this->isSecondarySlot()) {

            /**
             * Used when we want to remove the cache of slots for a requested page
             * (ie {@link Cache::removeSideSlotCache()})
             *
             * The $absolutePath is the logical path and may not exists
             *
             * Find the first physical file
             * Don't use ACL otherwise the ACL protection event 'AUTH_ACL_CHECK' will kick in
             * and we got then a recursive problem
             * with the {@link \action_plugin_combo_pageprotection}
             */
            $useAcl = false;
            $id = page_findnearest($this->dokuPath->getLastNameWithoutExtension(), $useAcl);
            if ($id !== false && $id !== $this->dokuPath->getDokuwikiId()) {
                $absolutePath = DokuPath::PATH_SEPARATOR . $id;
                $this->dokuPath = DokuPath::createPagePathFromPath($absolutePath);
            }

        }

        global $ID;
        $this->requestedId = $ID;

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
        $pageId = PluginUtility::getRequestedWikiId();
        if ($pageId === null) {
            $pageId = RenderUtility::DEFAULT_SLOT_ID_FOR_TEST;
            if(!PluginUtility::isTest()) {
                // should never happen, we don't throw an exception
                LogUtility::msg("We were unable to determine the requested page from the variables environment, default non-existing page id used");
            }
        }
        return Page::createPageFromId($pageId);
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


    static function createPageFromQualifiedPath($qualifiedPath): Page
    {
        return new Page($qualifiedPath);
    }


    /**
     *
     * @throws ExceptionCombo
     */
    public
    function setCanonical($canonical): Page
    {
        $this->canonical
            ->setValue($canonical)
            ->sendToWriteStore();
        return $this;
    }


    /**
     * @return bool true if this is not the main slot.
     */
    public function isSecondarySlot(): bool
    {
        $slotNames = Site::getSecondarySlotNames();
        $name = $this->getPath()->getLastNameWithoutExtension();
        if ($name === null) {
            // root case
            return false;
        }
        return in_array($name, $slotNames, true);
    }

    /**
     * @return bool true if this is the main
     */
    public function isMainHeaderFooterSlot(): bool
    {

        try {
            $slotNames = [Site::getMainHeaderSlotName(), Site::getMainFooterSlotName()];
        } catch (ExceptionCombo $e) {
            return false;
        }
        $name = $this->getPath()->getLastNameWithoutExtension();
        if ($name === null) {
            // root case
            return false;
        }
        return in_array($name, $slotNames, true);
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
    public
    function getCanonicalOrDefault(): ?string
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
        $this->readStore = null;
        $this->buildPropertiesFromFileSystem();
        $this->databasePage = null;
        $this->htmlDocument = null;
        $this->instructionsDocument = null;
        return $this;
    }

    /**
     *
     * @return Page[]|null the internal links or null
     */
    public
    function getLinkReferences(): ?array
    {
        $store = $this->getReadStoreOrDefault();
        if (!($store instanceof MetadataDokuWikiStore)) {
            return null;
        }
        $metadata = $store->getCurrentFromName('relation');
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
    function getHtmlDocument(): HtmlDocument
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


    public
    function getCanBeOfLowQuality(): ?bool
    {

        return $this->canBeOfLowQuality->getValue();

    }


    public
    function getH1(): ?string
    {

        return $this->h1->getValueFromStore();

    }

    /**
     * Return the Title
     * @deprecated for {@link PageTitle::getValue()}
     */
    public
    function getTitle(): ?string
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
     * Shortcut to {@link PageTitle::getValueOrDefault()}
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
     * A wrapper around {@link FileSystems::getContent()} with {@link DokuPath}
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
        /**
         * Add to index check the metadata cache
         * Because we log the cache at the requested page level, we need to
         * set the global ID
         */
        global $ID;
        $keep = $ID;
        try {
            $ID = $this->getPath()->getDokuwikiId();
            idx_addPage($ID);
        } finally {
            $ID = $keep;
        }
        return $this;

    }

    /**
     * @return mixed
     */
    public
    function getTypeOrDefault()
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
    public
    function getMediasMetadata(): ?array
    {

        $store = $this->getReadStoreOrDefault();
        if (!($store instanceof MetadataDokuWikiStore)) {
            return null;
        }
        $medias = [];

        $relation = $store->getCurrentFromName('relation');
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

        return $this->pageImages->getValueAsPageImagesOrDefault();

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
    public
    function getAuthor(): ?string
    {
        $store = $this->getReadStoreOrDefault();
        if (!($store instanceof MetadataDokuWikiStore)) {
            return null;
        }

        return $store->getFromPersistentName('creator');
    }

    /**
     * Get author ID
     *
     * @return string
     */
    public
    function getAuthorID(): ?string
    {

        $store = $this->getReadStoreOrDefault();
        if (!($store instanceof MetadataDokuWikiStore)) {
            return null;
        }

        return $store->getFromPersistentName('user');

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
     * @return null|DateTime
     */
    public
    function getModifiedTime(): ?\DateTime
    {
        return $this->modifiedTime->getValueFromStore();
    }

    public
    function getModifiedTimeOrDefault(): ?\DateTime
    {
        return $this->modifiedTime->getValueFromStoreOrDefault();
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
        $metadataStore = $this->getReadStoreOrDefault();
        $metadataStore->renderAndPersist();

        /**
         * Return
         */
        return $this;

    }

    /**
     * @return string|null
     * @deprecated for {@link Region}
     */
    public
    function getLocaleRegion(): ?string
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
     * @param bool $absoluteUrlMandatory - by default, dokuwiki allows the canonical to be relative but it's mandatory to be absolute for the HTML meta
     * @param string $separator - TODO: delete. HTML encoded or not ampersand (the default should always be good because the encoding is done just before printing (ie {@link TagAttributes::encodeToHtmlValue()})
     * @return string|null
     */
    public
    function getCanonicalUrl(array $urlParameters = [], bool $absoluteUrlMandatory = false, string $separator = DokuwikiUrl::AMPERSAND_CHARACTER): ?string
    {

        /**
         * Conf
         */
        $urlType = PageUrlType::getOrCreateForPage($this)->getValue();
        if ($urlType === PageUrlType::CONF_VALUE_PAGE_PATH && $absoluteUrlMandatory == false) {
            $absoluteUrlMandatory = Site::shouldUrlBeAbsolute();
        }

        /**
         * Dokuwiki Methodology Taken from {@link tpl_metaheaders()}
         */
        if ($absoluteUrlMandatory && $this->isRootHomePage()) {
            return DOKU_URL;
        }

        return wl($this->getUrlId(), $urlParameters, $absoluteUrlMandatory, $separator);


    }

    public function getUrl($type = null): ?string
    {
        if ($type === null) {
            return $this->getCanonicalUrl();
        }
        $pageUrlId = DokuPath::toDokuwikiId(PageUrlPath::createForPage($this)
            ->getUrlPathFromType($type));
        return wl($pageUrlId);
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


    /**
     *
     */
    public
    function toXhtml(): ?string
    {

        return $this->getHtmlDocument()->getOrProcessContent();

    }


    public
    function getHtmlAnchorLink($logicalTag = null): string
    {
        $id = $this->getPath()->getDokuwikiId();
        try {
            return MarkupRef::createFromPageId($id)
                    ->toAttributes($logicalTag)
                    ->toHtmlEnterTag("a")
                . $this->getNameOrDefault()
                . "</a>";
        } catch (ExceptionCombo $e) {
            LogUtility::msg("The markup ref returns an error for the creation of the page anchor html link ($this). Error: {$e->getMessage()}");
            return "<a href=\"{$this->getCanonicalUrl()}\" data-wiki-id=\"$id\">{$this->getNameOrDefault()}</a>";
        }
    }


    /**
     * Without the `:` at the end
     * @return string
     * @deprecated / shortcut for {@link DokuPath::getParent()}
     * Because a page has always a parent, the string is never null.
     */
    public
    function getNamespacePath(): string
    {

        return $this->dokuPath->getParent()->toString();

    }


    /**
     * @return $this
     * @deprecated use {@link MetadataDokuWikiStore::deleteAndFlush()}
     */
    public
    function deleteMetadatasAndFlush(): Page
    {
        MetadataDokuWikiStore::getOrCreateFromResource($this)
            ->deleteAndFlush();
        return $this;
    }

    public
    function getName(): ?string
    {

        return $this->pageName->getValueFromStore();

    }

    public
    function getNameOrDefault(): string
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
            PageH1::PROPERTY_NAME,
            PageTitle::TITLE,
            PageId::PROPERTY_NAME,
            Canonical::PROPERTY_NAME,
            PagePath::PROPERTY_NAME,
            PageDescription::PROPERTY_NAME,
            ResourceName::PROPERTY_NAME,
            PageType::PROPERTY_NAME,
            Slug::PROPERTY_NAME,
            PageCreationDate::PROPERTY_NAME,
            ModificationDate::PROPERTY_NAME,
            PagePublicationDate::PROPERTY_NAME,
            StartDate::PROPERTY_NAME,
            EndDate::PROPERTY_NAME,
            PageLayout::PROPERTY_NAME,
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
                ->setWriteStore(TemplateStore::class)
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
    function getDatabasePage(): DatabasePageRow
    {
        if ($this->databasePage == null) {
            $this->databasePage = DatabasePageRow::createFromPageObject($this);
        }
        return $this->databasePage;
    }

    public
    function canBeUpdatedByCurrentUser(): bool
    {
        return Identity::isWriter($this->getDokuwikiId());
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
            ->sendToWriteStore();

        return $this;

    }

    public
    function getPageType(): ?string
    {
        return $this->type->getValueFromStore();
    }

    public
    function getCanonical(): ?string
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
    public
    function setLowQualityIndicatorCalculation($bool): Page
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
            $lowQualityAttributeName
                ->setValue($value)
                ->persist();
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
    function getPageImages(): ?array
    {
        return $this->pageImages->getValueAsPageImages();
    }


    /**
     * @return array|null
     * @deprecated for {@link LdJson}
     */
    public
    function getLdJson(): ?string
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
            ->sendToWriteStore();
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
            ->sendToWriteStore();
        return $this;
    }


    /**
     * @param $aliasPath
     * @param string $aliasType
     * @return Alias
     * @deprecated for {@link Aliases}
     */
    public
    function addAndGetAlias($aliasPath, string $aliasType = AliasType::REDIRECT): Alias
    {

        return $this->aliases->addAndGetAlias($aliasPath, $aliasType);

    }


    /**
     * @return Alias[]
     */
    public
    function getAliases(): ?array
    {
        return $this->aliases->getValueAsAlias();
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
    public
    function getDefaultSlug(): ?string
    {
        return $this->slug->getDefaultValue();
    }

    /**
     * The parent page is the parent in the page tree directory
     *
     * If the page is at the root, the parent page is the root home
     * Only the root home does not have any parent page and return null.
     *
     * @return Page|null
     */
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
        $parentNamespaceId = implode(DokuPath::PATH_SEPARATOR, $parentNames);
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
            ->sendToWriteStore();
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
            ->sendToWriteStore();
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
            ->sendToWriteStore();
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public
    function setPublishedDate($value): Page
    {
        $this->publishedDate
            ->setFromStoreValue($value)
            ->sendToWriteStore();
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
            ->sendToWriteStore();
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
            ->sendToWriteStore();
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
            ->sendToWriteStore();
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
            ->sendToWriteStore();
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
            ->sendToWriteStore();
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
            ->sendToWriteStore();
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

    }


    function getPageIdAbbr()
    {

        if ($this->getPageId() === null) return null;
        return substr($this->getPageId(), 0, PageId::PAGE_ID_ABBREV_LENGTH);

    }

    public
    function setDatabasePage(DatabasePageRow $databasePage): Page
    {
        $this->databasePage = $databasePage;
        return $this;
    }

    /**
     *
     * TODO: Move to {@link HtmlDocument} ?
     */
    public function getUrlPath(): string
    {

        return $this->pageUrlPath->getValueOrDefault();

    }


    /**
     * @return string|null
     *
     */
    public
    function getSlug(): ?string
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
            ->sendToWriteStore();
        return $this;
    }


    public
    function getUrlId()
    {
        return DokuPath::toDokuwikiId($this->getUrlPath());
    }


    /**
     * @throws ExceptionCombo
     */
    public
    function setQualityMonitoringIndicator($boolean): Page
    {
        $this->qualityMonitoringIndicator
            ->setFromStoreValue($boolean)
            ->sendToWriteStore();
        return $this;
    }

    /**
     *
     * @param $aliasPath - third information - the alias used to build this page
     */
    public
    function setBuildAliasPath($aliasPath)
    {
        $this->buildAliasPath = $aliasPath;
    }

    public
    function getBuildAlias(): ?Alias
    {
        if ($this->buildAliasPath === null) return null;
        foreach ($this->getAliases() as $alias) {
            if ($alias->getPath() === $this->buildAliasPath) {
                return $alias;
            }
        }
        return null;
    }

    public
    function isDynamicQualityMonitored(): bool
    {
        if ($this->getQualityMonitoringIndicator() !== null) {
            return $this->getQualityMonitoringIndicator();
        }
        return $this->getDefaultQualityMonitoring();
    }

    public
    function getDefaultQualityMonitoring(): bool
    {
        if (PluginUtility::getConfValue(action_plugin_combo_qualitymessage::CONF_DISABLE_QUALITY_MONITORING) === 1) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param MetadataStore|string $store
     * @return $this
     */
    public
    function setReadStore($store): Page
    {
        $this->readStore = $store;
        return $this;
    }


    /**
     * @param array $usages
     * @return Image[]
     */
    public
    function getImagesOrDefaultForTheFollowingUsages(array $usages): array
    {
        $usages = array_merge($usages, [PageImageUsage::ALL]);
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


    public
    function getKeywords(): ?array
    {
        return $this->keywords->getValues();
    }

    public
    function getKeywordsOrDefault(): array
    {
        return $this->keywords->getValueOrDefaults();
    }


    /**
     * @throws ExceptionCombo
     */
    public
    function setKeywords($value): Page
    {
        $this->keywords
            ->setFromStoreValue($value)
            ->sendToWriteStore();
        return $this;
    }

    /**
     * @return DateTime|null
     * @deprecated for {@link CacheExpirationDate}
     */
    public
    function getCacheExpirationDate(): ?DateTime
    {
        return $this->cacheExpirationDate->getValue();
    }

    /**
     * @return DateTime|null
     * @deprecated for {@link CacheExpirationDate}
     */
    public
    function getDefaultCacheExpirationDate(): ?DateTime
    {
        return $this->cacheExpirationDate->getDefaultValue();
    }

    /**
     * @return string|null
     * @deprecated for {@link CacheExpirationFrequency}
     */
    public
    function getCacheExpirationFrequency(): ?string
    {
        return $this->cacheExpirationFrequency->getValue();
    }


    /**
     * @param DateTime $cacheExpirationDate
     * @return $this
     * @deprecated for {@link CacheExpirationDate}
     */
    public
    function setCacheExpirationDate(DateTime $cacheExpirationDate): Page
    {
        $this->cacheExpirationDate->setValue($cacheExpirationDate);
        return $this;
    }

    /**
     * @return bool - true if the page has changed
     * @deprecated use {@link Page::getInstructionsDocument()} instead
     */
    public
    function isParseCacheUsable(): bool
    {
        return $this->getInstructionsDocument()->shouldProcess() === false;
    }

    /**
     * @return $this
     * @deprecated use {@link Page::getInstructionsDocument()} instead
     * Parse a page and put the instructions in the cache
     */
    public
    function parse(): Page
    {

        $this->getInstructionsDocument()
            ->process();

        return $this;

    }

    /**
     *
     */
    public
    function getInstructionsDocument(): InstructionsDocument
    {
        if ($this->instructionsDocument === null) {
            $this->instructionsDocument = new InstructionsDocument($this);
        }
        return $this->instructionsDocument;

    }

    public
    function delete()
    {

        Index::getOrCreate()->deletePage($this);
        saveWikiText($this->getDokuwikiId(), "", "Delete");

    }

    /**
     * @return string|null -the absolute canonical url
     */
    public
    function getAbsoluteCanonicalUrl(): ?string
    {
        return $this->getCanonicalUrl([], true);
    }


    public
    function getReadStoreOrDefault(): MetadataStore
    {
        if ($this->readStore === null) {
            /**
             * No cache please if not set
             * Cache should be in the MetadataDokuWikiStore
             * that is page requested scoped and not by slot
             */
            return MetadataDokuWikiStore::getOrCreateFromResource($this);
        }
        if (!($this->readStore instanceof MetadataStore)) {
            $this->readStore = MetadataStoreAbs::toMetadataStore($this->readStore, $this);
        }
        return $this->readStore;
    }

    /**
     * @return DokuPath
     */
    public
    function getPath(): Path
    {
        return $this->dokuPath;
    }


    /**
     * A shortcut for {@link Page::getPath()::getDokuwikiId()}
     */
    public
    function getDokuwikiId()
    {
        return $this->getPath()->getDokuwikiId();
    }

    public
    function getUid(): Metadata
    {
        return $this->pageId;
    }


    public
    function getAbsolutePath(): string
    {
        return DokuPath::PATH_SEPARATOR . $this->getDokuwikiId();
    }

    function getType(): string
    {
        return self::TYPE;
    }

    public
    function getUrlPathObject(): PageUrlPath
    {
        return $this->pageUrlPath;
    }

    public function getMainFooterSlot(): ?Page
    {
        if ($this->isSecondarySlot() || $this->isRootHomePage()) {
            return null;
        }

        try {
            Site::loadStrapUtilityTemplateIfPresentAndSameVersion();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("We can't load strap. The nearest main footer slot could not be detected, Error: {$e->getMessage()}");
            return null;
        }

        $nearestMainFooter = $this->findNearest(TplUtility::SLOT_MAIN_FOOTER_NAME);
        if ($nearestMainFooter === false) {
            return null;
        }
        return Page::createPageFromId($nearestMainFooter);


    }

    public function getSideSlot(): ?Page
    {
        if ($this->isSecondarySlot() || $this->isRootHomePage()) {
            return null;
        }

        $nearestMainFooter = $this->findNearest(Site::getSidebarName());
        if ($nearestMainFooter === false) {
            return null;
        }
        return Page::createPageFromId($nearestMainFooter);


    }

    /**
     * @param $pageName
     * @return false|string
     */
    private function findNearest($pageName)
    {
        global $ID;
        $keep = $ID;
        try {
            $ID = $this->getDokuwikiId();
            return page_findnearest($pageName);
        } finally {
            $ID = $keep;
        }

    }

    /**
     * @return Page[]
     */
    public function getSecondarySlots(): array
    {
        $secondarySlots = [];
        $sideSlot = $this->getSideSlot();
        if ($sideSlot !== null) {
            $secondarySlots[] = $sideSlot;
        }
        $footerSlot = $this->getMainFooterSlot();
        if ($footerSlot !== null) {
            $secondarySlots[] = $footerSlot;
        }
        return $secondarySlots;
    }


    public function isHidden(): bool
    {
        return isHiddenPage($this->getDokuwikiId());
    }


}
