<?php


namespace ComboStrap;


use dokuwiki\Cache\CacheRenderer;

abstract class OutputDocument extends PageCompilerDocument
{


    /**
     * @var CacheRenderer
     */
    protected $cache;

    /**
     * @var Path Cache/Output file
     */
    private $path;

    /**
     *
     * OutputDocument constructor.
     * @var Page $page
     */
    public function __construct(Page $page)
    {
        parent::__construct($page);

        if ($page->isStrapSideSlot()) {

            /**
             * Logical cache based on scope (ie logical id) is the scope and part of the key
             */
            $this->cache = new CacheByLogicalKey($page, $this->getExtension());

        } else {

            $path = $page->getPath();
            $id = $path->getDokuwikiId();

            /**
             * The local path is part of the key cache and should be the same
             * than dokuwiki
             *
             * For whatever reason, Dokuwiki uses:
             *   * `/` as separator on Windows
             *   * and Windows short path `GERARD~1` not gerardnico
             * See {@link wikiFN()}
             * There is also a cache in the function
             *
             * We can't use our {@link Path} class because the
             * path is on windows format without the short path format
             */
            $localFile = wikiFN($id);
            $this->cache = new CacheRenderer($id, $localFile, $this->getExtension());

        }

        $this->path = LocalPath::createFromPath($this->cache->cache);

    }

    function process()
    {

        if (!$this->getPage()->exists()) {
            return "";
        }

        if (
            !$this->shouldProcess()
            && FileSystems::exists($this->getCachePath())
            && PluginUtility::isDevOrTest()
        ) {
            LogUtility::msg("The file ({$this->getExtension()}) should not compile and exists, compilation is not needed", LogUtility::LVL_MSG_ERROR);
        }

        /**
         * Global ID is the ID of the HTTP request
         * (ie the page id)
         * We change it for the run
         * And restore it at the end
         */
        global $ID;
        $keep = $ID;
        $ID = $this->getPage()->getPath()->getDokuwikiId();

        /**
         * The code below is adapted from {@link p_cached_output()}
         * $ret = p_cached_output($file, 'xhtml', $pageid);
         */
        $instructions = $this->getPage()->getInstructionsDocument()->getOrProcessContent();

        /**
         * Due to the instructions parsing, they may have been changed
         * by a component
         */
        $logicalId = $this->getPage()->getLogicalId();
        $scope = $this->getPage()->getScope();

        /**
         * Render
         */
        $result = p_render($this->getRendererName(), $instructions, $info);

        // restore ID
        $ID = $keep;

        /**
         * Store
         * if the cache is not on, don't store
         */
        $enabledCache = $info['cache'];
        if ($enabledCache && $this->cache->storeCache($result)) {
            if (
                (Site::debugIsOn() || PluginUtility::isDevOrTest())
                && $this->getExtension() === HtmlDocument::extension
            ) {
                $result = "<div id=\"{$this->getPage()->getCacheHtmlId()}\" style=\"display:none;\" data-logical-Id=\"$logicalId\" data-scope=\"$scope\" data-cache-op=\"created\" data-cache-file=\"{$this->getCachePath()->toAbsolutePath()->toString()}\"></div>" . $result;
            }
        } else {
            $this->cache->removeCache(); // try to delete cachefile
            if (
                (Site::debugIsOn() || PluginUtility::isDevOrTest())
                && $this->getExtension() === HtmlDocument::extension
            ) {
                $result = "<div id=\"{$this->getPage()->getCacheHtmlId()}\" style=\"display:none;\" data-logical-Id=\"$logicalId\" data-scope=\"$scope\" data-cache-op=\"forbidden\"></div>" . $result;
            }
        }

        return $result;

    }

    public function getCachePath(): Path
    {
        return $this->path;
    }

    public function shouldProcess(): bool
    {
        if (!FileSystems::exists($this->getCachePath())) {
            return true;
        }
        return $this->cache->useCache() === false;
    }


}
