<?php


namespace ComboStrap;

/**
 * Class Cache
 * A wrapper around {@link \dokuwiki\Cache\Cache}
 * @package ComboStrap
 */
class Cache
{
    /**
     * @var File
     */
    private $path;
    /**
     * @var \dokuwiki\Cache\Cache
     */
    private $fileCache;
    private $maxAge;


    /**
     * Cache constructor.
     */
    public function __construct(File $path, TagAttributes &$tagAttributes)
    {

        $this->path = $path;


        /**
         * Cache Attribute
         */
        $cacheParameter = $tagAttributes->getValueAsStringAndRemove('cache', -1);
        /**
         * Cache transformation
         * From Image cache value (https://www.dokuwiki.org/images#caching)
         * to {@link Cache::setMaxAgeInSec()}
         */
        switch ($cacheParameter) {
            case "nocache":
                $cacheParameter = -1;
                break;
            case "recache":
                global $conf;
                $cacheParameter = $conf['cachetime'];
                break;
        }
        $this->setMaxAgeInSec($cacheParameter);

        /**
         * Cache Key Construction
         */
        $cacheKey = $this->path->getPath();
        foreach ($tagAttributes->getComponentAttributes() as $name => $value) {
            $cacheKey .= "&" . $name . "=" . $value;
        }

        $this->fileCache = new \dokuwiki\Cache\Cache($cacheKey, $this->path->getExtension());

    }

    public static function createFromPath(File $file, $tagAttributes = null)
    {
        if ($tagAttributes == null) {
            $tagAttributes = TagAttributes::createEmpty();
        }
        return new Cache($file, $tagAttributes);
    }


    public function isCacheUsable()
    {
        if ($this->maxAge == 0) {
            return false;
        } else {
            $dependencies = array(
                'files' => [
                    $this->path->getPath(),
                    Resources::getComboHome() . "/plugin.info.txt"
                ]
            );
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

    public function getFile()
    {
        return File::createFromPath($this->fileCache->cache);
    }


}
