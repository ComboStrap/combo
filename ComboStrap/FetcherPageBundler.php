<?php

namespace ComboStrap;

class FetcherPageBundler extends IFetcherAbs implements IFetcherString
{

    use FetcherTraitWikiPath;

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
        return "pagebundler";
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
            $indexOutline = $indexPage->getOutline();
        } else {
            $title = PageTitle::createForPage($indexPage)->getValueOrDefault();
            $content = <<<EOF
====== $title ======
EOF;
            $indexOutline = Outline::createFromMarkup($content);
        }

        $childrenPages = MarkupFileSystem::getOrCreate()->getChildren($indexPage, FileSystems::LEAF);
        foreach ($childrenPages as $child) {
            Outline::merge($indexOutline, $child->getOutline());
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

}
