<?php


namespace ComboStrap;

use dokuwiki\Cache\CacheParser;

/**
 * Class CacheManagerForSlot
 * @package ComboStrap
 * Cache data on slot level
 *
 * * This is mostly used on
 *   * side slots to have several output of a list {@link \syntax_plugin_combo_pageexplorer navigation pane} for different namespace (ie there is one cache by namespace)
 *   * header and footer main slot to have one output for each requested main page
 */
class CacheRuntimeDependencies
{
    /**
     * The dependency value is the requested page path
     * (used for syntax mostly used in the header and footer of the main slot for instance)
     */
    public const REQUESTED_PAGE_VALUE = "requested_page";
    /**
     * The special scope value current means the namespace of the requested page
     * The real scope value is then calculated before retrieving the cache
     */
    public const REQUESTED_NAMESPACE_VALUE = "requested_namespace";
    /**
     * @deprecated use the {@link CacheRuntimeDependencies::REQUESTED_NAMESPACE_VALUE}
     */
    public const NAMESPACE_OLD_VALUE = "current";

    public const dependencies = [self::REQUESTED_PAGE_VALUE, self::REQUESTED_NAMESPACE_VALUE];


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

    private $page;

    /**
     * @var string the first key captured
     */
    private $firstActualKey;


    /**
     * CacheManagerForSlot constructor.
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->page = Page::createPageFromId($id);

        $data = $this->getDependenciesCacheStore()->retrieveCache();
        if (!empty($data)) {
            $this->runtimeStoreDependencies = json_decode($data, true);
        }

    }

    public static function create(Page $page): CacheRuntimeDependencies
    {
        return new CacheRuntimeDependencies($page);
    }


    /**
     * @return string - output the namespace used in the cache key
     *
     * For example:
     *   * the ':sidebar' html output may be dependent to the namespace `ns` or `ns2`
     * @throws ExceptionCombo
     */
    public static function getValueForKey($dependenciesValue): string
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
        $requestPage = Page::createPageFromRequestedPage();
        switch ($dependenciesValue) {
            case CacheRuntimeDependencies::NAMESPACE_OLD_VALUE:
            case CacheRuntimeDependencies::REQUESTED_NAMESPACE_VALUE:
                $parentPath = $requestPage->getPath()->getParent();
                if ($parentPath === null) {
                    return ":";
                } else {
                    return $parentPath->toString();
                }
            case CacheRuntimeDependencies::REQUESTED_PAGE_VALUE:
                return $requestPage->getPath()->toString();
            default:
                throw new ExceptionCombo("The requested dependency value ($dependenciesValue) is unknown");
        }


    }

    /**
     * @return string
     *
     * Cache is now managed by dependencies function that creates a unique key
     * for the instruction document and the output document
     *
     * See the discussion at: https://github.com/splitbrain/dokuwiki/issues/3496
     * @throws ExceptionCombo
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

        if ($runtimeDependencies !== null) {

            foreach ($runtimeDependencies as $dependency) {
                $dependencyKey .= self::getValueForKey($dependency);
            }

        }
        return $dependencyKey;
    }


    /**
     * @param string $dependencyName
     * @return CacheRuntimeDependencies
     * @throws ExceptionCombo
     */
    public function addDependency(string $dependencyName): CacheRuntimeDependencies
    {
        if (!in_array($dependencyName, self::dependencies)) {
            throw new ExceptionCombo("Unknown dependency value ($dependencyName");
        }
        $this->runtimeAddedDependencies[$dependencyName] = "";
        return $this;
    }

    public function getDependencies(): ?array
    {
        if ($this->runtimeAddedDependencies != null) {
            return array_keys($this->runtimeAddedDependencies);
        }
        return $this->runtimeStoreDependencies;
    }

    /**
     * The default key as seen in {@link CacheParser}
     * Used for test purpose
     * @return string
     */
    public function getDefaultKey(): string
    {
        return $this->page->getPath()->toLocalPath()->toString() . $_SERVER['HTTP_HOST'] . $_SERVER['SERVER_PORT'];
    }

    public function rerouteCacheDestination(&$cache)
    {

        try {
            $cache->key = $this->getOrCalculateDependencyKey($cache->key);
            $cache->cache = getCacheName($cache->key, '.' . $cache->mode);
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Error while trying to reroute the cache destination for the slot ({$this->page}). You may have cache problem. Error: {$e->getMessage()}");
        }

    }


    /**
     */
    private function storeDependencies()
    {


        /**
         * Cache file
         * Using a cache parser, set the page id and will trigger
         * the parser cache use event in order to log/report the cache usage
         * At {@link action_plugin_combo_cache::logCacheUsage()}
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

    private function getDependenciesCacheStore(): CacheParser
    {
        if ($this->dependenciesCacheStore !== null) {
            return $this->dependenciesCacheStore;
        }
        $id = $this->page->getDokuwikiId();
        $slotLocalFilePath = $this->page
            ->getPath()
            ->toLocalPath()
            ->toAbsolutePath()
            ->toString();
        $this->dependenciesCacheStore = new CacheParser($id, $slotLocalFilePath, "deps.json");
        return $this->dependenciesCacheStore;
    }

}
