<?php

namespace ComboStrap;


use action_plugin_combo_qualitymessage;
use DateTime;
use Exception;
use renderer_plugin_combo_analytics;


/**
 *
 * A markup (ie slot) is a logical unit
 * that represents a markup file
 *
 * For instance:
 *   * the main slot is the main markdown file and the header and footer slot
 *   * while the sidebar is a leaf
 *
 * It has its own file system {@link MarkupFileSystem} explained in the
 * https://combostrap.com/page/system (or system.txt file).
 *
 * We are not extending {@link WikiPath} because:
 *   * we want to be able to return {@link MarkupPath} in the {@link MarkupPath::getParent()} function
 * otherwise if we do, we get a hierarchical error.
 *   * we can then accepts also {@link LocalPath}
 *
 */
class MarkupPath implements ResourceCombo, Path
{

    const CANONICAL_PAGE = "markup";


    const TYPE = "page";

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

    private $uidObject;

    private LowQualityPageOverwrite $canBeOfLowQuality;
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
     * @var PageLayoutName
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
     * @var Path - we wrap a path and not extends to be able to return a {@link MarkupPath}
     * otherwise we get an hierarchy error
     */
    private Path $path;

    /**
     * Page constructor.
     *
     */
    public function __construct(Path $path)
    {

        $this->path = $path;
        if (FileSystems::isDirectory($path)) {
            $this->setCorrectPathForDirectoryToIndexPage();
        }
        $this->buildPropertiesFromFileSystem();

    }

    /**
     * The current running rendering markup
     */
    public static function createPageFromGlobalWikiId(): MarkupPath
    {
        $wikiPath = WikiPath::createRunningMarkupWikiPath();
        return self::createPageFromPathObject($wikiPath);
    }

    public static function createPageFromId($id): MarkupPath
    {
        return new MarkupPath(WikiPath::createPagePathFromId($id));
    }

    /**
     * @param $pathOrId
     * @return MarkupPath
     */
    public static function createPageFromNonQualifiedPath($pathOrId): MarkupPath
    {

//        global $ID;
//        $qualifiedId = $pathOrId;
//        resolve_pageid(getNS($ID), $qualifiedId, $exists);
//        /**
//         * Root correction
//         * yeah no root functionality in the {@link resolve_pageid resolution}
//         * meaning that we get an empty string
//         * they correct it in the link creation {@link wl()}
//         */
//        if ($qualifiedId === '') {
//            global $conf;
//            $qualifiedId = $conf['start'];
//        }
//        return PageFragment::createPageFromId($qualifiedId);
        return MarkupPath::createPageFromQualifiedPath($pathOrId);

    }

    /**
     * @return MarkupPath - the requested page
     */
    public static function createFromRequestedPage(): MarkupPath
    {
        $path = WikiPath::createRequestedPagePathFromRequest();
        return MarkupPath::createPageFromPathObject($path);
    }


    public static function createPageFromPathObject(Path $path): MarkupPath
    {
        return new MarkupPath($path);
    }


    /**
     *
     * @throws ExceptionBadSyntax - if this is not a
     * @deprecated just pass a namespace path to the page creation and you will get the index page in return
     */
    public static function getIndexPageFromNamespace(string $namespacePath): MarkupPath
    {
        WikiPath::checkNamespacePath($namespacePath);

        return MarkupPath::createPageFromId($namespacePath);
    }


    static function createPageFromQualifiedPath($qualifiedPath): MarkupPath
    {
        $path = WikiPath::createPagePathFromPath($qualifiedPath);
        return new MarkupPath($path);
    }


    /**
     *
     * @throws ExceptionCompile
     */
    public
    function setCanonical($canonical): MarkupPath
    {
        $this->canonical
            ->setValue($canonical)
            ->sendToWriteStore();
        return $this;
    }


    public function isPrimarySlot(): bool
    {
        return !$this->isSecondarySlot();
    }

    /**
     * @return bool true if this is not the main slot.
     */
    public function isSecondarySlot(): bool
    {
        $slotNames = Site::getSecondarySlotNames();
        try {
            $name = $this->getPathObject()->getLastNameWithoutExtension();
        } catch (ExceptionNotFound $e) {
            // root case
            return false;
        }
        return in_array($name, $slotNames, true);
    }

    /**
     * @return bool true if this is the side slot
     */
    public function isSideSlot(): bool
    {
        $slotNames = Site::getSidebarName();
        $name = $this->getPathObject()->getLastNameWithoutExtension();
        if ($name === null) {
            // root case
            return false;
        }
        return $name === $slotNames;
    }

    /**
     * @return bool true if this is the main
     */
    public function isMainHeaderFooterSlot(): bool
    {

        try {
            $slotNames = [Site::getPrimaryHeaderSlotName(), Site::getPrimaryFooterSlotName()];
        } catch (ExceptionCompile $e) {
            return false;
        }
        $name = $this->getPathObject()->getLastNameWithoutExtension();
        if ($name === null) {
            // root case
            return false;
        }
        return in_array($name, $slotNames, true);
    }


    /**
     * Return a canonical if set
     * otherwise derive it from the id
     * by taking the last two parts
     *
     * @return string
     * @throws ExceptionNotFound
     * @deprecated for {@link Canonical::getValueOrDefault()}
     */
    public
    function getCanonicalOrDefault(): string
    {
        return $this->canonical->getValueFromStoreOrDefault();

    }


    /**
     * Rebuild the page
     * (refresh from disk, reset object to null)
     * @return $this
     */
    public
    function rebuild(): MarkupPath
    {
        $this->readStore = null;
        $this->buildPropertiesFromFileSystem();
        $this->databasePage = null;
        return $this;
    }

    /**
     *
     * @return MarkupPath[]|null the internal links or null
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
            $pages[$referencePageId] = MarkupPath::createPageFromId($referencePageId);
        }
        return $pages;

    }


    /**
     *
     */
    public
    function getHtmlFetcher(): FetcherMarkup
    {

        try {
            return FetcherMarkup::createPageFragmentFetcherFromPath($this->getPathObject())
                ->setRequestedMimeToXhtml();
        } catch (ExceptionBadArgument $e) {
            throw new ExceptionRuntimeInternal("The path should be local. Error: {$e->getMessage()}");
        }

    }

    /**
     * Set the page quality
     * @param boolean $value true if this is a low quality page rank false otherwise
     * @throws ExceptionCompile
     */
    public
    function setCanBeOfLowQuality(bool $value): MarkupPath
    {
        return $this->setQualityIndicatorAndDeleteCacheIfNeeded($this->canBeOfLowQuality, $value);
    }

    /**
     * @return MarkupPath[] the backlinks
     * Duplicate of related
     *
     * Same as {@link WikiPath::getReferencedBy()} ?
     */
    public
    function getBacklinks(): array
    {
        $backlinks = array();
        /**
         * Same as
         * idx_get_indexer()->lookupKey('relation_references', $ID);
         */
        $ft_backlinks = ft_backlinks($this->getWikiId());
        foreach ($ft_backlinks as $backlinkId) {
            $backlinks[$backlinkId] = MarkupPath::createPageFromId($backlinkId);
        }
        return $backlinks;
    }


    /**
     * Low page quality
     * @return bool true if this is a low quality page
     */
    function isLowQualityPage(): bool
    {


        if (!$this->getCanBeOfLowQuality()) {
            return false;
        }

        if (!Site::isLowQualityProtectionEnable()) {
            return false;
        }
        try {
            return $this->getLowQualityIndicatorCalculated();
        } catch (ExceptionNotFound $e) {
            // We were returning null but null used in a condition is falsy
            // we return false
            return false;
        }

    }


    /**
     *
     */
    public function getCanBeOfLowQuality(): bool
    {

        return $this->canBeOfLowQuality->getValueOrDefault();

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
     *
     */
    public
    function getTitleOrDefault(): string
    {
        try {
            return $this->title->getValueOrDefault();
        } catch (ExceptionNotFound $e) {
            LogUtility::internalError("Internal Error: The page ($this) does not have any default title");
            return $this->getPathObject()->getLastNameWithoutExtension();
        }

    }

    /**
     * @return mixed
     * @throws ExceptionNotFound
     */
    public
    function getH1OrDefault()
    {

        return $this->h1->getValueOrDefault();

    }

    /**
     * @return mixed
     * @throws ExceptionNotFound
     */
    public
    function getDescription(): string
    {
        return $this->description->getValueFromStore();
    }


    /**
     * @return string - the description or the dokuwiki generated description
     * @throws ExceptionNotFound
     */
    public
    function getDescriptionOrElseDokuWiki(): string
    {
        return $this->description->getValueFromStoreOrDefault();
    }


    /**
     * @return string
     * The content / markup that should be parsed by the parser
     */
    public
    function getMarkup(): string
    {

        try {
            return FileSystems::getContent($this->getPathObject());
        } catch (ExceptionNotFound $e) {
            LogUtility::msg("The page ($this) was not found");
            return "";
        }

    }


    public
    function isInIndex(): bool
    {
        $Indexer = idx_get_indexer();
        $pages = $Indexer->getPages();
        $return = array_search($this->getPathObject()->getWikiId(), $pages, true);
        return $return !== false;
    }


    public
    function upsertContent($content, $summary = "Default"): MarkupPath
    {
        saveWikiText($this->getPathObject()->getWikiId(), $content, $summary);
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
        global $ACT;
        $keepACT = $ACT;
        try {
            $ACT = "show";
            $ID = $this->getPathObject()->getWikiId();
            idx_addPage($ID);
        } finally {
            $ID = $keep;
            $ACT = $keepACT;
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


    /**
     * @throws ExceptionNotFound
     */
    public
    function getFirstImage(): IFetcherLocalImage
    {
        $firstImage = FirstImage::createForPage($this);
        return $firstImage->getLocalImageFetcher();
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
     * @return DateTime
     */
    public
    function getModifiedTime(): DateTime
    {
        return $this->modifiedTime->getValueFromStore();
    }

    /**
     * @throws ExceptionNotFound
     */
    public
    function getModifiedTimeOrDefault(): DateTime
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
     * @throws ExceptionBadArgument
     */
    public function renderMetadataAndFlush(): MarkupPath
    {

        if (!FileSystems::exists($this)) {
            if (PluginUtility::isDevOrTest()) {
                LogUtility::msg("You can't render the metadata of a page that does not exist");
            }
            return $this;
        }

        /**
         * Setting the running id
         * (Used only in test)
         */
        $wikiPath = WikiPath::createFromPathObject($this->getPathObject());
        $wikiRequest = WikiRequest::createRequestOrSubRequest($wikiPath->getWikiId());
        try {
            /**
             * @var MetadataDokuWikiStore $metadataStore
             */
            $metadataStore = $this->getReadStoreOrDefault();
            $metadataStore->renderAndPersist();
        } finally {
            $wikiRequest->close($wikiPath->getWikiId());
        }

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

    public function getLangOrDefault(): string
    {
        return $this->lang->getValueOrDefault();
    }

    /**
     * Adapted from {@link FsWikiUtility::getHomePagePath()}
     * @return bool
     */
    public function isIndexPage(): bool
    {

        $startPageName = Site::getIndexPageName();
        try {
            if ($this->getPathObject()->getLastNameWithoutExtension() === $startPageName) {
                return true;
            }
        } catch (ExceptionNotFound $e) {
            // ok
        }

        try {
            /**
             * page named like the NS inside the NS
             * ie ns:ns
             */
            $objectPath = $this->path;
            $parentPath = $this->path->getParent();
            if ($parentPath->getLastNameWithoutExtension() === $objectPath->getLastNameWithoutExtension()) {
                /**
                 * If the start page does not exists, this is the index page
                 */
                $startPage = $parentPath->resolve($startPageName);
                if (!FileSystems::exists($startPage)) {
                    return true;
                }
            }
        } catch (ExceptionNotFound $e) {
            // no parent, no last name, etc
        }


        return false;
    }


    /**
     * @throws ExceptionNotFound
     */
    public
    function getPublishedTime(): DateTime
    {
        return $this->publishedDate->getValueFromStore();
    }

    /**
     * @return bool
     * @deprecated for {@link FileSystems::exists()}
     */
    public function exists(): bool
    {
        return FileSystems::exists($this);
    }

    /**
     * @return DateTime
     * @throws ExceptionNotFound
     */
    public
    function getPublishedElseCreationTime(): DateTime
    {
        return $this->publishedDate->getValueFromStoreOrDefault();
    }


    public
    function isLatePublication(): bool
    {
        try {
            $dateTime = $this->getPublishedElseCreationTime();
        } catch (ExceptionNotFound $e) {
            return false;
        }
        return $dateTime > new DateTime('now');
    }

    /**
     * The unique page Url (also known as Canonical URL) used:
     *   * in the link
     *   * in the canonical ref
     *   * in the site map
     * @return Url
     */
    public
    function getCanonicalUrl(): Url
    {

        /**
         * Dokuwiki Methodology Taken from {@link tpl_metaheaders()}
         */
        if ($this->isRootHomePage()) {
            try {
                return UrlEndpoint::createBaseUrl();
            } catch (ExceptionBadArgument|ExceptionBadSyntax $e) {
                LogUtility::error("The base url returns an error, we have returned an empty url for this root page. Error: {$e->getMessage()}");
                return Url::createEmpty();
            }
        }

        return UrlEndpoint::createDokuUrl()
            ->setQueryParameter(DokuwikiId::DOKUWIKI_ID_ATTRIBUTE, $this->getUrlId());


    }

    public function getUrlWhereIdIs($type = null): Url
    {
        if ($type === null) {
            return $this->getCanonicalUrl();
        }
        $pageUrlId = WikiPath::toDokuwikiId(PageUrlPath::createForPage($this)
            ->getUrlPathFromType($type));
        return UrlEndpoint::createDokuUrl()
            ->setQueryParameter(DokuwikiId::DOKUWIKI_ID_ATTRIBUTE, $pageUrlId);
    }


    /**
     *
     * @return string|null - the locale facebook way
     * @throws ExceptionNotFound
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
     * @deprecated use a {@link FetcherMarkup::getFetchPathAsHtmlString()} instead
     */
    public function toXhtml(): string
    {

        return $this->getHtmlFetcher()->getFetchPathAsHtmlString();

    }


    public
    function getHtmlAnchorLink($logicalTag = null): string
    {
        $id = $this->getPathObject()->getWikiId();
        try {
            return LinkMarkup::createFromPageIdOrPath($id)
                    ->toAttributes($logicalTag)
                    ->toHtmlEnterTag("a")
                . $this->getNameOrDefault()
                . "</a>";
        } catch (ExceptionCompile $e) {
            LogUtility::msg("The markup ref returns an error for the creation of the page anchor html link ($this). Error: {$e->getMessage()}");
            return "<a href=\"{$this->getCanonicalUrl()}\" data-wiki-id=\"$id\">{$this->getNameOrDefault()}</a>";
        }
    }


    /**
     * Without the `:` at the end
     * @return string
     * @throws ExceptionNotFound
     * @deprecated / shortcut for {@link WikiPath::getParent()}
     * Because a page has always a parent, the string is never null.
     */
    public function getNamespacePath(): string
    {

        return $this->getParent()->toPathString();

    }


    /**
     * @return $this
     * @deprecated use {@link MetadataDokuWikiStore::deleteAndFlush()}
     */
    public
    function deleteMetadatasAndFlush(): MarkupPath
    {
        MetadataDokuWikiStore::getOrCreateFromResource($this)
            ->deleteAndFlush();
        return $this;
    }

    /**
     * @throws ExceptionNotFound
     */
    public
    function getName(): string
    {

        return $this->pageName->getValueFromStore();

    }

    public
    function getNameOrDefault(): string
    {

        return ResourceName::createForResource($this)->getValueOrDefault();


    }

    /**
     * @param $property
     */
    public
    function unsetMetadata($property)
    {
        $meta = p_read_metadata($this->getPathObject()->getWikiId());
        if (isset($meta['persistent'][$property])) {
            unset($meta['persistent'][$property]);
        }
        p_save_metadata($this->getPathObject()->getWikiId(), $meta);

    }

    /**
     * @return array - return the standard / generated metadata
     * used to create a variable environment (context) in rendering
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
            PageLayoutName::PROPERTY_NAME,
            // Dokuwiki id is deprecated for path, no more advertised
            DokuwikiId::DOKUWIKI_ID_ATTRIBUTE,
            PageLevel::PROPERTY_NAME
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
            try {
                $value = $metadata
                    ->setResource($this)
                    ->setWriteStore(TemplateStore::class)
                    ->toStoreValueOrDefault();


            } catch (ExceptionNotFound $e) {
                $value = null;
            }
            $array[$metadataName] = $value;
        }

        $array["url"] = $this->getCanonicalUrl();
        $array["now"] = Iso8601Date::createFromNow()->toString();
        return $array;

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

    /**
     * @throws ExceptionNotFound
     */
    public
    function getStartDate(): DateTime
    {
        return $this->startDate->getValueFromStore();
    }

    /**
     * A page id
     * @return string
     * @throws ExceptionNotFound - when the page does not exist
     */
    public
    function getPageId(): string
    {
        return $this->pageId->getValue();
    }


    public
    function getAnalyticsDocument(): FetcherMarkup
    {
        return renderer_plugin_combo_analytics::createAnalyticsFetcherForPageFragment($this);
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
        return Identity::isWriter($this->getWikiId());
    }


    public
    function isRootHomePage(): bool
    {
        global $conf;
        $startPageName = $conf['start'];
        return $this->getPathObject()->toPathString() === ":$startPageName";

    }

    /**
     * Used when the page is moved to take the Page Id of the source
     * @param string|null $pageId
     * @return MarkupPath
     * @throws ExceptionCompile
     */
    public
    function setPageId(?string $pageId): MarkupPath
    {

        $this->pageId
            ->setValue($pageId)
            ->sendToWriteStore();

        return $this;

    }

    /**
     * @throws ExceptionNotFound
     */
    public
    function getPageType(): string
    {
        return $this->type->getValueFromStore();
    }

    /**
     * @throws ExceptionNotFound
     */
    public
    function getCanonical(): string
    {
        return $this->canonical->getValueFromStore();
    }

    /**
     * Create a canonical from the last page path part.
     *
     * @return string|null
     * @throws ExceptionNotFound
     */
    public
    function getDefaultCanonical(): ?string
    {
        return $this->canonical->getDefaultValue();
    }

    /**
     * @throws ExceptionNotFound
     */
    public
    function getLayout()
    {
        return $this->layout->getValueFromStore();
    }

    /**
     * @throws ExceptionNotFound
     */
    public
    function getDefaultPageName(): string
    {
        return $this->pageName->getDefaultValue();
    }

    public
    function getDefaultTitle(): string
    {
        return $this->title->getDefaultValue();
    }

    /**
     * @throws ExceptionNotFound
     */
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
     *
     * @throws ExceptionCompile
     */
    public
    function setLowQualityIndicatorCalculation($bool): MarkupPath
    {
        return $this->setQualityIndicatorAndDeleteCacheIfNeeded($this->lowQualityIndicatorCalculated, $bool);
    }


    /**
     * Change the quality indicator
     * and if the quality level has become low
     * and that the protection is on, delete the cache
     * @param MetadataBoolean $lowQualityAttributeName
     * @param bool $value
     * @return MarkupPath
     * @throws ExceptionBadArgument - if the value cannot be persisted
     */
    private
    function setQualityIndicatorAndDeleteCacheIfNeeded(MetadataBoolean $lowQualityAttributeName, bool $value): MarkupPath
    {
        try {
            $actualValue = $lowQualityAttributeName->getValue();
        } catch (ExceptionNotFound $e) {
            $actualValue = null;
        }
        if ($value !== $actualValue) {
            $lowQualityAttributeName
                ->setValue($value)
                ->persist();
        }
        return $this;
    }


    /**
     * @throws ExceptionNotFound
     */
    public
    function getLowQualityIndicatorCalculated()
    {

        return $this->lowQualityIndicatorCalculated->getValueOrDefault();

    }

    /**
     * @return PageImage[]
     */
    public
    function getPageMetadataImages(): array
    {
        return $this->pageImages->getValueAsPageImages();
    }


    /**
     * @param array|string $jsonLd
     * @return $this
     * @throws ExceptionCompile
     * @deprecated for {@link LdJson}
     */
    public
    function setJsonLd($jsonLd): MarkupPath
    {
        $this->ldJson
            ->setValue($jsonLd)
            ->sendToWriteStore();
        return $this;
    }

    /**
     * @throws ExceptionCompile
     */
    public
    function setPageType(string $value): MarkupPath
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
     * @return string
     */
    public
    function getSlugOrDefault(): string
    {
        try {
            return $this->getSlug();
        } catch (ExceptionNotFound $e) {
            return $this->getDefaultSlug();
        }

    }

    /**
     *
     * @return string
     *
     */
    public
    function getDefaultSlug(): string
    {
        return $this->slug->getDefaultValue();
    }

    /**
     * The parent page is the parent in the page tree directory
     *
     * If the page is at the root, the parent page is the root home
     * Only the root home does not have any parent page and return null.
     *
     * @return MarkupPath
     * @throws ExceptionNotFound
     */
    public function getParent(): MarkupPath
    {

        $names = $this->getNames();
        if (sizeof($names) == 0) {
            throw new ExceptionNotFound("No parent page");
        }
        $slice = 1;
        if ($this->isIndexPage()) {
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
            throw new ExceptionNotFound("No parent page");
        }
        /**
         * Get the actual directory for a page
         * or the parent directory for a home page
         */
        $parentNames = array_slice($names, 0, sizeof($names) - $slice);
        /**
         * Create the parent namespace id
         */
        $parentNamespaceId = implode(WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, $parentNames) . WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT;
        try {
            return self::getIndexPageFromNamespace($parentNamespaceId);
        } catch (ExceptionBadSyntax $e) {
            $message = "Error on getParentPage, null returned - Error: {$e->getMessage()}";
            LogUtility::internalError($message);
            throw new ExceptionNotFound($message);
        }

    }

    /**
     * @throws ExceptionCompile
     */
    public
    function setDescription($description): MarkupPath
    {

        $this->description
            ->setValue($description)
            ->sendToWriteStore();
        return $this;
    }

    /**
     * @throws ExceptionCompile
     * @deprecated uses {@link EndDate} instead
     */
    public
    function setEndDate($value): MarkupPath
    {
        $this->endDate
            ->setFromStoreValue($value)
            ->sendToWriteStore();
        return $this;
    }

    /**
     * @throws ExceptionCompile
     * @deprecated uses {@link StartDate} instead
     */
    public
    function setStartDate($value): MarkupPath
    {
        $this->startDate
            ->setFromStoreValue($value)
            ->sendToWriteStore();
        return $this;
    }

    /**
     * @throws ExceptionCompile
     */
    public
    function setPublishedDate($value): MarkupPath
    {
        $this->publishedDate
            ->setFromStoreValue($value)
            ->sendToWriteStore();
        return $this;
    }

    /**
     * Utility to {@link ResourceName::setValue()}
     * Used mostly to create page in test
     * @throws ExceptionCompile
     */
    public
    function setPageName($value): MarkupPath
    {
        $this->pageName
            ->setValue($value)
            ->sendToWriteStore();
        return $this;
    }


    /**
     * @throws ExceptionCompile
     */
    public
    function setTitle($value): MarkupPath
    {
        $this->title
            ->setValue($value)
            ->sendToWriteStore();
        return $this;
    }

    /**
     * @throws ExceptionCompile
     */
    public
    function setH1($value): MarkupPath
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
    function setRegion($value): MarkupPath
    {
        $this->region
            ->setFromStoreValue($value)
            ->sendToWriteStore();
        return $this;
    }

    /**
     * @throws ExceptionCompile
     */
    public
    function setLang($value): MarkupPath
    {

        $this->lang
            ->setFromStoreValue($value)
            ->sendToWriteStore();
        return $this;
    }

    /**
     * @throws ExceptionCompile
     */
    public
    function setLayout($value): MarkupPath
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
        $this->slug = \ComboStrap\Slug::createForPage($this);
        $this->canBeOfLowQuality = LowQualityPageOverwrite::createForPage($this);
        $this->lowQualityIndicatorCalculated = LowQualityCalculatedIndicator::createFromPage($this);
        $this->qualityMonitoringIndicator = QualityDynamicMonitoringOverwrite::createFromPage($this);
        $this->modifiedTime = \ComboStrap\ModificationDate::createForPage($this);
        $this->pageUrlPath = PageUrlPath::createForPage($this);
        $this->layout = PageLayoutName::createFromPage($this);

    }


    function getPageIdAbbr()
    {

        if ($this->getPageId() === null) return null;
        return PageId::getAbbreviated($this->getPageId());

    }

    public
    function setDatabasePage(DatabasePageRow $databasePage): MarkupPath
    {
        $this->databasePage = $databasePage;
        return $this;
    }


    /**
     *
     */
    public function getUrlPath(): string
    {

        return $this->pageUrlPath->getValueOrDefault();

    }


    /**
     * @return string|null
     *
     * @throws ExceptionNotFound
     */
    public
    function getSlug(): string
    {
        return $this->slug->getValue();
    }


    /**
     * @throws ExceptionCompile
     */
    public
    function setSlug($slug): MarkupPath
    {
        $this->slug
            ->setFromStoreValue($slug)
            ->sendToWriteStore();
        return $this;
    }


    public
    function getUrlId()
    {
        return WikiPath::toDokuwikiId($this->getUrlPath());
    }


    /**
     * @throws ExceptionCompile
     */
    public
    function setQualityMonitoringIndicator($boolean): MarkupPath
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
    function setReadStore($store): MarkupPath
    {
        $this->readStore = $store;
        return $this;
    }


    /**
     * @param array $usages
     * @return PageImage[]
     */
    public
    function getImagesForTheFollowingUsages(array $usages): array
    {
        $usages = array_merge($usages, [PageImageUsage::ALL]);
        $images = [];
        foreach ($this->getPageMetadataImages() as $pageImage) {
            foreach ($usages as $usage) {
                if (in_array($usage, $pageImage->getUsages())) {
                    $images[] = $pageImage->getImagePath();
                    continue 2;
                }
            }
        }
        return $images;

    }


    /**
     * @throws ExceptionNotFound
     */
    public
    function getKeywords(): array
    {
        return $this->keywords->getValue();
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getKeywordsOrDefault(): array
    {
        return $this->keywords->getValueOrDefaults();
    }


    /**
     * @throws ExceptionCompile
     */
    public
    function setKeywords($value): MarkupPath
    {
        $this->keywords
            ->setFromStoreValue($value)
            ->sendToWriteStore();
        return $this;
    }

    /**
     * @return DateTime|null
     * @throws ExceptionNotFound
     * @deprecated for {@link CacheExpirationDate}
     */
    public
    function getCacheExpirationDate(): ?DateTime
    {
        return $this->cacheExpirationDate->getValue();
    }

    /**
     * @return DateTime|null
     * @throws ExceptionNotFound
     * @deprecated for {@link CacheExpirationDate}
     */
    public
    function getDefaultCacheExpirationDate(): ?DateTime
    {
        return $this->cacheExpirationDate->getDefaultValue();
    }

    /**
     * @return string|null
     * @throws ExceptionNotFound
     * @deprecated for {@link CacheExpirationFrequency}
     */
    public
    function getCacheExpirationFrequency(): string
    {
        return $this->cacheExpirationFrequency->getValue();
    }


    /**
     * @param DateTime $cacheExpirationDate
     * @return $this
     * @deprecated for {@link CacheExpirationDate}
     */
    public
    function setCacheExpirationDate(DateTime $cacheExpirationDate): MarkupPath
    {
        $this->cacheExpirationDate->setValue($cacheExpirationDate);
        return $this;
    }


    /**
     *
     */
    public
    function getInstructionsDocument(): FetcherMarkup
    {

        return FetcherMarkup::createPageFragmentFetcherFromPath($this->getPathObject())
            ->setRequestedMimeToInstructions();

    }

    public
    function delete()
    {

        Index::getOrCreate()->deletePage($this);
        saveWikiText($this->getWikiId(), "", "Delete");

    }

    /**
     * @return Url -the absolute canonical url
     */
    public
    function getAbsoluteCanonicalUrl(): Url
    {
        return $this->getCanonicalUrl()->toAbsoluteUrl();
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
     * @return Path
     * A markup path wraps a path
     */
    public function getPathObject(): Path
    {
        return $this->path;
    }


    /**
     * A shortcut for {@link MarkupPath::getPathObject()::getDokuwikiId()}
     *
     * @throws ExceptionBadArgument - if the markup path is not a {@link WikiPath}
     */
    public
    function getWikiId(): string
    {
        $path = $this->getPathObject();
        return WikiPath::createFromPathObject($path)->getWikiId();
    }

    public
    function getUid(): Metadata
    {
        return $this->pageId;
    }


    public
    function getAbsolutePath(): string
    {
        return WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT . $this->getWikiId();
    }

    /**
     * Todo, it should be a property of the markup not every markup file are main page markup.
     * @return string
     */
    function getType(): string
    {
        return self::TYPE;
    }

    public
    function getUrlPathObject(): PageUrlPath
    {
        return $this->pageUrlPath;
    }


    public function getSideSlot(): ?MarkupPath
    {
        /**
         * Only primary slot have a side slot
         * Root Home page does not have one either
         */
        if ($this->isSecondarySlot() || $this->isRootHomePage()) {
            return null;
        }

        $nearestMainFooter = $this->findNearest(Site::getSidebarName());
        if ($nearestMainFooter === false) {
            return null;
        }
        return MarkupPath::createPageFromId($nearestMainFooter);


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
            $ID = $this->getWikiId();
            return page_findnearest($pageName);
        } finally {
            $ID = $keep;
        }

    }

    /**
     * The slots that are independent from the primary slot
     *
     * @return MarkupPath[]
     */
    public function getPrimaryIndependentSlots(): array
    {
        $secondarySlots = [];
        $sideSlot = $this->getSideSlot();
        if ($sideSlot !== null) {
            $secondarySlots[] = $sideSlot;
        }
        return $secondarySlots;
    }


    public function isHidden(): bool
    {
        return isHiddenPage($this->getWikiId());
    }


    public function getPrimaryHeaderPage(): ?MarkupPath
    {
        $nearest = page_findnearest(Site::getPrimaryHeaderSlotName());
        if ($nearest === false) {
            return null;
        }
        return MarkupPath::createPageFromId($nearest);
    }

    public function createPageFetcherHtml(): FetcherPage
    {
        return FetcherPage::createPageFetcherFromPage($this);
    }

    public function getHttpResponse(): HttpResponse
    {
        return HttpRequest::fetchXhtmlPageResponse($this->getWikiId());
    }

    public function getOutline(): Outline
    {
        $instructions = $this->getInstructionsDocument()->getFetchPathAsInstructionsArray();
        $callStack = CallStack::createFromInstructions($instructions);
        return Outline::createFromCallStack($callStack, $this);
    }


    public function persistToDefaultMetaStore(): MarkupPath
    {
        $this->getReadStoreOrDefault()->persist();
        return $this;
    }

    private function getPrimaryFooterPage(): ?MarkupPath
    {
        $nearest = page_findnearest(Site::getPrimaryFooterSlotName());
        if ($nearest === false) {
            return null;
        }
        return MarkupPath::createPageFromId($nearest);
    }

    /**
     * Set the page path to an index page for a directory path
     * @return void
     */
    private function setCorrectPathForDirectoryToIndexPage(): void
    {
        /**
         * We correct the path
         * We don't return a page because it does not work in a constructor
         */
        $startPageName = Site::getIndexPageName();
        $indexPath = $this->path->resolve($startPageName);
        if (FileSystems::exists($indexPath)) {
            // start page inside namespace
            $this->path = $indexPath;
            return;
        }

        // page named like the NS inside the NS
        try {
            $parentName = $this->getLastNameWithoutExtension();
            $nsInsideNsIndex = $this->path->resolve($parentName);
            if (FileSystems::exists($nsInsideNsIndex)) {
                $this->path = $nsInsideNsIndex;
                return;
            }
        } catch (ExceptionNotFound $e) {
            // no last name
        }

        // We don't support the child page
        // Does not exist but can be used by hierarchical function
        $this->path = $indexPath;
    }


    public function getUidObject(): Metadata
    {
        if ($this->uidObject === null) {
            try {
                $this->uidObject = Metadata::toMetadataObject($this->getUid())
                    ->setResource($this);
            } catch (ExceptionBadArgument $e) {
                throw new ExceptionRuntimeInternal("Uid object is a metadata object. It should not happen.", self::CANONICAL_PAGE, 1, $e);
            }
        }

        return $this->uidObject;
    }

    function getExtension()
    {
        return $this->path->getExtension();
    }

    function getLastNameWithoutExtension(): string
    {
        return $this->path->getLastNameWithoutExtension();
    }

    function getScheme(): string
    {
        return MarkupFileSystem::SCHEME;
    }

    function getLastName(): string
    {
        return $this->path->getLastName();
    }

    function getNames()
    {
        return $this->path->getNames();
    }

    function toPathString(): string
    {
        return $this->path->toPathString();
    }

    function toUriString(): string
    {
        return $this->path->toUriString();
    }

    function toAbsolutePath(): Path
    {
        return $this->path->toAbsolutePath();
    }

    function getMime(): Mime
    {
        return $this->path->getMime();
    }

    function resolve(string $name)
    {
        return $this->path->resolve($name);
    }

    function getUrl(): Url
    {
        return $this->path->getUrl();
    }

    function getHost(): string
    {
        return $this->path->getHost();
    }

    public function __toString(): string
    {
        return "$this->path";
    }

}
