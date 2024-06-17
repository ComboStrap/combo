<?php

namespace ComboStrap;


use ComboStrap\Meta\Field\PageTemplateName;
use ComboStrap\Web\Url;

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
    private ?Outline $bundledOutline = null;
    /**
     * @var int - the maximum number of pages to bundle
     * Security to not get DDOS by a Search engine
     */
    private int $maxPages = 5;
    /**
     * @var int the number of pages processed (ie actually added to the outline)
     */
    private int $countPageProcessed = 0;

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
            ->setRequestedExecutingPath($this->getStartPath())
            ->setRequestedContextPath($this->getRequestedContextPath())
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

        $layoutName = PageTemplateName::BLANK_TEMPLATE_VALUE;
        try {
            $toc = Toc::createEmpty()
                ->setValue($this->getBundledOutline()->toTocDokuwikiFormat());
        } catch (ExceptionBadArgument $e) {
            // this is an array
            throw new ExceptionRuntimeInternal("The toc could not be created. Error:{$e->getMessage()}", self::CANONICAL, 1, $e);
        }
        try {
            return TemplateForWebPage::create()
                ->setRequestedTemplateName($layoutName)
                ->setRequestedContextPath($startMarkupWikiPath)
                ->setRequestedTitle($title)
                ->setRequestedLang($lang)
                ->setToc($toc)
                ->setIsSocial(false)
                ->setRequestedEnableTaskRunner(false)
                ->setMainContent($mainContent)
                ->render();
        } catch (ExceptionBadSyntax|ExceptionNotFound|ExceptionBadArgument $e) {
            // layout should be good
            throw new ExceptionRuntimeInternal("The $layoutName template returns an error", self::CANONICAL, 1, $e);
        }


    }

    public function getBundledOutline(): Outline
    {

        if (isset($this->bundledOutline)) {
            return $this->bundledOutline;
        }

        if (!Identity::isAnonymous()) {
            $this->maxPages = 99999;
            set_time_limit(5 * 60);
        }
        $startPath = $this->getStartPath();
        $actualLevel = 0;
        $this->buildOutlineRecursive($startPath, $actualLevel);

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

    /**
     * If a page does not have any h1
     * (Case of index page for instance)
     *
     * If this is the case, the outline is broken.
     * @param Outline $outline
     * @return Outline
     */
    private function addFirstSectionIfMissing(Outline $outline): Outline
    {
        $rootOutlineSection = $outline->getRootOutlineSection();
        $addFirstSection = false;
        try {
            $firstChild = $rootOutlineSection->getFirstChild();
            if ($firstChild->getLevel() >= 2) {
                $addFirstSection = true;
            }
        } catch (ExceptionNotFound $e) {
            $addFirstSection = true;
        }
        if ($addFirstSection) {
            $enterHeading = Call::createComboCall(
                HeadingTag::HEADING_TAG,
                DOKU_LEXER_ENTER,
                array(HeadingTag::LEVEL => 1),
                HeadingTag::TYPE_OUTLINE,
                null,
                null,
                null,
                \syntax_plugin_combo_xmlblocktag::TAG
            );
            $title = PageTitle::createForMarkup($outline->getMarkupPath())->getValueOrDefault();
            $unmatchedHeading = Call::createComboCall(
                HeadingTag::HEADING_TAG,
                DOKU_LEXER_UNMATCHED,
                [],
                null,
                $title,
                $title,
                null,
                \syntax_plugin_combo_xmlblocktag::TAG
            );
            $exitHeading = Call::createComboCall(
                HeadingTag::HEADING_TAG,
                DOKU_LEXER_EXIT,
                array(HeadingTag::LEVEL => 1),
                null,
                null,
                null,
                null,
                \syntax_plugin_combo_xmlblocktag::TAG
            );
            $h1Section = OutlineSection::createFromEnterHeadingCall($enterHeading)
                ->addHeaderCall($unmatchedHeading)
                ->addHeaderCall($exitHeading);
            $children = $rootOutlineSection->getChildren();
            foreach ($children as $child) {
                $child->detachBeforeAppend();
                try {
                    $h1Section->appendChild($child);
                } catch (ExceptionBadState $e) {
                    LogUtility::error("An error occurs when trying to move the h2 children below the recreated heading title ($title)", self::CANONICAL);
                }
            }
            /**
             * Without h1
             * The content is in the root heading
             */
            foreach ($rootOutlineSection->getContentCalls() as $rootHeadingCall) {
                $h1Section->addContentCall($rootHeadingCall);
            }
            $rootOutlineSection->deleteContentCalls();
            try {
                $rootOutlineSection->appendChild($h1Section);
            } catch (ExceptionBadState $e) {
                LogUtility::error("An error occurs when trying to add the recreated title heading ($title) to the root", self::CANONICAL);
            }
        }
        return $outline;
    }

    public function getLabel(): string
    {
        return self::CANONICAL;
    }

    private function buildOutlineRecursive(MarkupPath $indexPath, int $actualLevel)
    {
        /**
         * Index Page
         */
        if (FileSystems::exists($indexPath)) {
            $outline = FetcherMarkup::confRoot()
                ->setRequestedExecutingPath($indexPath)
                ->setRequestedContextPath($indexPath->toWikiPath())
                ->setRequestedMimeToInstructions()
                ->build()
                ->getOutline();
            $indexOutline = $this->addFirstSectionIfMissing($outline);
        } else {
            $title = PageTitle::createForMarkup($indexPath)->getValueOrDefault();
            $content = <<<EOF
====== $title ======
EOF;
            $indexOutline = Outline::createFromMarkup($content, $indexPath, $this->getRequestedContextPath());
            $indexOutline = $this->addFirstSectionIfMissing($indexOutline);
        }

        /**
         * Start of bundled outline or not
         */
        if ($this->bundledOutline === null) {
            $this->bundledOutline = $indexOutline;
        } else {
            Outline::merge($this->bundledOutline, $indexOutline, $actualLevel);
        }
        $this->countPageProcessed = +1;
        if ($this->countPageProcessed > $this->maxPages) {
            return;
        }

        /**
         * Children Pages (Same level)
         */
        $childrenPages = MarkupFileSystem::getOrCreate()->getChildren($indexPath, FileSystems::LEAF);
        foreach ($childrenPages as $child) {
            if ($child->isSlot()) {
                continue;
            }
            try {
                $outline = FetcherMarkup::confRoot()
                    ->setRequestedExecutingPath($child)
                    ->setRequestedContextPath($child->toWikiPath())
                    ->setRequestedMimeToInstructions()
                    ->build()
                    ->getOutline();
            } catch (ExceptionNotExists $e) {
                // as it's in a file system loop, the page should exist
                continue;
            }
            $outer = $this->addFirstSectionIfMissing($outline);
            Outline::merge($this->bundledOutline, $outer, $actualLevel);
            $this->countPageProcessed = +1;
            if ($this->countPageProcessed > $this->maxPages) {
                return;
            }
        }
        $containerPages = MarkupFileSystem::getOrCreate()->getChildren($indexPath, FileSystems::CONTAINER);
        $nextLevel = $actualLevel + 1;
        foreach ($containerPages as $child) {
            $this->buildOutlineRecursive($child, $nextLevel);
        }

    }
}
