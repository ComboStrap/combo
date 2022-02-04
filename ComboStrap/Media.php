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
 * if any
 */
abstract class Media extends ResourceComboAbs
{
    const RESOURCE_TYPE = "media";

    /**
     * @var TagAttributes
     */
    protected $attributes;

    /**
     * @var Path
     */
    private $path;

    /**
     * Media constructor.
     * The file system path and the attributes (properties)
     */
    public function __construct(Path $path, $attributes = null)
    {
        if ($attributes === null) {
            $attributes = TagAttributes::createEmpty();
        }
        $this->attributes = $attributes;

        $this->path = $path;

    }


    /**
     * @return string $cache - one of {@link CacheMedia::CACHE_KEY} or null if not set
     */
    public function getCache(): ?string
    {
        return $this->attributes->getValue(CacheMedia::CACHE_KEY);

    }

    public function getTitle()
    {
        return $this->attributes->getValue(TagAttributes::TITLE_KEY);
    }

    public function &getAttributes()
    {
        return $this->attributes;
    }


    public function getPath(): Path
    {
        return $this->path;
    }

    public function getName(): ?string
    {
        return ResourceName::createForResource($this)
            ->getValue();
    }

    public function getNameOrDefault(): string
    {
        return ResourceName::createForResource($this)
            ->getValueOrDefault();
    }

    /**
     * The URL will change if the file change
     * @param $queryParameters
     */
    protected function addCacheBusterToQueryParameters(&$queryParameters)
    {
        $queryParameters[CacheMedia::CACHE_BUSTER_KEY] = $this->getBuster();
    }

    /**
     * @return mixed
     */
    public abstract function getUrl();


    public function getReadStoreOrDefault(): MetadataStore
    {
        throw new ExceptionComboRuntime("To implement");
    }

    function getType(): string
    {
        return self::RESOURCE_TYPE;
    }


    public function getUid(): Metadata
    {
        throw new ExceptionComboRuntime("To implement");
    }

    public function __toString()
    {
        return $this->getPath()->toString();
    }


}
