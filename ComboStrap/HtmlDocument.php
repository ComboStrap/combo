<?php


namespace ComboStrap;


use dokuwiki\Cache\CacheParser;

class HtmlDocument extends OutputDocument
{
    const extension = "xhtml";
    /**
     * @var CacheParser
     */
    private $snippetCache;

    /**
     * HtmlDocument constructor.
     * @param Page $page
     */
    public function __construct(Page $page)
    {
        parent::__construct($page);
        if ($page->isSlot()) {

            /**
             * Logical cache based on scope (ie logical id) is the scope and part of the key
             *
             * We don't use {@link CacheRenderer}
             * because the cache key is the physical file
             */
            $this->cache = new CacheByLogicalKey($page, $this->getExtension());

        }

        /**
         * Snippet cache
         */
        /**
         * Using a cache parser, set the page id and will trigger
         * the parser cache use event in order to log/report the cache usage
         * At {@link action_plugin_combo_cache::logCacheUsage()}
         */
        $id = $this->getPage()->getDokuwikiId();
        $slotLocalFilePath = $this->getPage()
            ->getPath()
            ->toLocalPath()
            ->toAbsolutePath()
            ->toString();
        $this->snippetCache = new CacheParser($id, $slotLocalFilePath, "snippet.json");

    }


    function getExtension(): string
    {
        return self::extension;
    }

    function getRendererName(): string
    {
        return self::extension;
    }

    public function shouldProcess(): bool
    {
        if (!Site::isHtmlRenderCacheOn()) {
            return true;
        }
        return parent::shouldProcess();
    }

    public function getOrProcessContent(): string
    {

        $debug = "";


        if ($this->shouldProcess()) {
            $this->process();

            /**
             * Scope may change during processing
             * And therefore logical id also
             */
            $scope = $this->getPage()->getScope();
            $logicalId = $this->getPage()->getLogicalId();

            if (
            (Site::debugIsOn() || PluginUtility::isDevOrTest())
            ) {
                $cachePath = $this->getCachePath()->toAbsolutePath()->toString();
                $debug = "<div id=\"{$this->getPage()->getCacheHtmlId()}\" style=\"display:none;\" data-logical-Id=\"$logicalId\" data-scope=\"$scope\" data-cache-op=\"created\" data-cache-file=\"{$cachePath}\"></div>";
            }

        } else {

            $scope = $this->getPage()->getScope();
            $logicalId = $this->getPage()->getLogicalId();
            if (
            (Site::debugIsOn() || PluginUtility::isDevOrTest())
            ) {
                $debug = "<div id=\"{$this->getPage()->getCacheHtmlId()}\" style=\"display:none;\" data-logical-Id=\"$logicalId\" data-scope=\"$scope\" data-cache-op=\"forbidden\"></div>";
            }

        }
        return $debug . $this->getContent();


    }


    public function storeContent($content)
    {
        /**
         * Html document is stored
         *
         * We make the Snippet store to Html store an atomic operation
         *
         * Why ? Because if the rendering of the page is stopped,
         * the cache of the HTML page may be stored but not the cache of the snippets
         * leading to a bad page because the next rendering will see then no snippets.
         */
        $this->storeSnippets();
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
        $snippetManager = PluginUtility::getSnippetManager();
        $jsonDecodeSnippets = $snippetManager->getSnippetsForSlot($slotId);
        if ($jsonDecodeSnippets !== null) {
            $data1 = json_encode($jsonDecodeSnippets);
            $this->snippetCache->storeCache($data1);
        } else {
            $this->snippetCache->removeCache();
        }

    }

    /**
     * @return Snippet[]
     */
    public
    function getSnippets(): array
    {
        $data = $this->snippetCache->retrieveCache();
        $nativeSnippets = [];
        if (!empty($data)) {
            $jsonDecodeSnippets = json_decode($data, true);
            foreach ($jsonDecodeSnippets as $type => $snippets) {
                foreach ($snippets as $snippetId => $snippetArray) {
                    try {
                        $nativeSnippets[] = Snippet::createFromJson($snippetArray);
                    } catch (ExceptionCombo $e) {
                        LogUtility::msg("The snippet json array cannot be build into a snippet object. " . $e->getMessage());
                    }
                }
            }
        }
        return $nativeSnippets;

    }

    private function removeSnippets()
    {
        $snippetCacheFile = $this->snippetCache->cache;
        if ($snippetCacheFile !== null) {
            if (file_exists($snippetCacheFile)) {
                unlink($snippetCacheFile);
            }
        }
    }
}
