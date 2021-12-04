<?php


namespace ComboStrap;

/**
 * Class MetadataFileSystemStore
 * @package ComboStrap
 * The meta file system store
 */
class MetadataDokuWikiStore implements MetadataStore
{

    /**
     * Current metadata / runtime metadata / calculated metadata
     * This metadata can only be set when  {@link Syntax::render() rendering}
     * The data may be deleted
     * https://www.dokuwiki.org/devel:metadata#metadata_persistence
     */
    public const CURRENT_METADATA = "current";
    /**
     * Persistent metadata (data that should be in a backup)
     *
     * They are used as the default of the current metadata
     * and is never cleaned
     *
     * https://www.dokuwiki.org/devel:metadata#metadata_persistence
     *
     * Because the current is only usable in rendering, all
     * metadata are persistent inside dokuwiki
     */
    public const PERSISTENT_METADATA = "persistent";


    /**
     * @var array|array[]
     */
    private $metadatas;

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
     *
     *
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

        $persistentMetadata = $this->getPersistentMetadata($metadata);
        if ($persistentMetadata === null) {
            $persistentMetadata = $this->getCurrentMetadata($metadata);
        }
        if ($persistentMetadata === null) {
            return $default;
        } else {
            return $persistentMetadata;
        }


    }


    public function persist()
    {
        foreach ($this->metadatas as $wikiId => $metadata) {
            p_save_metadata($wikiId, $metadata);
        }
        /**
         * Metadata can be changed by other part of the dokuwiki
         * framework
         * We set, we persist, we read again
         * We don't check for each get that the metadata sill fresh is
         */
        $this->metadatas = [];

    }

    private function getPersistentMetadata(Metadata $metadata)
    {

        $key = $this->getMetadatas($metadata)[self::PERSISTENT_METADATA][$metadata->getName()];
        /**
         * Empty string return null
         * because Dokuwiki does not allow to delete keys
         * {@link p_set_metadata()}
         */
        if ($key === "") {
            return null;
        }
        return $key;

    }

    /**
     * @param Metadata $metadata
     * @return array|mixed
     *
     * We don't use {@link p_get_metadata()}
     * because it can trigger a rendering of the meta again
     * and it has a fucking cache
     */
    private function getMetadatas(Metadata $metadata)
    {
        $dokuwikiId = $metadata->getResource()->getPath()->getDokuwikiId();
        if (isset($this->metadatas[$dokuwikiId])) {
            return $this->metadatas[$dokuwikiId];
        }
        $this->metadatas[$dokuwikiId] = p_read_metadata($dokuwikiId);
    }

    public
    function getPersistentMetadatas($metadata): array
    {
        return $this->getMetadatas($metadata)[self::PERSISTENT_METADATA];
    }

    /**
     * @param Metadata $metadata
     * @return mixed|null
     */
    public
    function getCurrentMetadata(Metadata $metadata)
    {
        $key = $this->getMetadatas($metadata)[self::CURRENT_METADATA][$metadata->getName()];
        /**
         * Empty string return null
         * because Dokuwiki does not allow to delete keys
         * {@link p_set_metadata()}
         */
        if ($key === "") {
            return null;
        }
        return $key;
    }

    /**
     * @param Page $page
     */
    public function renderForPage(Page $page){
        /**
         * Read/render the metadata from the file
         * with parsing
         */
        $dokuwikiId = $page->getPath()->getDokuwikiId();
        $actualMeta = $this->metadatas[$dokuwikiId];
        $this->metadatas[$dokuwikiId] = p_render_metadata($dokuwikiId, $actualMeta);
        return $this;
    }
}
