<?php

namespace ComboStrap;


class FetcherPage extends IFetcherAbs implements IFetcherSource, IFetcherString
{

    use FetcherTraitWikiPath;

    const NAME = "page";
    const CANONICAL = "page";

    private string $requestedLayout;
    private bool $build = false;
    private bool $closed = false;


    private MarkupPath $requestedMarkupPath;
    private string $requestedLayoutName;
    private PageTemplate $pageLayout;
    private FetcherCache $fetcherCache;



    public static function createPageFetcherFromPath(Path $path): FetcherPage
    {
        $fetcherPage = new FetcherPage();
        $fetcherPage->setRequestedPath($path);
        return $fetcherPage;
    }

    public static function createPageFetcherFromId(string $wikiId): FetcherPage
    {
        $wikiPath = WikiPath::createMarkupPathFromId($wikiId);
        return self::createPageFetcherFromPath($wikiPath);
    }

    /**
     * @throws ExceptionBadArgument
     */
    public static function createPageFragmentFetcherFromUrl(Url $fetchUrl): FetcherPage
    {
        $pageFragment = new FetcherPage();
        $pageFragment->buildFromUrl($fetchUrl);
        return $pageFragment;
    }

    /**
     * @param Url|null $url
     * @return Url
     *
     * Note: The fetch url is the {@link FetcherCache keyCache}
     */
    function getFetchUrl(Url $url = null): Url
    {
        /**
         * Overwrite default fetcher endpoint
         * that is {@link UrlEndpoint::createFetchUrl()}
         */
        $url = UrlEndpoint::createDokuUrl();
        $url = parent::getFetchUrl($url);
        try {
            $url->addQueryParameter(PageLayoutName::PROPERTY_NAME, $this->getRequestedLayout());
        } catch (ExceptionNotFound $e) {
            // no requested layout
        }
        $this->addLocalPathParametersToFetchUrl($url, DokuwikiId::DOKUWIKI_ID_ATTRIBUTE);

        // the drive is not needed
        $url->removeQueryParameter(WikiPath::DRIVE_ATTRIBUTE);

        // this is the default fetcher, no need to add it as parameter
        $url->removeQueryParameter(IFetcher::FETCHER_KEY);

        return $url;
    }

    /**
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     * @throws ExceptionNotFound
     */
    public function buildFromTagAttributes(TagAttributes $tagAttributes): IFetcher
    {
        parent::buildFromTagAttributes($tagAttributes);
        $this->buildOriginalPathFromTagAttributes($tagAttributes);
        $layout = $tagAttributes->getValueAndRemoveIfPresent(PageLayoutName::PROPERTY_NAME);
        if($layout!==null){
            $this->setRequestedLayout($layout);
        }
        return $this;
    }


    public static function createPageFetcherFromMarkupPath(MarkupPath $markupPath): FetcherPage
    {
        return self::createPageFetcherFromPath($markupPath->getPathObject());
    }


    /**
     * @return string
     * @throws ExceptionBadSyntax - if the layout is incorrect (missing element, ...)
     * @throws ExceptionNotFound - if the main markup fragment could not be found
     * @throws ExceptionBadArgument - if the main markup fragment path can not be transformed as wiki path
     */
    public function getFetchString(): string
    {

        $this->buildObjectIfNeeded();

        $cache = $this->fetcherCache
            ->addFileDependency($this->pageLayout->getCssPath())
            ->addFileDependency($this->pageLayout->getJsPath())
            ->addFileDependency($this->pageLayout->getHtmlTemplatePath());


        /**
         * Run the main slot
         * Get the HTML fragment
         * The first one should be the main because it has the frontmatter
         */
        try {
            $mainFetcher = $this->pageLayout->getMainElement();
        } catch (ExceptionNotFound $e) {
            throw new ExceptionBadSyntax("The main element was not found in the html template ({$this->getLayout()}");
        }
        try {
            /**
             * The {@link FetcherMarkup::processIfNeededAndGetFetchPath() Get fetch path}
             * will start the rendering if there is no HTML path
             * or the cache is not fresh
             */
            $fetcherMainPageFragment = $mainFetcher->getMarkupFetcher();
            $path = $fetcherMainPageFragment->processIfNeededAndGetFetchPath();
            $cache->addFileDependency($path);
        } catch (ExceptionNotFound $e) {
            // it should be found
            throw new ExceptionNotFound("The main page markup document was not found. Error: {$e->getMessage()}", self::NAME);
        } catch (\Exception $e) {
            throw new ExceptionBadArgument("The main page markup document could be served as wiki path. Error: {$e->getMessage()}", self::NAME);
        }

        /**
         * Run the secondary slots
         */
        $pageLayoutElements = $this->pageLayout->getPageLayoutElements();
        foreach ($pageLayoutElements as $pageElement) {
            if ($pageElement->isMain()) {
                // already done
                continue;
            }
            try {
                $fetcherPageFragment = $pageElement->getMarkupFetcher();
                $cache->addFileDependency($fetcherPageFragment->processIfNeededAndGetFetchPath());
            } catch (ExceptionNotFound|ExceptionCompile $e) {
                // no markup for this slot
            }
        }

        /**
         * Public static cache
         * (Do we create the page or return the cache)
         */
        if ($cache->isCacheUsable() && $this->isPublicStaticPage()) {
            try {
                return FileSystems::getContent($cache->getFile());
            } catch (ExceptionNotFound $e) {
                // the cache file should exists
                LogUtility::internalError("The cache HTML fragment file was not found", self::NAME);
            }
        }

        $mainFetcher = $this->pageLayout->getMainElement()->getMarkupFetcher();
        $mainHtml = $mainFetcher->getFetchString();


        /**
         * Found in {@link tpl_content()}
         * Used to add html such as {@link \action_plugin_combo_routermessage}
         * Not sure if this is the right place to add it.
         */
        ob_start();
        global $ACT;
        \dokuwiki\Extension\Event::createAndTrigger('TPL_ACT_RENDER', $ACT);
        $tplActRenderOutput = ob_get_clean();
        $mainHtml = $tplActRenderOutput . $mainHtml;

        /**
         * Generate the whole html page via the layout
         */
        $htmlDocumentString = $this->pageLayout->render($mainHtml);

        /**
         * We store only the public pages
         */
        if ($this->isPublicStaticPage()) {
            $cache->storeCache($htmlDocumentString);
        }

        return $htmlDocumentString;

    }


    /**
     *
     */
    function getBuster(): string
    {
        return "";
    }

    public function getMime(): Mime
    {
        return Mime::create(Mime::HTML);
    }

    public function getFetcherName(): string
    {
        return self::NAME;
    }

    /**
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotFound
     * @throws ExceptionBadArgument
     */
    public function getFetchAsHtmlDom(): XmlDocument
    {
        $content = $this->getFetchString();
        return XmlDocument::createHtmlDocFromMarkup($content);
    }


    /**
     *
     */
    private function buildObjectIfNeeded(): void
    {

        if ($this->build) {
            if ($this->closed) {
                throw new ExceptionRuntimeInternal("This fetcher page object has already been close and cannot be reused", self::NAME);
            }
            return;
        }
        $this->fetcherCache = FetcherCache::createFrom($this);
        $this->build = true;

        $this->requestedMarkupPath = MarkupPath::createPageFromPathObject($this->getRequestedPath());

        $pageLang = Lang::createForMarkup($this->getRequestedPage());
        $title = PageTitle::createForMarkup($this->getRequestedPage())->getValueOrDefault();
        try {
            $layoutName = $this->getRequestedLayoutOrDefault();
            $this->pageLayout = PageTemplate::createFromLayoutName($layoutName)
                ->setRequestedContextPath($this->getRequestedPath())
                ->setRequestedLang($pageLang)
                ->setRequestedTitle($title);
        } catch (ExceptionBadSyntax|ExceptionNotFound $e) {
            throw new ExceptionRuntimeInternal("Layout error while trying to create the page: {$e->getMessage()}", self::NAME, 1, $e);
        }


    }

    public function getRequestedPath(): WikiPath
    {
        return $this->getSourcePath();
    }

    public function setRequestedLayout(string $layoutValue): FetcherPage
    {
        $this->requestedLayout = $layoutValue;
        return $this;
    }

    /**
     * @throws ExceptionNotFound
     */
    private function getRequestedLayout(): string
    {
        if (!isset($this->requestedLayout)) {
            throw new ExceptionNotFound("No requested layout");
        }
        return $this->requestedLayout;
    }


    public function setRequestedPath(Path $requestedPath): FetcherPage
    {
        try {
            $requestedPath = WikiPath::createFromPathObject($requestedPath);
        } catch (ExceptionBadArgument $e) {
            throw new ExceptionRuntimeInternal("Not a local wiki path", self::NAME, 1, $e);
        }
        $this->setSourcePath($requestedPath);
        return $this;
    }


    /**
     *
     */
    public function close(): FetcherPage
    {
        // nothing to do
        return $this;
    }


    private function getRequestedPage(): MarkupPath
    {
        $this->buildObjectIfNeeded();
        return $this->requestedMarkupPath;
    }

    private function getLayout(): string
    {
        $this->buildObjectIfNeeded();
        return $this->requestedLayoutName;
    }


    /**
     * The cache stores only public pages.
     *
     * ie when the user is unknown
     * and there is no railbar
     * (railbar is dynamically created even for the public
     * and the javascript for the menu item expects to run after a window load event)
     *
     * @return bool
     */
    private function isPublicStaticPage(): bool
    {
        return SiteConfig::getConfValue(FetcherRailBar::CONF_PRIVATE_RAIL_BAR, 0) === 1 && !Identity::isLoggedIn();
    }

    private function getRequestedLayoutOrDefault(): string
    {
        try {
            return $this->getRequestedLayout();
        } catch (ExceptionNotFound $e) {
            return PageLayoutName::createFromPage($this->getRequestedPage())->getValueOrDefault();
        }
    }

    public function getContentCachePath(): LocalPath
    {
        $this->buildObjectIfNeeded();
        return $this->fetcherCache->getFile();
    }

    public function getLabel(): string
    {
        $sourcePath = $this->getSourcePath();
        return ResourceName::getFromPath($sourcePath);
    }
}
