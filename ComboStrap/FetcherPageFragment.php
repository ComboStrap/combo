<?php


namespace ComboStrap;


use dokuwiki\Cache\CacheInstructions;
use dokuwiki\Cache\CacheParser;
use dokuwiki\Cache\CacheRenderer;
use http\Exception\RuntimeException;

class FetcherPageFragment extends FetcherAbs implements FetcherSource
{
    const XHTML_MODE = "xhtml";
    const INSTRUCTION_EXTENSION = "i";

    /**
     * @var CacheRenderer cache file
     */
    protected CacheParser $cache;

    /**
     * @var CacheParser
     */
    private $snippetCache;

    private CacheDependencies $cacheDependencies;
    private PageFragment $pageFragment;
    private Mime $mime;
    private bool $cacheAfterRendering = true;
    private string $renderer;


    public static function createPageFragmentFetcherFromObject(PageFragment $pageFragment): FetcherPageFragment
    {
        return (new FetcherPageFragment())
            ->setRequestedPageFragment($pageFragment);
    }

    public function setRequestedPageFragment(PageFragment $pageFragment): FetcherPageFragment
    {

        $this->pageFragment = $pageFragment;
        $this->buildCacheObject();


        return $this;

    }

    public function getMime(): Mime
    {
        if (isset($this->mime)) {
            return $this->mime;
        }

        // XHTML default
        try {
            return Mime::createFromExtension(self::XHTML_MODE);
        } catch (ExceptionNotFound $e) {
            // should not happen
            throw new RuntimeException("Internal error: The mime was not found");
        }
    }

    /**
     * @return string
     * @deprecated for {@link PageFragment::getMime()}
     */
    function getExtension(): string
    {
        return self::XHTML_MODE;
    }


    function getRendererName(): string
    {
        if (isset($this->renderer)) {
            return $this->renderer;
        }
        return "xhtml";
    }

    public function shouldProcess(): bool
    {

        if ($this->getCacheTime() > 0) {
            return true;
        }

        /**
         * The cache is stored by requested
         * page scope
         *
         * We set the id because it's not passed
         * in all actions and is needed to log the cache
         * result
         */
        global $ID;
        $keepID = $ID;
        $ID = $this->getRequestedPageFragment()->getPath()->getWikiId();
        global $ACT;
        $keepAct = $ACT;
        if ($ACT === null) {
            /**
             * ACT is the usage of the parsed instructions
             * and is needed for {@link LayoutMainAreaBuilder::shouldMainAreaBeBuild()}
             */
            $ACT = "show";
        }
        try {

            /**
             * Use cache should be always called because it trigger
             * the event coupled to the cache (ie PARSER_CACHE_USE)
             */
            $depends = $this->getDepends();
            return ($this->cache->useCache($depends) === false);
        } finally {
            $ID = $keepID;
            $ACT = $keepAct;
        }


    }


    public
    function storeSnippets()
    {

        $slotId = $this->getRequestedPageFragment()->getDokuwikiId();

        /**
         * Snippet
         */
        $snippetManager = PluginUtility::getSnippetManager();
        $jsonDecodeSnippets = $snippetManager->getJsonArrayFromSlotSnippets($slotId);

        /**
         * Cache file
         * Using a cache parser, set the page id and will trigger
         * the parser cache use event in order to log/report the cache usage
         * At {@link action_plugin_combo_cache::createCacheReport()}
         */
        $snippetCache = $this->getSnippetCacheStore();


        if ($jsonDecodeSnippets !== null) {
            $data1 = json_encode($jsonDecodeSnippets);
            $snippetCache->storeCache($data1);
        } else {
            $snippetCache->removeCache();
        }

    }

    /**
     * @return Snippet[]
     */
    public
    function loadSnippets(): array
    {
        $data = $this->getSnippetCacheStore()->retrieveCache();
        $nativeSnippets = [];
        if (!empty($data)) {
            $jsonDecodeSnippets = json_decode($data, true);
            foreach ($jsonDecodeSnippets as $snippet) {
                try {
                    $nativeSnippets[] = Snippet::createFromJson($snippet);
                } catch (ExceptionCompile $e) {
                    LogUtility::msg("The snippet json array cannot be build into a snippet object. " . $e->getMessage());
                }

            }
        }
        return $nativeSnippets;

    }

    private
    function removeSnippets()
    {
        $snippetCacheFile = $this->getSnippetCacheStore()->cache;
        if ($snippetCacheFile !== null) {
            if (file_exists($snippetCacheFile)) {
                unlink($snippetCacheFile);
            }
        }
    }

    /**
     * Cache file
     * Using a cache parser, set the page id and will trigger
     * the parser cache use event in order to log/report the cache usage
     * At {@link action_plugin_combo_cache::createCacheReport()}
     */
    public
    function getSnippetCacheStore(): CacheParser
    {
        if ($this->snippetCache !== null) {
            return $this->snippetCache;
        }
        $id = $this->getRequestedPageFragment()->getDokuwikiId();
        $slotLocalFilePath = $this->getRequestedPageFragment()
            ->getPath()
            ->toLocalPath()
            ->toAbsolutePath()
            ->toPathString();
        $this->snippetCache = new CacheParser($id, $slotLocalFilePath, "snippet.json");
        /**
         * Snippet.json is data dependent
         *
         * For instance, the carrousel may add glide or grid as snippet. It depends on the the number of backlinks.
         *
         * Therefore the output should be unique by rendered slot
         * Therefore we reroute (recalculate the cache key to the same than the html file)
         */
        $this->cacheDependencies->rerouteCacheDestination($this->snippetCache);
        return $this->snippetCache;
    }

    public
    function getDependencies(): CacheDependencies
    {
        return $this->cacheDependencies;
    }

    public
    function getCacheDependencies(): CacheDependencies
    {
        return $this->cacheDependencies;
    }

    public
    function getDependenciesCacheStore(): CacheParser
    {
        return $this->cacheDependencies->getDependenciesCacheStore();
    }

    /**
     * @throws ExceptionNotFound
     */
    function getFetchPath(): Path
    {

        $fetchPath = $this->getCachePath();

        if (!$this->shouldProcess()) {
            return $fetchPath;
        }

        /**
         * Snippets if XHTML
         */
        if ($this->getMime()->getExtension() === self::XHTML_MODE) {
            /** We make the Snippet store to Html store an atomic operation
             *
             * Why ? Because if the rendering of the page is stopped,
             * the cache of the HTML page may be stored but not the cache of the snippets
             * leading to a bad page because the next rendering will see then no snippets.
             */
            try {
                $this->storeSnippets();
            } catch (\Exception $e) {
                // if any write os exception
                LogUtility::msg("Error while storing the xhtml content: {$e->getMessage()}");
                $this->removeSnippets();
            }

            /**
             * Cache output dependencies
             */
            $this->cacheDependencies->storeDependencies();

        }

        /**
         * Process
         */
        $extension = $this->getMime()->getExtension();
        switch ($extension) {
            case self::INSTRUCTION_EXTENSION:
                $content = $this->processInstruction();
                break;
            default:
                $content = $this->process();
                /**
                 * Reroute the cache output by runtime dependencies
                 * set during processing
                 */
                $this->cacheDependencies->rerouteCacheDestination($this->cache);
        }


        /**
         * We store always the output in the cache
         * if the cache is not on, the file is just overwritten
         */
        $this->cache->storeCache($content);

        return $fetchPath;

    }

    function getBuster(): string
    {
        // no buster
        return "";
    }

    public
    function getFetcherName(): string
    {
        return "page-fragment";
    }

    private function getRequestedPageFragment(): PageFragment
    {
        return $this->pageFragment;
    }

    private function getDepends(): array
    {
        $depends = [];
        foreach ($this->getRequestedPageFragment()->getChildren() as $child) {
            $depends['files'][] = $child->getPath()->toLocalPath()->toPathString();
        }
        return $depends;
    }

    /**
     */
    public function setRequestedMime(Mime $mime): FetcherPageFragment
    {
        $this->mime = $mime;
        $this->buildCacheObject();
        return $this;
    }

    public function setRequestedFormatAsXhtml(): FetcherPageFragment
    {
        try {
            return $this->setRequestedMime(Mime::createFromExtension("xhtml"));
        } catch (ExceptionNotFound $e) {
            throw new RuntimeException("Internal error", 0, $e);
        }

    }

    private function getCacheTime(): int
    {

        $extension = $this->getMime()->getExtension();
        switch ($extension) {
            case self::XHTML_MODE:
                if (!Site::isHtmlRenderCacheOn()) {
                    return 0;
                }
                break;
            case self::INSTRUCTION_EXTENSION:
                return 999999999;
        }

        return $this->cacheAfterRendering ? 1 : 0;

    }

    /**
     * @return string
     * @throws ExceptionNotFound
     */
    function process(): string
    {

        if (!$this->getRequestedPageFragment()->exists()) {
            return "";
        }

        if (
            !$this->shouldProcess()
            && FileSystems::exists($this->getFetchPath())
            && PluginUtility::isDevOrTest()
        ) {
            throw new ExceptionRuntime("The fetcher ({$this}) should not compile and exists already, compilation is not needed", LogUtility::LVL_MSG_ERROR);
        }

        /**
         * Global ID is the ID of the HTTP request
         * (ie the page id)
         * We change it for the run
         * And restore it at the end
         */
        global $ID;
        $keep = $ID;
        global $ACT;
        $keepACT = $ACT;
        try {

            $ID = $this->getRequestedPageFragment()->getPath()->getWikiId();
            $ACT = "show";

            /**
             * The code below is adapted from {@link p_cached_output()}
             * $ret = p_cached_output($file, 'xhtml', $pageid);
             */
            $instructions = FetcherPageFragment::createPageFragmentFetcherFromObject($this->getRequestedPageFragment())
                ->setRequestedMimeToInstructions()
                ->getFetchPathAsInstructionsArray();

            /**
             * Render
             */
            $result = p_render($this->getRendererName(), $instructions, $info);
            $this->cacheAfterRendering = $info['cache'];


        } finally {
            // restore
            $ID = $keep;
            $ACT = $keepACT;
        }

        return $result;

    }

    private function buildCacheObject()
    {

        $wikiId = $this->pageFragment->getPath()->getWikiId();

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
         * We can't use our {@link Path} class to be compatible because the
         * path is on windows format without the short path format
         */
        $localFile = wikiFN($wikiId);

        /**
         * Cache by extension (ie type)
         */
        $extension = $this->getMime()->getExtension();
        switch ($extension) {
            case self::INSTRUCTION_EXTENSION:
                $this->cache = new CacheInstructions($wikiId, $localFile);
                break;
            default:
                $this->cache = new CacheRenderer($wikiId, $localFile, $extension);
                /**
                 * Modifying the cache key and the corresponding output file
                 * from runtime dependencies
                 */
                $this->cacheDependencies = CacheManager::getOrCreateFromRequestedPage()->getCacheDependenciesForSlot($wikiId);
                $this->cacheDependencies->rerouteCacheDestination($this->cache);
                break;
        }


    }

    public function __toString()
    {
        return $this->getRequestedPageFragment() . $this->getMime()->toString();
    }

    private function processInstruction(): array
    {
        /**
         * The id is not passed while on handler
         * Therefore the global id should be set
         */
        global $ID;
        $oldId = $ID;
        $ID = $this->getRequestedPageFragment()->getPath()->getWikiId();

        /**
         * Get the instructions
         * Adapted from {@link p_cached_instructions()}
         *
         * Note that this code may not run at first rendering
         *
         * Why ?
         * Because dokuwiki asks first page information
         * via the {@link pageinfo()} method.
         * This function then render the metadata (ie {@link p_render_metadata()} and therefore will trigger
         * the rendering with this function
         * ```p_cached_instructions(wikiFN($id),false,$id)```
         *
         * The best way to manipulate the instructions is not before but after
         * the parsing. See {@link \action_plugin_combo_headingpostprocessing}
         *
         */
        $path = $this->getRequestedPageFragment()->getPath();
        try {
            $text = FileSystems::getContent($path);
            $instructions = p_get_instructions($text);
        } catch (ExceptionNotFound $e) {
            LogUtility::msg("The file ($path) does not exists, call stack instructions was set to empty");
            $instructions = [];
        } finally {
            // close restore ID
            $ID = $oldId;
        }

        // the parsing may have set new metadata values
        $this->getRequestedPageFragment()->rebuild();
        return $instructions;

    }


    public function getOriginalPath(): DokuPath
    {
        return $this->pageFragment->getPath();
    }

    public function setRequestedMimeToInstructions(): FetcherPageFragment
    {
        try {
            $this->setRequestedMime(Mime::createFromExtension(self::INSTRUCTION_EXTENSION));
        } catch (ExceptionNotFound $e) {
            throw new RuntimeException("Internal error: the mime is internal and should be good");
        }
        return $this;

    }

    /**
     * Utility function that returns the fetch path as instructions array
     * (ie un-serialized)
     * @throws ExceptionNotFound
     */
    public function getFetchPathAsInstructionsArray()
    {
        $contents = FileSystems::getContent($this->getFetchPath());
        return !empty($contents) ? unserialize($contents) : array();
    }

    public function setRendererName(string $rendererName): FetcherPageFragment
    {
        $this->renderer = $rendererName;
        return $this;
    }

    public function getCachePath(): LocalPath
    {
        $path = $this->cache->cache;
        return LocalPath::createFromPath($path);
    }

}
