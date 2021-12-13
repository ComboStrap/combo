<?php


namespace ComboStrap;

use dokuwiki\Extension\Event;

/**
 * Class MetadataFileSystemStore
 * @package ComboStrap
 * The meta file system store
 *
 * Dokuwiki allows the creation of metadata via rendering {@link Page::renderMetadataAndFlush()}
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
     * @var MetadataDokuWikiStore
     */
    private static $store;


    const CANONICAL = Metadata::CANONICAL;


    public static function getOrCreate(): MetadataDokuWikiStore
    {
        if (self::$store === null) {
            self::$store = new MetadataDokuWikiStore();
        }
        return self::$store;
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
        $this->setFromWikiId($dokuwikiId, $name, $persistentValue, $defaultValue);
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
        return $this->getFromWikiId($resource->getDokuwikiId(), $metadata->getName(), $default);


    }

    /**
     * Getting a metadata for a resource via its name
     * when we don't want to create a class
     *
     * This function is used primarily by derived / process metadata
     *
     * @param ResourceCombo $resource
     * @param string $name
     * @param null $default
     * @return mixed
     */
    public function getFromResourceAndName(ResourceCombo $resource, string $name, $default = null)
    {
        $wikiId = $resource->getPath()->getDokuwikiId();
        return $this->getFromWikiId($wikiId, $name, $default);
    }


    public function persist()
    {

        /**
         * Metadata can be changed by other part of the dokuwiki
         * framework, even cached in a global variable in {@link p_get_metadata()}
         * We don't use a memory store then
         * only the function of dokuwiki
         */

    }

    /**
     * @param Page $resource
     * @param string $name
     * @param string|array $value
     */
    public function setFromResourceAndName(ResourceCombo $resource, string $name, $value)
    {
        $this->setFromWikiId($resource->getDokuwikiId(), $name, $value);
    }

    /**
     * @param $dokuwikiId
     * @param $name
     * @return mixed|null
     */
    private function getPersistentMetadata($dokuwikiId, $name)
    {

        $value = $this->getRawMetadatasFromWikiId($dokuwikiId)[self::PERSISTENT_METADATA][$name];
        /**
         * Empty string return null
         * because Dokuwiki does not allow to delete keys
         * {@link p_set_metadata()}
         */
        if ($value === "") {
            return null;
        }
        return $value;

    }


    /**
     * @param $dokuWikiId
     * @param $name
     * @return mixed|null
     * @deprecated we use now the {@link p_get_metadata()} function to {@link MetadataDokuWikiStore::getRawMetadatasFromWikiId()} retrieve the meta that {@link MetadataDokuWikiStore::getFlatMetadata()} the array
     */
    public
    function getCurrentMetadata($dokuWikiId, $name)
    {
        $value = $this->getRawMetadatasFromWikiId($dokuWikiId)[self::CURRENT_METADATA][$name];
        /**
         * Empty string return null
         * because Dokuwiki does not allow to delete keys
         * {@link p_set_metadata()}
         */
        if ($value === "") {
            return null;
        }
        return $value;
    }

    /**
     * @param Page $page
     * @return MetadataDokuWikiStore
     */
    public function renderAndPersistForPage(Page $page): MetadataDokuWikiStore
    {
        /**
         * Read/render the metadata from the file
         * with parsing
         */
        $dokuwikiId = $page->getPath()->getDokuwikiId();
        $actualMeta = $this->getRawMetadatasFromWikiId($dokuwikiId);
        $newMetadata = p_render_metadata($dokuwikiId, $actualMeta);
        p_save_metadata($dokuwikiId, $newMetadata);
        return $this;
    }

    private function getRawMetadatasFromWikiId($dokuwikiId): array
    {
        return p_read_metadata($dokuwikiId);
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

        $oldValue = $this->getFromWikiId($wikiId, $key);
        if (is_bool($value)) {
            if ($oldValue === null) {
                $oldValue = $default;
            } else {
                $oldValue = Boolean::toBoolean($oldValue);
            }
        }
        if ($oldValue !== $value) {

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


    public function isHierarchicalTextBased(): bool
    {
        return true;
    }

    /**
     *
     * @return string - the full path to the meta file
     */
    public
    function getMetaFile($dokuwikiId)
    {
        return metaFN($dokuwikiId, '.meta');
    }

    public function __toString()
    {
        return "DokuMeta";
    }

    /**
     * Delete the memory data
     */
    public function reset()
    {

    }

    private function getFromWikiId($dokuwikiId, string $name, $default = null)
    {
        /**
         * Note that {@link p_get_metadata()} can trigger a rendering of the meta again
         * and it has a fucking cache
         *
         * Due to the cache in {@link p_get_metadata()} we can't use {@link p_read_metadata}
         * when testing a {@link \action_plugin_combo_imgmove move} otherwise
         * the move meta is not seen and the tests are failing.
         *
         * $METADATA_RENDERERS: A global cache variable where the persistent data is set
         * with {@link p_set_metadata()} and that you can't retrieve with {@link p_get_metadata()}
         */
        global $METADATA_RENDERERS;
        $value = $METADATA_RENDERERS[$dokuwikiId][MetadataDokuWikiStore::PERSISTENT_METADATA][$name];
        if ($value !== null) {
            return $value;
        }

        /**
         * {@link p_get_metadata} flat out the metadata array and we loose the
         * persistent and current information
         * Because there may be already a metadata in current for instance title
         * It will be returned, but we want only the persistent
         */
        $value = $this->getPersistentMetadata($dokuwikiId, $name);

        /**
         * Empty string return the default (null)
         * because Dokuwiki does not allow to delete keys
         * {@link p_set_metadata()}
         */
        if ($value !== null && $value !== "") {
            return $value;
        }
        return $default;
    }

    /**
     * @param $dokuwikiId
     * @return null|array
     */
    private function getFlatMetadatas($dokuwikiId): ?array
    {
        return p_get_metadata($dokuwikiId, '', METADATA_DONT_RENDER);
    }


}
