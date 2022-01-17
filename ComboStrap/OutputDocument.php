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

        if ($page->isSlot()) {

            /**
             * Logical cache based on scope (ie logical id) is the scope and part of the key
             */
            $this->cache = new CacheByLogicalKey($page, $this->getExtension());

        } else {

            /**
             * We follow Dokuwiki Cache
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
         * Render
         */
        $result = p_render($this->getRendererName(), $instructions, $info);
        $this->cacheStillEnabledAfterRendering = $info['cache'];

        // restore ID
        $ID = $keep;



        $this->setContent($result);
        return $this;

    }

    public function storeContent($content)
    {
        /**
         * Store
         * if the cache is not on, don't store
         */
        if ($this->cacheStillEnabledAfterRendering) {
            $this->cache->storeCache($content);
        } else {
            $this->cache->removeCache(); // try to delete cachefile
        }
        return $this;
    }


    public function getCachePath(): Path
    {
        $path = $this->cache->cache;
        return LocalPath::createFromPath($path);
    }

    public function shouldProcess(): bool
    {

        /**
         * The cache is stored by requested
         * page scope
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


}
