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

    public function getPagesForMedia(WikiPath $media): array
    {
        $dokuwikiId = $media->getWikiId();
        return $this->indexer->lookupKey('relation_media', $dokuwikiId);
    }

    /**
     * Return a list of page id that have the same last name
     *
     * @param MarkupPath $pageToMatch
     * @return MarkupPath[]
     */
    public function getPagesWithSameLastName(MarkupPath $pageToMatch): array
    {
        /**
         * * A shortcut to:
         * ```
         * $pagesWithSameName = ft_pageLookup($pageName);
         * ```
         * where {@link ft_pageLookup()}
         */

        try {
            $lastNameToFind = $pageToMatch->getPathObject()->getLastNameWithoutExtension();
            $wikiIdToFind = $pageToMatch->getWikiId();
        } catch (ExceptionNotFound|ExceptionBadArgument $e) {
            return [];
        }

        // There is two much pages with the start name
        if ($lastNameToFind === Site::getIndexPageName()) {
            return [];
        }

        $pageIdList = $this->indexer->getPages();

        $matchedPages = [];
        foreach ($pageIdList as $pageId) {
            if ($wikiIdToFind === $pageId) {
                // don't return the page to find in the result
                continue;
            }
            $actualPage = WikiPath::createMarkupPathFromId($pageId);
            try {
                if ($actualPage->getLastNameWithoutExtension() === $lastNameToFind) {
                    $matchedPages[] = MarkupPath::createPageFromPathObject($actualPage);
                }
            } catch (ExceptionNotFound $e) {
                // root, should not happen
            }
        }
        return $matchedPages;

    }

    public function deletePage(MarkupPath $page)
    {

        $this->indexer->deletePage($page->getWikiId());

    }
}
