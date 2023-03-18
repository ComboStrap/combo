<?php

namespace ComboStrap;

use ComboStrap\Web\Url;

class FetcherPdf extends FetcherRawLocalPath
{


    private ?int $pageNumber = null;


    public function buildFromUrl(Url $url): FetcherPdf
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
        parent::buildFromUrl($url);
        return $this;
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
