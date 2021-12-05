<?php


namespace ComboStrap;

use dokuwiki\Extension\Event;

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
        $persistentValue = $metadata->toStoreValue();
        $defaultValue = $metadata->toStoreDefaultValue();
        $resource = $metadata->getResource();
        if ($resource === null) {
            throw new ExceptionComboRuntime("A resource is mandatory", self::CANONICAL);
        }
        if (!($resource instanceof Page)) {
            throw new ExceptionComboRuntime("The DokuWiki metadata store is only for page resource", self::CANONICAL);
        }
        $dokuwikiId = $resource->getDokuwikiId();
        $this->setFromWikiId($dokuwikiId, $persistentValue, $defaultValue);
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

    /**
     * Getting a metadata for a resource via its name
     * when we don't want to create a class
     *
     * This function is used primarily by derived / process metadata
     *
     * @param ResourceCombo $resource
     * @param string $metadataName
     * @return mixed
     */
    public function getFromResourceAndName(ResourceCombo $resource, string $metadataName)
    {
        $wikiId = $resource->getPath()->getDokuwikiId();
        return $this->metadatas[$wikiId][$metadataName];
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
        return $this->getMetadatasFromWikiId($dokuwikiId);
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
     * @return MetadataDokuWikiStore
     */
    public function renderForPage(Page $page): MetadataDokuWikiStore
    {
        /**
         * Read/render the metadata from the file
         * with parsing
         */
        $dokuwikiId = $page->getPath()->getDokuwikiId();
        $actualMeta = $this->metadatas[$dokuwikiId];
        $this->metadatas[$dokuwikiId] = p_render_metadata($dokuwikiId, $actualMeta);
        return $this;
    }

    private function &getMetadatasFromWikiId($dokuwikiId)
    {
        if (isset($this->metadatas[$dokuwikiId])) {
            return $this->metadatas[$dokuwikiId];
        }
        $this->metadatas[$dokuwikiId] = p_read_metadata($dokuwikiId);
        return $this->metadatas[$dokuwikiId];
    }

    /**
     * Change a meta on file
     * and triggers the {@link Page::PAGE_METADATA_MUTATION_EVENT} event
     *
     * @param $wikiId
     * @param $key
     * @param $value
     * @param null $default - use in case of boolean
     */
    private function setFromWikiId($wikiId, $key, $value, $default = null)
    {
        $metadata = &$this->getMetadatasFromWikiId($wikiId);
        $type = self::PERSISTENT_METADATA;
        $oldValue = $metadata[$type][$key];
        if (is_bool($value)) {
            if ($oldValue === null) {
                $oldValue = $default;
            } else {
                $oldValue = Boolean::toBoolean($oldValue);
            }
        }
        if ($oldValue !== $value) {

            if ($value !== null) {
                $metadata[$type][$key] = $value;
            } else {
                unset($metadata[$type][$key]);
            }
            /**
             * Metadata in Dokuwiki is fucked up.
             *
             * You can't remove a metadata,
             * You need to know if this is a rendering or not
             *
             * See just how fucked {@link p_set_metadata()} is
             *
             * Also don't change the type of the value to a string
             * otherwise dokuwiki will not see a change
             * between true and a string and will not persist the value
             *
             * A metadata is also not immediately flushed on disk
             * in a test when rendering
             * They are going into the global $METADATA_RENDERERS
             *
             * A current metadata is never stored if not set in the rendering process
             * We persist therefore always
             */
            $persistent = true;
            p_set_metadata($wikiId,
                [
                    $key => $value
                ],
                false,
                $persistent
            );
            /**
             * Event
             */
            $data = [
                "name" => $key,
                "new_value" => $value,
                "old_value" => $oldValue
            ];
            Event::createAndTrigger(Page::PAGE_METADATA_MUTATION_EVENT, $data);
        }

    }


}
