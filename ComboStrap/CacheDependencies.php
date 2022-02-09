<?php


namespace ComboStrap;

use dokuwiki\Cache\CacheParser;

/**
 * Class CacheManagerForSlot
 * @package ComboStrap
 * Cache data on slot level
 */
class CacheDependencies
{

    /**
     * @var array list of dependencies to calculate the cache key
     *
     * In a general pattern, a dependency is a series of function that would output runtime data
     * that should go into the render cache key such as user logged in, requested page, namespace of the requested page, ...
     *
     * The cache dependencies data are saved alongside the page (same as snippets)
     *
     */
    private $dependencies = null;
    private $page;
    /**
     * @var string
     */
    private $dependencyKey;

    /**
     * CacheManagerForSlot constructor.
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->page = Page::createPageFromId($id);
    }

    public static function create(Page $page): CacheDependencies
    {
        return new CacheDependencies($page);
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
        $this->dependencyKey = $actualKey;
        if ($this->dependencyKey === null && $this->dependencies !== null) {

            foreach ($this->dependencies as $dependency => $function) {
                if ($function != null && $function !== CacheRuntimeDependencies::DEPENDENCY_NAME) {
                    throw new ExceptionCombo("Unknown dependency function");
                } else {
                    $this->dependencyKey .= CacheRuntimeDependencies::getValueForKey($dependency);
                }
            }

        }
        return $this->dependencyKey;
    }


    /**
     * A wrapper to show how the cache file name is calculated
     * inside Dokuwiki
     */
    public function getCacheFile(CacheParser $cache): string
    {
        return getCacheName($this->getDependencyKey(), '.' . $cache->mode);
    }

    /**
     * @param string $dependencyName
     * @param string|null $dependencyFunction
     */
    public function addDependency(string $dependencyName, ?string $dependencyFunction)
    {
        $this->dependencies[$dependencyName] = $dependencyFunction;
    }

    public function getDependencies(): ?array
    {
        return $this->dependencies;
    }

    public function getDependencyKey(): string
    {
        return $this->dependencyKey;
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

}
