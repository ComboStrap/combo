<?php

namespace ComboStrap;


use ComboStrap\Meta\PageTemplateName;

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
    private PageTemplate $pageTemplate;
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
            $url->addQueryParameter(PageTemplateName::PROPERTY_NAME, $this->getRequestedLayout());
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
        $layout = $tagAttributes->getValueAndRemoveIfPresent(PageTemplateName::PROPERTY_NAME);
        if ($layout !== null) {
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

        try {
            $this->fetcherCache->addFileDependency($this->pageTemplate->getCssPath());
        } catch (ExceptionNotFound $e) {
            // no css file
        }
        try {
            $this->fetcherCache->addFileDependency($this->pageTemplate->getJsPath());
        } catch (ExceptionNotFound $e) {
            // no js
        }
        // mandatory, should not throw
        try {
            $cache = $this->fetcherCache->addFileDependency($this->pageTemplate->getHtmlTemplatePath());
        } catch (ExceptionNotFound $e) {
            //throw ExceptionRuntimeInternal::withMessageAndError("The html template should be found", $e);
            $cache = null;
        }


        /**
         * Public static cache
         * (Do we create the page or return the cache)
         */
//        if ($cache->isCacheUsable() && $this->isPublicStaticPage()) {
//            try {
//                return FileSystems::getContent($cache->getFile());
//            } catch (ExceptionNotFound $e) {
//                // the cache file should exists
//                LogUtility::internalError("The cache HTML fragment file was not found", self::NAME);
//            }
//        }

        /**
         * Generate the whole html page via the layout
         */
        $htmlDocumentString = $this->pageTemplate->render();

        /**
         * We store only the public pages
         */
        if ($this->isPublicStaticPage() && $cache !== null) {
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

        $layoutName = $this->getRequestedTemplateOrDefault();
        $this->pageTemplate = PageTemplate::create()
            ->setRequestedTemplateName($layoutName)
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
        return
            SiteConfig::getConfValue(FetcherRailBar::CONF_PRIVATE_RAIL_BAR, 0) === 1
            && !Identity::isLoggedIn()
            && !$this->pageTemplate->hasMessages();
    }

    private function getRequestedTemplateOrDefault(): string
    {
        try {
            return $this->getRequestedLayout();
        } catch (ExceptionNotFound $e) {
            return PageTemplateName::createFromPage($this->getRequestedPage())->getValueOrDefault();
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
