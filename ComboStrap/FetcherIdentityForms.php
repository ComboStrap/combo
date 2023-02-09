<?php

namespace ComboStrap;


class FetcherIdentityForms extends IFetcherAbs implements IFetcherString
{

    const NAME = "login";
    const CANONICAL = "login";


    private string $requestedLayout;
    private bool $build = false;


    private PageLayout $pageLayout;
    private FetcherCache $fetcherCache;


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
        try {
            $url->addQueryParameter(PageLayoutName::PROPERTY_NAME, $this->getRequestedLayout());
        } catch (ExceptionNotFound $e) {
            // no requested layout
        }
        return parent::getFetchUrl($url)
            ->addQueryParameter("do", self::NAME);
    }


    /**
     * @return string
     * @throws ExceptionNotFound - if the main markup fragment could not be found
     * @throws ExceptionBadArgument - if the main markup fragment path can not be transformed as wiki path
     */
    public function getFetchString(): string
    {

        if (!$this->build) {

            $this->fetcherCache = FetcherCache::createFrom($this);
            $this->build = true;
            $pageLang = Site::getLangObject();
            $title = $this->getLabel();
            try {
                $this->pageLayout = PageLayout::createFromLayoutName($this->getRequestedLayoutOrDefault())
                    ->setRequestedLang($pageLang)
                    ->setRequestedEnableTaskRunner(false) // no page id
                    ->setRequestedTitle($title);
            } catch (ExceptionBadSyntax|ExceptionNotFound $e) {
                throw new ExceptionRuntimeInternal("Layout error: {$e->getMessage()}", self::NAME, 1, $e);
            }

        }

        $cache = $this->fetcherCache
            ->addFileDependency($this->pageLayout->getCssPath())
            ->addFileDependency($this->pageLayout->getJsPath())
            ->addFileDependency($this->pageLayout->getHtmlTemplatePath());


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
                    $cache->addFileDependency($fetcherPageFragment->processIfNeededAndGetFetchPath());
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
        if (false && $cache->isCacheUsable() && $this->isPublicAction()) {
            try {
                return FileSystems::getContent($cache->getFile());
            } catch (ExceptionNotFound $e) {
                // the cache file should exists
                LogUtility::internalError("The cache HTML fragment file was not found", self::NAME);
            }
        }

        /**
         * The content
         *
         * Adapted from {@link tpl_content()}
         *
         * As this is only for identifier forms,
         * the buffer should not be a problem
         *
         * Because all admin action are using the php buffer
         * We can then have an overflow
         */
        ob_start();
        global $ACT;
        \dokuwiki\Extension\Event::createAndTrigger('TPL_ACT_RENDER', $ACT, 'tpl_content_core');
        $mainHtml = ob_get_clean();


        /**
         * Generate the whole html page via the layout
         */
        $htmlDocumentString = $this->pageLayout->generateAndGetPageHtmlAsString($mainHtml);

        /**
         * We store only the public pages
         */
        if ($this->isPublicAction()) {
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


    public function setRequestedLayout(string $layoutValue): FetcherIdentityForms
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


    /**
     *
     */
    public function close(): FetcherIdentityForms
    {
        // nothing to do
        return $this;
    }


    /**
     * Can we cache the output
     *
     * @return bool
     */
    private function isPublicAction(): bool
    {
        return self::NAME === ExecutionContext::LOGIN_ACTION;
    }

    private function getRequestedLayoutOrDefault(): string
    {
        try {
            return $this->getRequestedLayout();
        } catch (ExceptionNotFound $e) {
            return PageLayoutName::MEDIAN_LAYOUT_VALUE;
        }
    }


    public function getLabel(): string
    {
        return ucfirst($this->getAction());
    }

    private function getAction(): string
    {
        return self::NAME;
    }
}
