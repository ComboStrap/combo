<?php

namespace ComboStrap;


use ComboStrap\Meta\Field\PageTemplateName;
use ComboStrap\Web\Url;
use ComboStrap\Web\UrlEndpoint;
use ComboStrap\Xml\XmlDocument;

class FetcherPage extends IFetcherAbs implements IFetcherSource, IFetcherString
{

    use FetcherTraitWikiPath;

    const NAME = "page";
    const CANONICAL = "page";
    const PURGE = "purge";

    private string $requestedLayout;
    private bool $build = false;
    private bool $closed = false;


    private MarkupPath $requestedMarkupPath;
    private string $requestedLayoutName;
    private TemplateForWebPage $pageTemplate;
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
            $template = $this->getRequestedTemplate();
            $url->addQueryParameter(PageTemplateName::PROPERTY_NAME, $template);
        } catch (ExceptionNotFound $e) {
            // ok
        }

        $this->addLocalPathParametersToFetchUrl($url, DokuwikiId::DOKUWIKI_ID_ATTRIBUTE);

        // the drive is not needed
        $url->deleteQueryParameter(WikiPath::DRIVE_ATTRIBUTE);

        // this is the default fetcher, no need to add it as parameter
        $url->deleteQueryParameter(IFetcher::FETCHER_KEY);

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
        /**
         * Purge the cache
         */
        $tagAttributes->getValueAndRemoveIfPresent(self::PURGE);
        return $this;
    }


    public static function createPageFetcherFromMarkupPath(MarkupPath $markupPath): FetcherPage
    {
        return self::createPageFetcherFromPath($markupPath->getPathObject());
    }


    /**
     * @return string
     */
    public function getFetchString(): string
    {

        $this->buildObjectIfNeeded();

        $cache = $this->fetcherCache;

        /**
         * Public static cache
         * (Do we create the page or return the cache)
         */
        $isPublicStaticPage = $this->isPublicStaticPage();
        $isCacheUsable = $cache->isCacheUsable();
        if ($isCacheUsable && $isPublicStaticPage) {
            try {
                return FileSystems::getContent($cache->getFile());
            } catch (ExceptionNotFound $e) {
                // the cache file should exists
                LogUtility::internalError("The cache HTML fragment file was not found", self::NAME);
            }
        }

        /**
         * Generate the whole html page via the layout
         */
        $htmlDocumentString = $this->pageTemplate->render();

        /**
         * We store only the static public pages
         * without messages (they are dynamically insert)
         */
        $hasMessages = $this->pageTemplate->hasMessages();
        if ($isPublicStaticPage && !$hasMessages) {
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

        $this->build = true;

        $this->requestedMarkupPath = MarkupPath::createPageFromPathObject($this->getRequestedPath());

        $pageLang = Lang::createForMarkup($this->getRequestedPage());
        $title = PageTitle::createForMarkup($this->getRequestedPage())->getValueOrDefault();

        $layoutName = $this->getRequestedTemplateOrDefault();
        $this->pageTemplate = TemplateForWebPage::create()
            ->setRequestedTemplateName($layoutName)
            ->setRequestedContextPath($this->getRequestedPath())
            ->setRequestedLang($pageLang)
            ->setRequestedTitle($title);

        /**
         * Build the cache
         * The template is mandatory as cache key
         * If the user change it, the cache should be disabled
         */
        $this->fetcherCache = FetcherCache::createFrom($this, [$layoutName]);
        // the requested page
        $this->fetcherCache->addFileDependency($this->getRequestedPath());
        if (PluginUtility::isDevOrTest()) {
            /**
             * The hbs template dependency
             * (only on test/dev)
             */
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
                $this->fetcherCache->addFileDependency($this->pageTemplate->getHtmlTemplatePath());
            } catch (ExceptionNotFound $e) {
                LogUtility::internalError("The html template should be found", self::CANONICAL, $e);
            }
        }
        /**
         * The Slots of the requested template
         */
        foreach ($this->pageTemplate->getSlots() as $templateSlot) {
            try {
                $this->fetcherCache->addFileDependency($templateSlot->getMarkupFetcher()->getSourcePath());
            } catch (ExceptionNotFound $e) {
                // no slot page found
            }
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
    private function getRequestedTemplate(): string
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
        $privateRailbar = SiteConfig::getConfValue(FetcherRailBar::CONF_PRIVATE_RAIL_BAR, 0);
        $isLoggedIn = Identity::isLoggedIn();
        return $privateRailbar === 1 && !$isLoggedIn;
    }

    private function getRequestedTemplateOrDefault(): string
    {
        try {
            return $this->getRequestedTemplate();
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
