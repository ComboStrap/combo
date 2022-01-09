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

    public function getPagesForMedia(Media $media): array
    {
        $dokuwikiId = $media->getPath()->getDokuwikiId();
        return $this->indexer->lookupKey('relation_media', $dokuwikiId);
    }

    /**
     * Return a list of page id that have the same last name
     *
     * @param string $pageIdSource
     * @return array
     */
    public function getPagesWithSameLastName(string $pageIdSource): array
    {
        /**
         * * A shortcut to:
         * ```
         * $pagesWithSameName = ft_pageLookup($pageName);
         * ```
         * where {@link ft_pageLookup()}
         */

        // There is two much pages with the start name
        $name = noNS($pageIdSource);
        if ($name === Site::getHomePageName()) {
            return [];
        }

        $pages = $this->indexer->getPages();

        $matchesPages = [];
        foreach ($pages as $pageId) {
            if ($pageIdSource === $pageId) {
                continue;
            }
            $page = DokuPath::createPagePathFromId($pageId);
            if ($page->getLastName() === $name) {
                $matchesPages[] = $pageId;
            }
        }
        return $matchesPages;

    }

    public function deletePage(Page $page)
    {

        $this->indexer->deletePage($page->getDokuwikiId());

    }
}
