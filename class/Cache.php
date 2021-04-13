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
     * @var Cache
     */
    private $fileCache;
    private $maxAge;

    /**
     * Cache constructor.
     */
    public function __construct(File $path)
    {
        $this->path = $path;
        $key = $this->path->getPath();
        $this->fileCache = new \dokuwiki\Cache\Cache($key, $this->path->getExtension());
    }

    public static function createFromPath(File $file)
    {
        return new Cache($file);
    }


    public function cacheUsable()
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
        return new File($this->fileCache->cache);
    }



}
