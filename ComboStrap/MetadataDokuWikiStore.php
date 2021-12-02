<?php


namespace ComboStrap;

use http\Exception\RuntimeException;

/**
 * Class MetadataFileSystemStore
 * @package ComboStrap
 * The meta file system store
 */
class MetadataDokuWikiStore implements MetadataStore
{


    const CANONICAL = Metadata::CANONICAL_PROPERTY;


    public static function create(): MetadataDokuWikiStore
    {
        return new MetadataDokuWikiStore();
    }

    public function set(Metadata $metadata)
    {
        $name = $metadata->getName();
        $persistentValue = $metadata->toPersistentValue();
        $defaultValue = $metadata->toPersistentDefaultValue();
        $type = $metadata->getPersistenceType();
        $resource = $metadata->getResource();
        if ($resource === null) {
            throw new ExceptionComboRuntime("A resource is mandatory", self::CANONICAL);
        }
        if (!($resource instanceof Page)) {
            throw new ExceptionComboRuntime("The DokuWiki metadata store is only for page resource", self::CANONICAL);
        }
        $resource->setMetadata($name, $persistentValue, $defaultValue, $type);
    }

    /**
     * @param Metadata $metadata
     * @param null $default
     * @return mixed|null
     */
    public function get(Metadata $metadata, $default = null)
    {
        $resource = $metadata->getResource();
        if ($resource === null) {
            throw new ExceptionComboRuntime("A resource is mandatory", self::CANONICAL);
        }
        if (!($resource instanceof Page)) {
            throw new ExceptionComboRuntime("The DokuWiki metadata store is only for page resource", self::CANONICAL);
        }
        return $resource->getMetadata($metadata->getName(), $default);
    }


}
