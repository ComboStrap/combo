<?php


namespace ComboStrap;


use dokuwiki\Cache\CacheInstructions;
use dokuwiki\Cache\CacheParser;

class InstructionsDocument extends PageCompilerDocument
{

    private $path;

    /**
     * @var CacheInstructions
     */
    private $cache;

    /**
     * @var CacheParser
     */
    private $dependenciesCacheStore;
    /**
     * @var CacheDependencies
     */
    private $cacheDependencies;
    /**
     * @var string
     */
    private $initialCacheKey;


    /**
     * InstructionsDocument constructor.
     * @throws ExceptionCombo
     * @var Page $page
     */
    public function __construct(Page $page)
    {
        parent::__construct($page);


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
        $this->cache = new CacheInstructions($id, $localFile);

        /**
         * Cache Key
         */
        $this->cacheDependencies = $this->getStoredCacheDependencies();
        $this->initialCacheKey = $this->cache->key;
        $this->refreshInstructionsCacheKeyAndPath();

    }

    /**
     * @return CacheDependencies
     */
    public function getStoredCacheDependencies(): CacheDependencies
    {
        $data = $this->getDependenciesCacheStore()->retrieveCache();
        $cacheDependencies = CacheDependencies::create($this->getPage());
        if (!empty($data)) {
            $deps = json_decode($data, true);
            foreach ($deps as $dep=>$function) {
                $cacheDependencies->addDependency($dep,$function);
            }
        }
        return $cacheDependencies;

    }

    /**
     * @throws ExceptionCombo
     */
    private function storeDependencies()
    {

        try {

            /**
             * Runtime cache dependencies
             */
            $slotId = $this->getPage()->getDokuwikiId();
            $this->cacheDependencies = CacheManager::getOrCreate()->getRuntimeCacheDependenciesForSlot($slotId);
            $deps = $this->cacheDependencies->getDependencies();

            /**
             * Cache file
             * Using a cache parser, set the page id and will trigger
             * the parser cache use event in order to log/report the cache usage
             * At {@link action_plugin_combo_cache::logCacheUsage()}
             */
            $dependencies = $this->getDependenciesCacheStore();
            if ($deps !== null) {
                $jsonDeps = json_encode($deps);
                $dependencies->storeCache($jsonDeps);
            } else {
                $dependencies->removeCache();
            }

        } finally {

            $this->refreshInstructionsCacheKeyAndPath();

        }

    }

    function getExtension(): string
    {
        return "i";
    }

    function process(): CachedDocument
    {

        if (!$this->shouldProcess()) {
            return $this;
        }

        /**
         * The id is not passed while on handler
         * Therefore the global id should be set
         */
        global $ID;
        $oldId = $ID;
        $ID = $this->getPage()->getPath()->getDokuwikiId();

        /**
         * Get the instructions
         * Adapted from {@link p_cached_instructions()}
         */
        try {
            $text = $this->getPage()->getTextContent();
            $instructions = p_get_instructions($text);
        } finally {
            // close restore ID
            $ID = $oldId;
        }

        if (!$this->cache->storeCache($instructions)) {
            $message = 'Unable to save the parsed instructions cache file. Hint: disk full; file permissions; safe_mode setting ?';
            LogUtility::msg($message, LogUtility::LVL_MSG_ERROR);
            $this->setContent([]);
            return $this;
        }

        // the parsing may have set new metadata values
        $this->getPage()->rebuild();

        $this->setContent($instructions);
        return $this;

    }


    public function getFileContent()
    {
        /**
         * The data is {@link serialize serialized} for instructions
         * we can't use the parent method that retrieve text by default
         */
        return $this->cache->retrieveCache();

    }


    function getRendererName(): string
    {
        return "i";
    }

    public function getCachePath(): Path
    {
        return $this->path;
    }

    public function shouldProcess(): bool
    {

        global $ID;
        $keep = $ID;
        try {
            $ID = $this->getPage()->getDokuwikiId();
            return $this->cache->useCache() === false;
        } finally {
            $ID = $keep;
        }
    }


    /**
     * @throws ExceptionCombo
     */
    public function storeContent($content)
    {
        /**
         * Save the dependencies
         */
        $this->storeDependencies();

        /**
         * Refresh the cache key
         */
        $this->cache->cache = $this->cacheDependencies->getCacheFile($this->cache);
        $this->cache->storeCache($content);
        return $this;
    }

    private function getDependenciesCacheStore(): CacheParser
    {
        if ($this->dependenciesCacheStore !== null) {
            return $this->dependenciesCacheStore;
        }
        $id = $this->getPage()->getDokuwikiId();
        $slotLocalFilePath = $this->getPage()
            ->getPath()
            ->toLocalPath()
            ->toAbsolutePath()
            ->toString();
        $this->dependenciesCacheStore = new CacheParser($id, $slotLocalFilePath, "deps.json");
        return $this->dependenciesCacheStore;
    }

    /**
     * @throws ExceptionCombo
     */
    private function refreshInstructionsCacheKeyAndPath()
    {

        $this->cache->key = $this->cacheDependencies->getOrCalculateDependencyKey($this->initialCacheKey);
        $this->cache->cache = $this->cacheDependencies->getCacheFile($this->cache);
        $this->path = LocalPath::createFromPath($this->cache->cache);
    }


}
