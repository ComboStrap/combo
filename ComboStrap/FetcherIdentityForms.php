<?php

namespace ComboStrap;


/**
 * No Cache for the idenity forms
 * as if there is a cache problems,
 * We can't login anymore for instance
 */
class FetcherIdentityForms extends IFetcherAbs implements IFetcherString
{

    const NAME = "identity";
    const CANONICAL = "identity";

    use FetcherTraitWikiPath;

    private string $requestedLayout;
    private bool $build = false;


    private PageTemplate $pageLayout;


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

            $this->build = true;
            $pageLang = Site::getLangObject();
            $title = $this->getLabel();

            try {
                $this->pageLayout = PageTemplate::createFromLayoutName($this->getRequestedLayoutOrDefault())
                    ->setRequestedLang($pageLang)
                    ->setRequestedEnableTaskRunner(false) // no page id
                    ->setRequestedTitle($title)
                    ->setRequestedContextPath($this->getSourcePath());
            } catch (ExceptionBadSyntax|ExceptionNotFound $e) {
                throw new ExceptionRuntimeInternal("Layout error: {$e->getMessage()}", self::NAME, 1, $e);
            }

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
                    $fetcherPageFragment->processIfNeededAndGetFetchPath();
                } finally {
                    $fetcherPageFragment->close();
                }
            } catch (ExceptionNotFound $e) {
                // no markup for this slot
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
        return $this->pageLayout->generateAndGetPageHtmlAsString($mainHtml);

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
        global $ACT;
        $label = "Identity forms";
        switch ($ACT){
            case ExecutionContext::RESEND_PWD_ACTION:
                $label = "Resend Password";
                break;
            case ExecutionContext::LOGIN_ACTION:
                $label = "Login";
                break;
            case ExecutionContext::REGISTER_ACTION:
                $label = "Register";
                break;
        }
        return $label;
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
}
