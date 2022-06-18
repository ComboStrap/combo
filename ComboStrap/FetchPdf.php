<?php

namespace ComboStrap;

class FetchPdf extends FetchRaw
{

    /**
     * We support also query parameter after the anchor
     * This is something a little bit weird but yeah.
     * Documentation here:
     * https://helpx.adobe.com/acrobat/kb/link-html-pdf-page-acrobat.html
     *
     * Open a PDF file to a specific page
     * add #page=[page number] to the end of the link's URL.
     *
     * For example, this HTML tag opens page 4 of a PDF file named myfile.pdf:
     * <A HREF="http://www.example.com/myfile.pdf#page=4">
     */
    private ?int $pageNumber = null;

    public function buildFromUrl(Url $url): FetchRaw
    {
        try {
            $fragment = $url->getFragment();
            $fragments = explode($fragment, "=");
            if ($fragments[0] === "page" && sizeof($fragments) >= 2) {
                try {
                    $this->pageNumber = DataType::toInteger($fragments[1]);
                } catch (ExceptionBadArgument $e) {
                    throw new ExceptionBadArgument("The pdf page number anchor seems to not be a number. Error: {$e->getMessage()}");
                }
            }
        } catch (ExceptionNotFound $e) {
            // ok no page
        }
        return parent::buildFromUrl($url);
    }

    public function getMime(): Mime
    {
        return Mime::create(Mime::PDF);
    }

    function getFetchUrl(Url $url = null): Url
    {
        $url = parent::getFetchUrl($url);
        if ($this->pageNumber !== null) {
            $url->setFragment("page={$this->pageNumber}");
        }
        return $url;
    }

    /**
     * @throws ExceptionNotFound
     */
    function getPageNumber(): int
    {
        if ($this->pageNumber === null) {
            throw new ExceptionNotFound("No page number");
        }
        return $this->pageNumber;
    }

}
