<?php

namespace ComboStrap;


use syntax_plugin_combo_container;

class FetcherPage extends IFetcherAbs implements IFetcherSource, IFetcherString
{

    use FetcherTraitWikiPath;

    const NAME = "page";
    const CANONICAL = "page";

    /**
     * A configuration to take over the show action
     * of any template
     */
    const CONF_ENABLE_AS_SHOW_ACTION = "enablePage";
    const CONF_ENABLE_AS_SHOW_ACTION_DEFAULT = 1;

    private string $requestedLayout;
    private WikiRequest $wikiRequest;
    private bool $build = false;
    private bool $closed = false;


    private MarkupPath $requestedMarkupPath;
    private string $requestedLayoutName;
    private PageLayout $pageLayout;


    public static function createPageFetcherFromRequestedPage(): FetcherPage
    {
        return self::createPageFetcherFromPath(WikiPath::createRequestedPagePathFromRequest());
    }

    private static function createPageFetcherFromPath(Path $path): FetcherPage
    {
        $fetcherPage = new FetcherPage();
        $fetcherPage->setRequestedPath($path);
        return $fetcherPage;
    }

    public static function createPageFetcherFromId(string $wikiId): FetcherPage
    {
        $wikiPath = WikiPath::createPagePathFromId($wikiId);
        return self::createPageFetcherFromPath($wikiPath);
    }

    /**
     * @return bool
     */
    public static function isEnabledAsShowAction(): bool
    {
        $confValue = PluginUtility::getConfValue(self::CONF_ENABLE_AS_SHOW_ACTION, self::CONF_ENABLE_AS_SHOW_ACTION_DEFAULT);
        return $confValue === 1;
    }

    /**
     * @param Url|null $url
     * @return Url
     *
     * Note: The fetch url is the {@link FetcherCache keyCache}
     * @throws ExceptionNotFound
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
        $this->addLocalPathParametersToFetchUrl($url, DokuwikiId::DOKUWIKI_ID_ATTRIBUTE);;
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
        return $this;
    }


    public static function createPageFetcherFromPage(MarkupPath $pageFragment): FetcherPage
    {
        return self::createPageFetcherFromPath($pageFragment->getPathObject());
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

        $cache = FetcherCache::createFrom($this)
            ->addFileDependency($this->pageLayout->getCssPath())
            ->addFileDependency($this->pageLayout->getJsPath())
            ->addFileDependency($this->pageLayout->getHtmlTemplatePath());

        $htmlFragmentByVariables = [];

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
             * The {@link FetcherMarkup::getFetchPath() Get fetch path}
             * will start the rendering if there is no HTML path
             * or the cache is not fresh
             */
            $fetcherMainPageFragment = $mainFetcher->getMarkupFetcher();
            try {
                $path = $fetcherMainPageFragment->getFetchPath();
            } finally {
                $fetcherMainPageFragment->close();
            }

            $cache->addFileDependency($path);
        } catch (ExceptionNotFound $e) {
            // it should be found
            throw new ExceptionNotFound("The main page markup document was not found. Error: {$e->getMessage()}", self::NAME);
        } catch (ExceptionBadArgument $e) {
            throw new ExceptionBadArgument("The main page markup document could be served as wiki path. Error: {$e->getMessage()}", self::NAME);
        }

        /**
         * Run the secondary slots
         */
        foreach ($this->pageLayout->getPageLayoutElements() as $pageElement) {
            if ($pageElement->isMain()) {
                // already done
                continue;
            }
            try {
                $fetcherPageFragment = $pageElement->getMarkupFetcher();
                try {
                    $cache->addFileDependency($fetcherPageFragment->getFetchPath());
                } finally {
                    $fetcherPageFragment->close();
                }
            } catch (ExceptionNotFound $e) {
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
        try {
            $mainHtml = $mainFetcher->getFetchString();
        } finally {
            $mainFetcher->close();
        }
        $htmlDocumentString = $this->pageLayout->generateAndGetPageHtmlAsString($mainHtml);

        /**
         * We store only the public pages
         */
        if ($this->isPublicStaticPage()) {
            $cache->storeCache($htmlDocumentString);
        }

        return $htmlDocumentString;

    }


    /**
     * @throws ExceptionNotFound
     */
    function getBuster(): string
    {
        return FileSystems::getCacheBuster($this->getSourcePath());
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
    public function getFetchPathAsHtmlDom(): XmlDocument
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
        $this->build = true;

        /**
         * Request / Environment First
         */
        $this->wikiRequest = WikiRequest::createFromRequestId($this->getRequestedPath()->getWikiId());

        $this->requestedMarkupPath = MarkupPath::createPageFromPathObject($this->getRequestedPath());

        $pageLang = Lang::createForMarkup($this->getRequestedPage());
        $title = PageTitle::createForMarkup($this->getRequestedPage())->getValueOrDefault();
        $this->pageLayout = PageLayout::createFromLayoutName($this->getRequestedLayoutOrDefault())
            ->setRequestedContextPath($this->getRequestedPath())
            ->setRequestedLang($pageLang)
            ->setRequestedTitle($title);


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


    private function setRequestedPath(Path $requestedPath): FetcherPage
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
        $this->wikiRequest->close($this->getRequestedPath()->getWikiId());
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
        return PluginUtility::getConfValue(FetcherRailBar::CONF_PRIVATE_RAIL_BAR, 0) === 1 && !Identity::isLoggedIn();
    }

    private function getRequestedLayoutOrDefault(): string
    {
        try {
            return $this->getRequestedLayout();
        } catch (ExceptionNotFound $e) {
            return PageLayoutName::createFromPage($this->getRequestedPage())->getValueOrDefault();
        }
    }

}
