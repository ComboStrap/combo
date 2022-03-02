<?php


namespace ComboStrap;


use dokuwiki\Cache\CacheParser;

class HtmlDocument extends OutputDocument
{
    const mode = "xhtml";
    /**
     * @var CacheParser
     */
    private $snippetCache;
    /**
     * @var CacheDependencies
     */
    private $cacheDependencies;


    /**
     * HtmlDocument constructor.
     * @param Page $page
     */
    public function __construct(Page $page)
    {
        parent::__construct($page);

        /**
         * Modifying the cache key and the corresponding output file
         * from runtime dependencies
         */
        $this->cacheDependencies = CacheManager::getOrCreate()->getCacheDependenciesForSlot($page->getDokuwikiId());
        $this->cacheDependencies->rerouteCacheDestination($this->cache);

    }


    function getExtension(): string
    {
        return self::mode;
    }


    function getRendererName(): string
    {
        return self::mode;
    }

    public function shouldProcess(): bool
    {
        if (!Site::isHtmlRenderCacheOn()) {
            return true;
        }
        return parent::shouldProcess();
    }

    /**
     */
    public function getOrProcessContent(): ?string
    {


        if ($this->shouldProcess()) {
            $this->process();
        }
        return $this->getContent();


    }


    /**
     * Html document is stored
     */
    public function storeContent($content)
    {


        /** We make the Snippet store to Html store an atomic operation
         *
         * Why ? Because if the rendering of the page is stopped,
         * the cache of the HTML page may be stored but not the cache of the snippets
         * leading to a bad page because the next rendering will see then no snippets.
         */
        $this->storeSnippets();

        /**
         * Cache output dependencies
         */
        $this->cacheDependencies->storeDependencies();
        $this->cacheDependencies->rerouteCacheDestination($this->cache);

        try {
            return parent::storeContent($content);
        } catch (\Exception $e) {
            // if any write os exception
            LogUtility::msg("Deleting the snippets, Error while storing the xhtml content: {$e->getMessage()}");
            $this->removeSnippets();
            return $this;
        }
    }

    public function storeSnippets()
    {

        $slotId = $this->getPage()->getDokuwikiId();

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
                } catch (ExceptionCombo $e) {
                    LogUtility::msg("The snippet json array cannot be build into a snippet object. " . $e->getMessage());
                }

            }
        }
        return $nativeSnippets;

    }

    private function removeSnippets()
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
    public function getSnippetCacheStore(): CacheParser
    {
        if ($this->snippetCache !== null) {
            return $this->snippetCache;
        }
        $id = $this->getPage()->getDokuwikiId();
        $slotLocalFilePath = $this->getPage()
            ->getPath()
            ->toLocalPath()
            ->toAbsolutePath()
            ->toString();
        $this->snippetCache = new CacheParser($id, $slotLocalFilePath, "snippet.json");
        return $this->snippetCache;
    }

    public function getDependencies(): CacheDependencies
    {
        return $this->cacheDependencies;
    }

    public function getCacheDependencies(): CacheDependencies
    {
        return $this->cacheDependencies;
    }

    public function getDependenciesCacheStore(): CacheParser
    {
        return $this->cacheDependencies->getDependenciesCacheStore();
    }

}
