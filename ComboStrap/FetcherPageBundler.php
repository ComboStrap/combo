<?php

namespace ComboStrap;

class FetcherPageBundler extends IFetcherAbs implements IFetcherString
{


    private WikiPath $requestedPath;

    public static function createPageBundler(): FetcherPageBundler
    {
        return new FetcherPageBundler();
    }

    function getBuster(): string
    {
        return "";
    }

    public function getMime(): Mime
    {
        return Mime::getHtml();
    }

    public function getFetcherName(): string
    {
        return "page-bundler";
    }

    public function getFetchString(): string
    {

        $outline = $this->getOutline();
        $instructionsCalls = $outline->toHtmlSectionOutlineCalls();
        $mainContent = MarkupRenderer::createFromInstructions($instructionsCalls)
            ->setRequestedMime($this->getMime())
            ->getOutput();

        $htmlHeadTags = HtmlHeadTags::create()
            ->get();

        $title = PageTitle::createForPage(Markup::createPageFromPathObject($this->requestedPath))
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

    public function getOutline(): Outline
    {

        $requestedPage = Markup::createPageFromPathObject($this->requestedPath);
        $outline = $requestedPage->getOutline();

        $childrenPages = MarkupFileSystem::getOrCreate()->getChildren($requestedPage, FileSystems::LEAF);
        foreach ($childrenPages as $child) {
            Outline::merge($outline, $child->getOutline());
        }
        return $outline;
    }

    public function setRequestedNamespace(WikiPath $namespaceRoot): FetcherPageBundler
    {
        $this->requestedPath = $namespaceRoot;
        return $this;
    }
}
