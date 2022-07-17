<?php

namespace ComboStrap;

use syntax_plugin_combo_heading;

/**
 * Bundle page from the same namespace
 * with {@link FetcherPageBundler::getBundledOutline() corrected outline}
 *
 * From a wiki app, just add: `?do=combo_pagebundler`
 *
 */
class FetcherPageBundler extends IFetcherAbs implements IFetcherString
{

    use FetcherTraitWikiPath;

    const CANONICAL = self::NAME;
    const NAME = "pagebundler";
    private Outline $bundledOutline;

    public static function createPageBundler(): FetcherPageBundler
    {
        return new FetcherPageBundler();
    }

    public function buildFromUrl(Url $url): FetcherPageBundler
    {
        /**
         * Just to return the good type
         */
        parent::buildFromUrl($url);
        return $this;
    }

    /**
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     * @throws ExceptionNotFound
     */
    public function buildFromTagAttributes(TagAttributes $tagAttributes): FetcherPageBundler
    {
        parent::buildFromTagAttributes($tagAttributes);
        $this->buildOriginalPathFromTagAttributes($tagAttributes);
        return $this;
    }


    function getBuster(): string
    {
        return "";
    }

    /**
     * @return Mime
     */
    public function getMime(): Mime
    {
        return Mime::getHtml();
    }

    public function getFetcherName(): string
    {
        return self::NAME;
    }

    public function getFetchString(): string
    {

        $outline = $this->getBundledOutline();
        $instructionsCalls = $outline->toHtmlSectionOutlineCalls();
        $mainContent = MarkupRenderer::createFromInstructions($instructionsCalls)
            ->setRequestedMime($this->getMime())
            ->getOutput();


        $startMarkup = $this->getStartPath();
        $title = PageTitle::createForMarkup($startMarkup)->getValueOrDefault();
        $lang = Lang::createForMarkup($startMarkup);
        try {
            $startMarkupWikiPath = WikiPath::createFromPathObject($startMarkup->getPathObject());
        } catch (ExceptionBadArgument $e) {
            /**
             * should not happen as this class accepts only wiki path as {@link FetcherPageBundler::setContextPath() context path}
             */
            throw new ExceptionRuntimeInternal("We were unable to get the start markup wiki path. Error:{$e->getMessage()}", self::CANONICAL);
        }

        $layoutName = PageLayout::BLANK_LAYOUT;
        try {
            $toc = Toc::createEmpty()
                ->setValue($this->getBundledOutline()->getTocDokuwikiFormat());
        } catch (ExceptionBadArgument $e) {
            // this is an array
            throw new ExceptionRuntimeInternal("The toc could not be created. Error:{$e->getMessage()}", self::CANONICAL, 1, $e);
        }
        try {

            return PageLayout::createFromLayoutName($layoutName)
                ->setRequestedContextPath($startMarkupWikiPath)
                ->setRequestedTitle($title)
                ->setRequestedLang($lang)
                ->setToc($toc)
                ->setDeleteSocialHeadTags(true)
                ->setRequestedEnableTaskRunner(false)
                ->generateAndGetPageHtmlAsString($mainContent);
        } catch (ExceptionBadSyntax|ExceptionNotFound $e) {
            // layout should be good
            throw new ExceptionRuntimeInternal("The $layoutName template returns an error", self::CANONICAL, 1, $e);
        }


    }

    public function getBundledOutline(): Outline
    {

        if (isset($this->bundledOutline)) {
            return $this->bundledOutline;
        }

        $startPath = $this->getStartPath();
        if (FileSystems::exists($startPath)) {
            $indexOutline = $startPath->getOutline();
        } else {
            $title = PageTitle::createForMarkup($startPath)->getValueOrDefault();
            $content = <<<EOF
====== $title ======
EOF;
            $indexOutline = Outline::createFromMarkup($content);
        }

        $childrenPages = MarkupFileSystem::getOrCreate()->getChildren($startPath, FileSystems::LEAF);
        foreach ($childrenPages as $child) {
            $outer = $child->getOutline();
            Outline::merge($indexOutline, $outer);
        }
        $this->bundledOutline = $indexOutline;

        return $this->bundledOutline;

    }

    /**
     * The path from where the bundle should start
     * If this is not an index markup, the index markup will be chosen {@link FetcherPageBundler::getStartPath()}
     *
     * @throws ExceptionBadArgument - if the path is not a {@link WikiPath web path}
     */
    public function setContextPath(Path $requestedPath): FetcherPageBundler
    {
        $this->setSourcePath(WikiPath::createFromPathObject($requestedPath));
        return $this;
    }

    private function getRequestedContextPath(): WikiPath
    {
        return $this->getSourcePath();
    }


    /**
     *
     * @return MarkupPath The index path or the request path is none
     *
     */
    private function getStartPath(): MarkupPath
    {
        $requestedPath = MarkupPath::createPageFromPathObject($this->getRequestedContextPath());
        if ($requestedPath->isIndexPage()) {
            return $requestedPath;
        }
        try {
            /**
             * Parent is an index path in the {@link MarkupFileSystem}
             */
            return $requestedPath->getParent();
        } catch (ExceptionNotFound $e) {
            // home markup case (should not happen - home page is a index page)
            return $requestedPath;
        }

    }

}
