<?php


namespace ComboStrap;

use dokuwiki\Cache\CacheParser;

/**
 *
 * @package ComboStrap
 *
 * A cache parser wrappers that modifies the key
 *
 * https://github.com/splitbrain/dokuwiki/issues/3496
 *
 * *Because**
 *
 * Most of the [sidebar](https://www.dokuwiki.org/faq:sidebar) requires to disallow the cache with the tag `~~CACHE~~` to keep the content relevant.
 * *Why ?**
 * - The output of a sidebar is related to the context of the requested page. (ie `$ID`). They contains mostly navigational plugin (for instance, showing the list of pages for the parent namespace of the requested page.)
 * - The sidebar functionality permits to have one `sidebar` page physical file at the root for all pages of the website (see the `page_findnearest` function at [pl_include_page](https://github.com/splitbrain/dokuwiki/blob/master/inc/template.php#L1570)
 * - The cache is stored at the physical level with the key using the file path. See [CacheParse](https://github.com/splitbrain/dokuwiki/blob/master/inc/Cache/CacheParser.php#L30)
 * ```
 * parent::__construct($file . $_SERVER['HTTP_HOST'] . $_SERVER['SERVER_PORT'], '.' . $mode);
 * ```
 *
 * This three points makes that:
 * - for a `:sidebar` at the root of the website listing the pages of the current namespace
 * - the first requested page (for instance, `:bar:page`) would see a sidebar with all pages of the namespace `bar`
 * - the second requested page (for instance `:foo:page`) would hit the cache and would show all pages of the `bar` namespace and not `foo`
 *
 *
 */
class CacheByLogicalKey extends CacheParser
{


    /**
     * @var Page $pageObject The page object
     */
    private $pageObject;

    /**
     * BarCache constructor.
     *
     *
     *
     * @param Page $pageObject - the page
     * @param string $mode
     */
    public function __construct($pageObject, $mode)
    {

        $this->pageObject = $pageObject;

        parent::__construct($pageObject->getDokuwikiId(), $this->pageObject->getLogicalId(), $mode);

        /**
         * The cache parser constructor takes the logical id as the file
         * we overwrite it
         *
         * The source (added as dependency at {@link CacheParser::addDependencies()}
         */
        $this->file = $pageObject->getPath()->toLocalPath()->toAbsolutePath()->toString();

    }

    protected function addDependencies()
    {
        parent::addDependencies();
    }

    public function storeCache($data): bool
    {

        /**
         * The logical id depends on the
         * scope that can be set when the page is parsed
         * (ie therefore after that the cache object is created
         * if the cache does not exist)
         *
         * The logic is get the cache, if it does not exist
         * parse it, then store the cache. If the cache does not exist,
         * the logical id is not yet known
         *
         * We change the cache location file
         * before storing
         */
        $this->cache = $this->getCacheFile();
        return io_saveFile($this->cache, $data);

    }

    private function getCacheKey(): string
    {
        return $this->pageObject->getLogicalId() . $_SERVER['HTTP_HOST'] . $_SERVER['SERVER_PORT'];
    }

    protected function getCacheFile(): string
    {
        return getCacheName($this->getCacheKey(), $this->getExt());
    }

    private function getExt(): string
    {
        return '.' . $this->mode;
    }

    public function makeDefaultCacheDecision(): bool
    {
        return parent::makeDefaultCacheDecision();
    }


}
