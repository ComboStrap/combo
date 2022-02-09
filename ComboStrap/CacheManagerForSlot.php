<?php


namespace ComboStrap;

use dokuwiki\Cache\CacheParser;

/**
 * Class CacheManagerForSlot
 * @package ComboStrap
 * Cache data on slot level
 */
class CacheManagerForSlot
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
    private $dependencies = [];
    private $id;

    /**
     * CacheManagerForSlot constructor.
     * @param $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     *
     * Cache is now managed by dependencies function that creates a unique key
     * for the instruction document and the output document
     *
     * See the discussion at: https://github.com/splitbrain/dokuwiki/issues/3496
     * @throws ExceptionCombo
     */
    public function getCacheKeyFromRuntimeDependencies(): string
    {
        $key = "";
        foreach ($this->dependencies as $dependency => $value) {
            if ($dependency !== CacheRuntimeDependencies::DEPENDENCY_NAME) {
                throw new ExceptionCombo("Unknown dependency");
            } else {
                $key .= CacheRuntimeDependencies::getValueForKey($value);
            }
        }
        return $key;
    }

    /**
     * @throws ExceptionCombo
     */
    public function getCacheFile(CacheParser $cache): string
    {
        $key = $this->getCacheKeyFromRuntimeDependencies();
        return getCacheName($key, '.' . $cache->mode);
    }

    /**
     * @param string $dependencyName
     * @param string|array $value
     */
    public function addDependency(string $dependencyName, $value)
    {
        $actualValues = &$this->dependencies[$dependencyName];
        if ($actualValues === null) {
            $this->dependencies[$dependencyName] = [$value];
            return;
        }
        foreach ($actualValues as $actualValue) {
            if ($actualValue === $value) {
                return;
            }
        }
        $actualValues[] = $value;

    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

}
