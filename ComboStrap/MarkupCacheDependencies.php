<?php


namespace ComboStrap;

use dokuwiki\Cache\CacheParser;

/**
 *
 * @package ComboStrap
 *
 * Manage the cache dependencies for a slot level (not instructions cache).
 *
 * The dependencies are stored on a file system.
 *
 * Cache dependencies are used:
 *   * to generate the cache key output
 *   * to add cache validity dependency such as requested page,
 *
 * For cache key generation, this is mostly used on
 *   * side slots to have several output of a list {@link \syntax_plugin_combo_pageexplorer navigation pane} for different namespace (ie there is one cache by namespace)
 *   * header and footer main slot to have one output for each requested main page
 */
class MarkupCacheDependencies
{
    /**
     * The dependency value is the requested page path
     * (used for syntax mostly used in the header and footer of the main slot for instance)
     */
    public const REQUESTED_PAGE_DEPENDENCY = "requested_page";
    /**
     * The special scope value current means the namespace of the requested page
     * The real scope value is then calculated before retrieving the cache
     */
    public const REQUESTED_NAMESPACE_DEPENDENCY = "requested_namespace";
    /**
     * @deprecated use the {@link MarkupCacheDependencies::REQUESTED_NAMESPACE_DEPENDENCY}
     */
    public const NAMESPACE_OLD_VALUE = "current";

    /**
     * This dependencies have an impact on the
     * output location of the cache
     * {@link MarkupCacheDependencies::getOrCalculateDependencyKey()}
     */
    public const OUTPUT_DEPENDENCIES = [self::REQUESTED_PAGE_DEPENDENCY, self::REQUESTED_NAMESPACE_DEPENDENCY];

    /**
     * This dependencies have an impact on the freshness
     * of the cache
     */
    public const validityDependencies = [
        self::BACKLINKS_DEPENDENCY,
        self::SQL_DEPENDENCY,
        self::PAGE_PRIMARY_META_DEPENDENCY,
        self::PAGE_SYSTEM_DEPENDENCY
    ];

    /**
     * Backlinks are printed in the page
     * {@link \action_plugin_combo_backlinkmutation}
     * If a referent page add or delete a link,
     * the slot should be refreshed / cache should be deleted
     */
    const BACKLINKS_DEPENDENCY = "backlinks";

    /**
     * A page sql is in the page
     * (The page should be refreshed by default once a day)
     */
    const SQL_DEPENDENCY = "sql";

    /**
     * If the name, the title, the h1 or the description
     * of a page changes, the cache should be invalidated
     * See {@link \action_plugin_combo_pageprimarymetamutation}
     */
    const PAGE_PRIMARY_META_DEPENDENCY = "page_primary_meta";

    /**
     * If a page is added or deleted
     * See {@link \action_plugin_combo_pagesystemmutation}
     */
    const PAGE_SYSTEM_DEPENDENCY = "page_system";

    const CANONICAL = "cache:dependency";


    /**
     * @var CacheParser
     */
    private $dependenciesCacheStore;


    /**
     * @var array list of dependencies to calculate the cache key
     *
     * In a general pattern, a dependency is a series of function that would output runtime data
     * that should go into the render cache key such as user logged in, requested page, namespace of the requested page, ...
     *
     * The cache dependencies data are saved alongside the page (same as snippets)
     *
     */
    private $runtimeAddedDependencies = null;
    /**
     * The stored runtime dependencies
     * @var array
     */
    private $runtimeStoreDependencies;

    private Path $pathFragment;

    /**
     * @var string the first key captured
     */
    private $firstActualKey;
    private Path $requestedPath;


    /**
     * CacheManagerForSlot constructor.
     *
     */
    private function __construct(Path $pathFragment, Path $requestedPath)
    {

        $this->pathFragment = $pathFragment;
        $this->requestedPath = $requestedPath;

        $data = $this->getDependenciesCacheStore()->retrieveCache();
        if (!empty($data)) {
            $this->runtimeStoreDependencies = json_decode($data, true);
        }

    }

    public static function create(Path $pathFragment, Path $requestedPath): MarkupCacheDependencies
    {
        return new MarkupCacheDependencies($pathFragment, $requestedPath);
    }

    /**
     * Rerender for now only the secondary slot if it has cache dependency
     * (ie {@link MarkupCacheDependencies::PAGE_SYSTEM_DEPENDENCY} or {@link MarkupCacheDependencies::PAGE_PRIMARY_META_DEPENDENCY})
     * @param $path
     * @param string $dependency -  a {@link MarkupCacheDependencies} ie
     * @param string $event
     */
    public static function reRenderSideSlotIfNeeded($path, string $dependency, string $event)
    {
        global $ID;
        $keep = $ID;
        try {
            $ID = WikiPath::toDokuWikiId($path);
            /**
             * Rerender secondary slot if needed
             */
            $page = MarkupPath::createMarkupFromId($ID);
            $independentSlots = $page->getPrimaryIndependentSlots();
            foreach ($independentSlots as $secondarySlot) {
                $htmlDocument = $secondarySlot->getHtmlFetcher();
                try {
                    $cacheDependencies = $htmlDocument->getCacheDependencies();
                } finally {
                    $htmlDocument->close();
                }
                if ($cacheDependencies->hasDependency($dependency)) {
                    $link = PluginUtility::getDocumentationHyperLink("cache:slot", "Slot Dependency", false);
                    $message = "$link ($dependency) was met with the primary slot ($path).";
                    CacheLog::deleteCacheIfExistsAndLog(
                        $htmlDocument,
                        $event,
                        $message
                    );
                    CacheLog::renderCacheAndLog(
                        $htmlDocument,
                        $event,
                        $message
                    );
                }
            }
        } finally {
            $ID = $keep;
        }
    }


    /**
     * @return string - output the namespace used in the cache key
     *
     * For example:
     *   * the ':sidebar' html output may be dependent to the namespace `ns` or `ns2`
     * @throws ExceptionCompile
     */
    public function getValueForKey($dependenciesValue): string
    {

        /**
         * Set the logical id
         * When no $ID is set (for instance, test),
         * the logical id is the id
         *
         * The logical id depends on the namespace attribute of the {@link \syntax_plugin_combo_pageexplorer}
         * stored in the `scope` metadata.
         *
         * Scope is directory/namespace based
         */
        $requestedPage = MarkupPath::createPageFromPathObject($this->requestedPath);
        switch ($dependenciesValue) {
            case MarkupCacheDependencies::NAMESPACE_OLD_VALUE:
            case MarkupCacheDependencies::REQUESTED_NAMESPACE_DEPENDENCY:
                try {
                    $parentPath = $requestedPage->getPathObject()->getParent();
                    return $parentPath->toPathString();
                } catch (ExceptionNotFound $e) {
                    // root
                    return ":";
                }
            case MarkupCacheDependencies::REQUESTED_PAGE_DEPENDENCY:
                return $requestedPage->getPathObject()->toPathString();
            default:
                throw new ExceptionCompile("The requested dependency value ($dependenciesValue) has no calculation");
        }


    }

    /**
     * @return string
     *
     * Cache is now managed by dependencies function that creates a unique key
     * for the instruction document and the output document
     *
     * See the discussion at: https://github.com/splitbrain/dokuwiki/issues/3496
     * @throws ExceptionCompile
     * @var $actualKey
     */
    public function getOrCalculateDependencyKey($actualKey): string
    {
        /**
         * We should wrap a call only once
         * We capture therefore the first actual key passed
         */
        if ($this->firstActualKey === null) {
            $this->firstActualKey = $actualKey;
        }
        $dependencyKey = $this->firstActualKey;
        $runtimeDependencies = $this->getDependencies();

        foreach ($runtimeDependencies as $dependency) {
            if (in_array($dependency, self::OUTPUT_DEPENDENCIES)) {
                $dependencyKey .= $this->getValueForKey($dependency);
            }
        }
        return $dependencyKey;
    }


    /**
     * @param string $dependencyName
     * @return MarkupCacheDependencies
     */
    public function addDependency(string $dependencyName): MarkupCacheDependencies
    {
        if (PluginUtility::isDevOrTest()) {
            if (!in_array($dependencyName, self::OUTPUT_DEPENDENCIES) &&
                !in_array($dependencyName, self::validityDependencies)
            ) {
                throw new ExceptionRuntime("Unknown dependency value ($dependencyName)");
            }
        }
        $this->runtimeAddedDependencies[$dependencyName] = "";
        return $this;
    }

    public
    function getDependencies(): array
    {
        if ($this->runtimeAddedDependencies !== null) {
            return array_keys($this->runtimeAddedDependencies);
        }
        if ($this->runtimeStoreDependencies === null) {
            return [];
        }
        return array_keys($this->runtimeStoreDependencies);
    }

    /**
     * The default key as seen in {@link CacheParser}
     * Used for test purpose
     * @return string
     */
    public
    function getDefaultKey(): string
    {
        try {
            $keyDokuWikiCompliant = str_replace("\\", "/", LocalPath::createFromPathObject($this->pathFragment)->toPathString());
        } catch (ExceptionBadArgument $e) {
            LogUtility::warning("Error while getting the dokuwiki compliant key. Error: " . $e->getMessage());
            $keyDokuWikiCompliant = $this->pathFragment->toPathString();
        }
        return $keyDokuWikiCompliant . $_SERVER['HTTP_HOST'] . $_SERVER['SERVER_PORT'];
    }

    public
    function rerouteCacheDestination(&$cache)
    {

        try {

            $cache->key = $this->getOrCalculateDependencyKey($cache->key);
            $cache->cache = getCacheName($cache->key, '.' . $cache->mode);

        } catch (ExceptionCompile $e) {
            LogUtility::msg("Error while trying to reroute the cache destination for the slot ({$this->pathFragment}). You may have cache problem. Error: {$e->getMessage()}");
        }

    }


    /**
     */
    public function storeDependencies()
    {

        /**
         * Cache file
         * Using a cache parser, set the page id and will trigger
         * the parser cache use event in order to log/report the cache usage
         * At {@link action_plugin_combo_cache::createCacheReport()}
         */
        $dependencies = $this->getDependenciesCacheStore();
        $deps = $this->runtimeAddedDependencies;
        if ($deps !== null) {
            $jsonDeps = json_encode($deps);
            $dependencies->storeCache($jsonDeps);
        } else {
            // dependencies does not exist or were removed
            $dependencies->removeCache();
        }


    }

    public
    function getDependenciesCacheStore(): CacheParser
    {
        if ($this->dependenciesCacheStore !== null) {
            return $this->dependenciesCacheStore;
        }

        /**
         * The local path to calculate the full qualified Os path
         */
        if ($this->pathFragment instanceof LocalPath) {
            $localPath = $this->pathFragment;
        } else {
            try {
                $localPath = LocalPath::createFromPathObject($this->pathFragment);
            } catch (ExceptionBadArgument $e) {
                throw new ExceptionRuntimeInternal("The page fragment path should be local. Error:{$e->getMessage()}", self::CANONICAL);
            }
        }
        /**
         * The wiki path for rendering (the path is shorter)
         */
        if ($this->pathFragment instanceof WikiPath) {
            $shorterPath = $this->pathFragment;
        } else {
            try {
                $shorterPath = WikiPath::createFromPathObject($this->pathFragment);
            } catch (ExceptionBadArgument $e) {
                // It could not be transformed
                $shorterPath = $localPath;
            }
        }
        $slotLocalFilePath = $localPath
            ->toAbsolutePath()
            ->toPathString();
        $this->dependenciesCacheStore = new CacheParser($shorterPath->toPathString(), $slotLocalFilePath, "deps.json");
        return $this->dependenciesCacheStore;
    }

    public
    function hasDependency(string $dependencyName): bool
    {
        $dependencies = $this->getDependencies();
        if ($dependencies === null) {
            return false;
        }
        return in_array($dependencyName, $dependencies);
    }

}
