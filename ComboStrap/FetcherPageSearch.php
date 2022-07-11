<?php

namespace ComboStrap;

class FetcherPageSearch extends IFetcherAbs implements IFetcherString
{

    const NAME = "page-search";

    private string $requestedSearchTerms;

    /**
     * No cache
     * @return string
     */
    function getBuster(): string
    {
        return time();
    }

    public function getMime(): Mime
    {
        return Mime::getJson();
    }

    public function getFetcherName(): string
    {
        return self::NAME;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getFetchString(): string
    {
        $requestedSearchTerms = $this->getRequestedQuery();

        if (empty($requestedSearchTerms)) return "";


        /**
         * Ter info: Old call: how dokuwiki call it.
         * It's then executing the SEARCH_QUERY_PAGELOOKUP event
         *
         * $inTitle = useHeading('navigation');
         * $pages = ft_pageLookup($query, true, $inTitle);
         */
        $pages = Search::getPages($requestedSearchTerms);
        $maxElements = 50;
        if (count($pages) > $maxElements) {
            array_splice($pages, 0, $maxElements);
        }

        $data = [];
        foreach ($pages as $page) {
            if (!$page->exists()) {
                $page->getDatabasePage()->delete();
                LogUtility::log2file("The page ($page) returned from the search query does not exist and was deleted from the database");
                continue;
            }
            $linkUtility = LinkMarkup::createFromPageIdOrPath($page->getWikiId());
            try {
                $html = $linkUtility->toAttributes()->toHtmlEnterTag("a") . $page->getTitleOrDefault() . "</a>";
            } catch (ExceptionCompile $e) {
                $html = "Unable to render the link for the page ($page). Error: {$e->getMessage()}";
            }
            $data[] = $html;
        }
        $count = count($data);
        if (!$count) {
            throw new ExceptionNotFound("No pages found");
        }
        return json_encode($data);

    }

    /**
     * @throws ExceptionNotFound
     */
    private function getRequestedQuery()
    {
        if (!isset($this->requestedSearchTerms)) {
            throw new ExceptionNotFound("No search terms were requested");
        }
        return $this->requestedSearchTerms;
    }

    public function buildFromTagAttributes(TagAttributes $tagAttributes): IFetcher
    {
        $searchTerms = $tagAttributes->getValueAndRemoveIfPresent("q");
        if ($searchTerms !== null) {
            $this->requestedSearchTerms = $searchTerms;
        }
        return parent::buildFromTagAttributes($tagAttributes);
    }


}
