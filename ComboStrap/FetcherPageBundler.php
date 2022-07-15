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

        $htmlHeadTags = HtmlHeadTags::create()
            ->get();

        $title = PageTitle::createForPage(Markup::createPageFromPathObject($this->getSourcePath()))
            ->getValueOrDefault();

        /**
         * Html
         */
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<title>$title</title>
$htmlHeadTags
</head>
<body>
$mainContent
</body>
</html>
HTML;

    }

    public function getBundledOutline(): Outline
    {

        if (isset($this->bundledOutline)) {
            return $this->bundledOutline;
        }

        $requestedPage = Markup::createPageFromPathObject($this->getContextPath());
        if (!$requestedPage->isIndexPage()) {
            try {
                $indexPage = $requestedPage->getParent();
            } catch (ExceptionNotFound $e) {
                // home page case (should not happen - home page is a index page)
                $indexPage = $requestedPage;
            }
        } else {
            $indexPage = $requestedPage;
        }
        if (FileSystems::exists($indexPage)) {
            $indexOutline = $this->addTitleSectionIfMissing($indexPage->getOutline());
        } else {
            $title = PageTitle::createForPage($indexPage)->getValueOrDefault();
            $content = <<<EOF
====== $title ======
EOF;
            $indexOutline = Outline::createFromMarkup($content);
        }

        $childrenPages = MarkupFileSystem::getOrCreate()->getChildren($indexPage, FileSystems::LEAF);
        foreach ($childrenPages as $child) {
            $outer = $this->addTitleSectionIfMissing($child->getOutline());
            Outline::merge($indexOutline, $outer);
        }
        $this->bundledOutline = $indexOutline;

        return $this->bundledOutline;

    }

    /**
     * @throws ExceptionBadArgument - if the path is not a {@link WikiPath web path}
     */
    public function setContextPath(Path $requestedPath): FetcherPageBundler
    {
        $this->setSourcePath(WikiPath::createFromPathObject($requestedPath));
        return $this;
    }

    private function getContextPath(): WikiPath
    {
        return $this->getSourcePath();
    }

    /**
     * If there is a header markup, the first section with the title
     * are deleted, we recreate the title section here
     * @param Outline $outline
     * @return Outline
     */
    private function addTitleSectionIfMissing(Outline $outline): Outline
    {
        $rootOutlineSection = $outline->getRootOutlineSection();
        try {
            $firstChild = $rootOutlineSection->getFirstChild();
        } catch (ExceptionNotFound $e) {
            if (PluginUtility::isDevOrTest()) {
                LogUtility::warning("No first child was found while checking the title section", self::CANONICAL);
            }
            return $outline;
        }
        if ($firstChild->getLevel() >= 2) {
            $enterHeading = Call::createComboCall(
                \syntax_plugin_combo_heading::TAG,
                DOKU_LEXER_ENTER,
                array(syntax_plugin_combo_heading::LEVEL => 1),
                syntax_plugin_combo_heading::TYPE_OUTLINE,
                null,
                null,
                0
            );
            $title = PageTitle::createForPage($outline->getMarkup())->getValueOrDefault();
            $unmatchedHeading = Call::createComboCall(
                \syntax_plugin_combo_heading::TAG,
                DOKU_LEXER_UNMATCHED,
                [],
                null,
                $title,
                $title
            );
            $exitHeading = Call::createComboCall(
                \syntax_plugin_combo_heading::TAG,
                DOKU_LEXER_EXIT,
                array(syntax_plugin_combo_heading::LEVEL => 1)
            );
            $h1Section = OutlineSection::createFromEnterHeadingCall($enterHeading)
                ->addHeadingCall($unmatchedHeading)
                ->addHeadingCall($exitHeading);
            $children = $rootOutlineSection->getChildren();
            foreach ($children as $child) {
                $child->detachBeforeAppend();
                try {
                    $h1Section->appendChild($child);
                } catch (ExceptionBadState $e) {
                    LogUtility::error("An error occurs when trying to move the h2 children below the recreated heading title ($title)", self::CANONICAL);
                }
            }
            try {
                $rootOutlineSection->appendChild($h1Section);
            } catch (ExceptionBadState $e) {
                LogUtility::error("An error occurs when trying to add the recreated title heading ($title) to the root", self::CANONICAL);
            }
        }
        return $outline;
    }

}
