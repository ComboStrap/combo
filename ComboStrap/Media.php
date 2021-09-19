<?php


namespace ComboStrap;

/**
 * Class Media
 * @package ComboStrap
 *
 * * It represents a generated file:
 *   * if the image width is 20 -> the image is generated
 *   * same for svg ...
 *
 * This is why there is a cache attribute - this is the cache of the generated file
 */
class Media extends DokuPath
{

    /**
     * @var TagAttributes
     */
    protected $attributes;

    /**
     * Media constructor.
     */
    public function __construct($absolutePath, $rev = null, $attributes = null)
    {
        if ($attributes === null) {
            $attributes = TagAttributes::createEmpty();
        }
        $this->attributes = $attributes;
        parent::__construct($absolutePath, DokuPath::MEDIA_TYPE, $rev);

    }

    public static function create(DokuPath $dokuPath, $rev, $tagAttributes): Media
    {
        return new Media($dokuPath, $rev, $tagAttributes);
    }

    /**
     * @return string $cache - one of {@link CacheMedia::CACHE_KEY}
     */
    public function getCache(): string
    {
        return $this->attributes->getValue(CacheMedia::CACHE_KEY, CacheMedia::CACHE_DEFAULT_VALUE);

    }

    public function getTitle()
    {
        return $this->attributes->getValue(TagAttributes::TITLE_KEY);
    }

    public function &getAttributes(){
        return $this->attributes;
    }

}
