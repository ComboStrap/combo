<?php


namespace ComboStrap;


class Index
{
    /**
     * @var Index
     */
    private static $index;

    /**
     * @var \dokuwiki\Search\Indexer
     */
    private $indexer;


    /**
     * Index constructor.
     */
    public function __construct()
    {
        $this->indexer = idx_get_indexer();
    }

    public static function getOrCreate(): Index
    {
        if (self::$index === null) {
            self::$index = new Index();
        }
        return self::$index;
    }

    public function getPagesForMedia(DokuPath $media): array
    {
        $dokuwikiId = $media->getWikiId();
        return $this->indexer->lookupKey('relation_media', $dokuwikiId);
    }

    /**
     * Return a list of page id that have the same last name
     *
     * @param PageFragment $pageToMatch
     * @return PageFragment[]
     */
    public function getPagesWithSameLastName(PageFragment $pageToMatch): array
    {
        /**
         * * A shortcut to:
         * ```
         * $pagesWithSameName = ft_pageLookup($pageName);
         * ```
         * where {@link ft_pageLookup()}
         */

        // There is two much pages with the start name
        $lastName = $pageToMatch->getPath()->getLastName();
        if ($lastName === Site::getIndexPageName()) {
            return [];
        }

        $pageIdList = $this->indexer->getPages();

        $matchedPages = [];
        foreach ($pageIdList as $pageId) {
            if ($pageToMatch->getDokuwikiId() === $pageId) {
                continue;
            }
            $actualPage = PageFragment::createPageFromId($pageId);
            if ($actualPage->getPath()->getLastName() === $lastName) {
                $matchedPages[] = $actualPage;
            }
        }
        return $matchedPages;

    }

    public function deletePage(PageFragment $page)
    {

        $this->indexer->deletePage($page->getDokuwikiId());

    }
}
