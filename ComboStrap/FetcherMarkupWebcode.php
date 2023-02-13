<?php

namespace ComboStrap;


use action_plugin_combo_css;

/**
 *
 * This code permits to render a markup from a string passed as argument
 *
 * Technically, it's the same than {@link FetcherMarkup}
 * but:
 *   * it outputs the HTML within a minimal HTML page (no layout as in {@link FetcherPage})
 *   * it gets the input from the url query properties
 *
 * It's used primarily by {@link \syntax_plugin_combo_webcode}
 * that's why it's called webcode.
 *
 */
class FetcherMarkupWebcode extends IFetcherAbs implements IFetcherString
{

    const CANONICAL = "webcode";
    const NAME = "markup";

    public const MARKUP_PROPERTY = "markup";
    const TITLE_PROPERTY = "title";

    private string $requestedMarkup;
    private string $requestedTitle = "ComboStrap WebCode - Markup Renderer";

    public static function createFetcherMarkup(string $markup): FetcherMarkupWebcode
    {
        return (new FetcherMarkupWebcode())
            ->setRequestedMarkup($markup);
    }

    /**
     * @throws ExceptionBadState - the markup is mandatory
     */
    function getFetchUrl(Url $url = null): Url
    {
        $url = parent::getFetchUrl($url);
        $url->addQueryParameter(self::MARKUP_PROPERTY, $this->getRequestedMarkup());
        $url->addQueryParameter(self::TITLE_PROPERTY, $this->getRequestedTitle());
        return $url;
    }


    function getBuster(): string
    {
        try {
            return FileSystems::getCacheBuster(ClassUtility::getClassPath(FetcherMarkupWebcode::class));
        } catch (ExceptionNotFound|\ReflectionException $e) {
            LogUtility::internalError("The cache buster should be good. Error:{$e->getMessage()}", self::NAME);
            return "";
        }
    }

    public function buildFromTagAttributes(TagAttributes $tagAttributes): IFetcher
    {

        $markupProperty = self::MARKUP_PROPERTY;
        $markup = $tagAttributes->getValueAndRemove($markupProperty);
        if ($markup === null) {
            throw new ExceptionBadArgument("The markup property ($markupProperty) is mandatory");
        }
        $this->setRequestedMarkup($markup);
        $title = $tagAttributes->getValueAndRemove(self::TITLE_PROPERTY);
        if ($title !== null) {
            $this->setRequestedTitle($title);
        }
        return parent::buildFromTagAttributes($tagAttributes);
    }


    public function getMime(): Mime
    {
        return Mime::getHtml();
    }

    public function getFetcherName(): string
    {
        return self::NAME;
    }

    /**
     * @throws ExceptionBadState - if the markup was not defined
     */
    public function getFetchString(): string
    {


        /**
         * Conf
         */
        Site::setConf(action_plugin_combo_css::CONF_DISABLE_DOKUWIKI_STYLESHEET, true);

        $fetcherCache = FetcherCache::createFrom($this);
        if ($fetcherCache->isCacheUsable()) {
            try {
                return FileSystems::getContent($fetcherCache->getFile());
            } catch (ExceptionNotFound $e) {
                $message = "The cache file should exists";
                if (PluginUtility::isDevOrTest()) {
                    throw new ExceptionRuntimeInternal($message);
                }
                LogUtility::internalError($message);
            }
        }

        $requestedMarkup = $this->getRequestedMarkup();

        $mainContent = MarkupRenderer::createFromMarkup($requestedMarkup, null, null)
            ->setDeleteRootBlockElement(true)
            ->setRendererName("xhtml")
            ->setRequestedMimeToXhtml()
            ->getOutput();

        $title = $this->getRequestedTitle();

        try {
            $html = PageTemplate::createFromLayoutName(PageLayoutName::BLANK_LAYOUT)
                ->setRequestedTitle($title)
                ->setRequestedEnableTaskRunner(false)
                ->generateAndGetPageHtmlAsString($mainContent);
        } catch (ExceptionBadSyntax|ExceptionNotFound|ExceptionBadArgument $e) {
            throw new ExceptionRuntimeInternal("An error has occurred while creating the HTML page. Error: {$e->getMessage()}", self::CANONICAL, 1, $e);
        }

        $fetcherCache->storeCache($html);
        return $html;

    }

    public function setRequestedMarkup(string $markup): FetcherMarkupWebcode
    {
        $this->requestedMarkup = $markup;
        return $this;

    }

    public function setRequestedTitle(string $title): FetcherMarkupWebcode
    {
        $this->requestedTitle = $title;
        return $this;
    }

    /**
     * @throws ExceptionBadState
     */
    private function getRequestedMarkup(): string
    {
        if (!isset($this->requestedMarkup)) {
            throw new ExceptionBadState("The markup was not defined.", self::CANONICAL);
        }
        return $this->requestedMarkup;
    }

    private function getRequestedTitle(): string
    {
        return $this->requestedTitle;
    }


    public function getLabel(): string
    {
        return self::CANONICAL;
    }

}
