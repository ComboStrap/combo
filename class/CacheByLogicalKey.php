<?php


namespace ComboStrap;

/**
 * Class BarCache
 * @package ComboStrap
 *
 * This class resolve the following problem.
 *
 * An improvement request was done at:
 * See:
 * https://github.com/splitbrain/dokuwiki/issues/3496
 *
 * *The problem**
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
 **Proposed solution**
 *
 * Can we have a logical key (ie the id) instead of the physical one (the file) ?
 *
 * ```
 * parent::__construct($id . $_SERVER['HTTP_HOST'] . $_SERVER['SERVER_PORT'], '.' . $mode);
 * ```
 *
 * It would then be possible to pass a different id (logical id):
 * - for the first request  `:bar:sidebar`
 * - for the second: `:foo:sidebar`
 *
 * and they would not share the same cache.
 */
class CacheByLogicalKey extends \dokuwiki\Cache\Cache
{
    public $logicalPagePath;
    public $file;
    public $mode;

    /**
     * To be compatible with
     * {@link action_plugin_move_rewrite::handle_cache()} line 88
     * that expect the $page with the id
     */
    public $page;

    /**
     * BarCache constructor.
     *
     *
     *
     * @param $logicalPagePath - logical absolute path
     * @param $file - file used
     * @param string $mode
     */
    public function __construct($logicalPagePath, $file, $mode)
    {

        $this->logicalPagePath = $logicalPagePath;
        $this->file = $file;
        $this->mode = $mode;

        $cacheKey = $logicalPagePath . $_SERVER['HTTP_HOST'] . $_SERVER['SERVER_PORT'];
        $this->setEvent('PARSER_CACHE_USE');
        $ext = '.' . $mode;


        $this->page = substr($logicalPagePath,1);

        parent::__construct($cacheKey, $ext);

    }

    protected function addDependencies()
    {

        /**
         * Configuration
         * File when they are touched the cache should be stale
         */
        $files = getConfigFiles('main');
        /**
         * The original file
         */
        $files[] = $this->file;

        /**
         * Update the dependency
         */
        $this->depends = ["files" => $files];

        parent::addDependencies();

    }


}
