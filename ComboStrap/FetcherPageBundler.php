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
        return "";
    }

    public function getOutline(): Outline
    {

        $requestedPage = PageFragment::createPageFromPathObject($this->requestedPath);
        $outline = $requestedPage->getOutline();

        $childrenPages = PageFileSystem::getOrCreate()->getChildren($requestedPage, FileSystems::LEAF);
        foreach ($childrenPages as $child) {
            Outline::merge($outline,$child->getOutline());
        }
        return $outline;
    }

    public function setRequestedNamespace(WikiPath $namespaceRoot): FetcherPageBundler
    {
        $this->requestedPath = $namespaceRoot;
        return $this;
    }
}
