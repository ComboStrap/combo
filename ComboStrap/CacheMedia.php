<?php


namespace ComboStrap;

use dokuwiki\Cache\Cache;

/**
 * Class Cache
 * A wrapper around {@link \dokuwiki\Cache\Cache}
 * @package ComboStrap
 * that takes into account the arguments / properties of the media
 * to create the cache file
 */
class CacheMedia
{
    /**
     * Cache
     * values:
     *   * cache
     *   * nocache
     *   * recache
     */
    const CACHE_KEY = 'cache';
    const CACHE_DEFAULT_VALUE = "cache";
    /**
     * buster got the same value
     * that the `rev` attribute (ie mtime)
     * We don't use rev as cache buster because Dokuwiki still thinks
     * that this is an old file and search in the attic
     * as seen in the function {@link mediaFN()}
     *
     * The value used by Dokuwiki for the buster is tseed.
     */
    const CACHE_BUSTER_KEY = "tseed";

    /**
     * @var LocalPath
     */
    private $path;
    /**
     * @var Cache
     */
    private $fileCache;
    private $maxAge;


    /**
     * Cache constructor.
     */
    public function __construct(Path $path, TagAttributes $tagAttributes)
    {

        $this->path = $path;

        if ($path instanceof DokuPath) {
            $this->path = $path->toLocalPath();
        }
        /**
         * Cache Key Construction
         */
        $cacheKey = $this->path->toAbsolutePath()->toString();
        foreach ($tagAttributes->getComponentAttributes() as $name => $value) {

            /**
             * The cache attribute are not part of the key
             * obviously
             */
            if (in_array($name, [
                CacheMedia::CACHE_KEY,
                CacheMedia::CACHE_BUSTER_KEY,
            ])) {
                continue;
            }

            /**
             * Normalize name (from w to width)
             */
            $name = TagAttributes::AttributeNameFromDokuwikiToCombo($name);

            $cacheKey .= "&" . $name . "=" . $value;

        }

        /**
         * Cache Attribute
         */
        $cacheParameter = $tagAttributes->getValue(self::CACHE_KEY, self::CACHE_DEFAULT_VALUE);
        /**
         * Cache transformation
         * From Image cache value (https://www.dokuwiki.org/images#caching)
         * to {@link CacheMedia::setMaxAgeInSec()}
         */
        switch ($cacheParameter) {
            case "nocache":
                $cacheParameter = 0;
                break;
            case "recache":
                global $conf;
                $cacheParameter = $conf['cachetime'];
                break;
            case "cache":
            default:
                $cacheParameter = -1;
                break;
        }
        $this->setMaxAgeInSec($cacheParameter);


        $this->fileCache = new Cache($cacheKey, ".{$this->path->getExtension()}");

    }

    public static function createFromPath(Path $path, $tagAttributes = null): CacheMedia
    {
        if ($tagAttributes == null) {
            $tagAttributes = TagAttributes::createEmpty();
        }
        return new CacheMedia($path, $tagAttributes);
    }


    /**
     * Cache file depends on code version and configuration
     * @return bool
     */
    public function isCacheUsable()
    {
        if ($this->maxAge == 0) {
            return false;
        } else {
            $files = [];
            if ($this->path->getExtension() === "svg") {
                // svg generation depends on configuration
                $files = getConfigFiles('main');
                $files[] = Site::getComboHome()->resolve("ComboStrap")->resolve("SvgDocument.php")->toString();
                $files[] = Site::getComboHome()->resolve("ComboStrap")->resolve( "XmlDocument.php")->toString();
            }
            $files[] = $this->path->toAbsolutePath()->toString();
            $files[] = Site::getComboHome()->resolve("plugin.info.txt");
            $dependencies = array('files' => $files);
            if ($this->maxAge != null) {
                $dependencies['age'] = $this->maxAge;
            }
            return $this->fileCache->useCache($dependencies);
        }
    }

    public function setMaxAgeInSec($maxAge)
    {

        /**
         * Got the Dokuwiki Rule
         * from
         * https://www.dokuwiki.org/devel:event:fetch_media_status
         */
        if ($maxAge < 0) {
            // cache forever
            $this->maxAge = PHP_INT_MAX;
        } elseif ($maxAge == 0) {
            // never cache
            $this->maxAge = 0;
        } else {
            $this->maxAge = $maxAge;
        }
    }

    public function storeCache($content)
    {
        $this->fileCache->storeCache($content);
    }

    public function getFile(): LocalPath
    {
        return LocalPath::createFromPath($this->fileCache->cache);
    }


}
