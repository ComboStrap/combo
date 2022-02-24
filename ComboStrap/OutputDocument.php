<?php


namespace ComboStrap;


use dokuwiki\Cache\CacheRenderer;

abstract class OutputDocument extends PageCompilerDocument
{


    /**
     * @var CacheRenderer cache file
     */
    protected $cache;


    /**
     * @var mixed
     */
    private $cacheStillEnabledAfterRendering;


    /**
     *
     * OutputDocument constructor.
     * @var Page $page
     */
    public function __construct(Page $page)
    {
        parent::__construct($page);


        /**
         * Variables
         */
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

    /**
     * @return OutputDocument
     * @noinspection PhpMissingReturnTypeInspection
     */
    function process()
    {

        if (!$this->getPage()->exists()) {
            return $this;
        }

        if (
            !$this->shouldProcess()
            && FileSystems::exists($this->getCachePath())
            && PluginUtility::isDevOrTest()
        ) {
            throw new ExceptionComboRuntime("The file ({$this->getExtension()}) should not compile and exists already, compilation is not needed", LogUtility::LVL_MSG_ERROR);
        }

        /**
         * Global ID is the ID of the HTTP request
         * (ie the page id)
         * We change it for the run
         * And restore it at the end
         */
        global $ID;
        $keep = $ID;
        try {
            $ID = $this->getPage()->getPath()->getDokuwikiId();

            /**
             * The code below is adapted from {@link p_cached_output()}
             * $ret = p_cached_output($file, 'xhtml', $pageid);
             */
            $instructions = $this->getPage()->getInstructionsDocument()->getOrProcessContent();


            /**
             * Render
             */
            $result = p_render($this->getRendererName(), $instructions, $info);
            $this->cacheStillEnabledAfterRendering = $info['cache'];


        } finally {
            // restore ID
            $ID = $keep;
        }

        /**
         * Set document should also know the requested page id
         * to be able to calculate the cache output directory
         */
        $this->setContent($result);
        return $this;

    }

    /**
     * @throws ExceptionCombo
     */
    public function storeContent($content)
    {

        /**
         * Store
         * if the cache is not on, don't store
         */
        if ($this->cacheStillEnabledAfterRendering) {

            /**
             * Reroute the cache output by runtime dependencies
             */
            $cacheRuntimeDependencies = CacheManager::getOrCreate()->getCacheDependenciesForSlot($this->page->getDokuwikiId());
            $cacheRuntimeDependencies->rerouteCacheDestination($this->cache);

            /**
             * Store
             */
            $this->cache->storeCache($content);
        } else {
            $this->cache->removeCache(); // try to delete cache file
        }
        return $this;
    }


    public function getCachePath(): LocalPath
    {
        $path = $this->cache->cache;
        return LocalPath::createFromPath($path);
    }

    public function shouldProcess(): bool
    {

        /**
         * The cache is stored by requested
         * page scope
         *
         * We set the id because it's not passed
         * in all actions and is needed to log the cache
         * result
         */
        global $ID;
        $keep = $ID;
        $ID = $this->getPage()->getPath()->getDokuwikiId();
        try {
            /**
             * Use cache should be always called because it trigger
             * the event coupled to the cache (ie PARSER_CACHE_USE)
             */
            return ($this->cache->useCache() === false);
        } finally {
            $ID = $keep;
        }


    }

    public function __toString()
    {
        return $this->getPage()->getDokuwikiId() . "." . $this->getExtension();
    }


}
