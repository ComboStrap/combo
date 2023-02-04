<?php


namespace ComboStrap;

use dokuwiki\Extension\Event;

/**
 * Class MetadataFileSystemStore
 * @package ComboStrap
 *
 * The meta file system store.
 *
 * It mimics an in-memory store where data are
 *      * read at the store creation
 *      * refreshed when the metadata render runs (See {@link  \action_plugin_combo_metasync}) (Ie dokuwiki modifies the metadata files this way) {@link MetadataDokuWikiStore::renderAndPersist()}
 *      * written immediately on the disk (few write) with the {@link p_set_metadata()}
 *
 * Why ?
 * Php is a CGI script meaning that it starts and end for each request
 * on the server.
 * But in test, this is not the case, as the script starts for the first test
 * and end with the last test.
 *
 * If the data store is local scoped, we get then a lot of inconsistency
 *   - the data for one page is not the same than another
 *   - the metadata object {@link PageId} (from {@link ResourceCombo::getUidObject()} may be null while it was created with another {@link Metadata} creating it twice
 *
 * This implementation has a cache object
 *
 */
class MetadataDokuWikiStore extends MetadataSingleArrayStore
{

    /**
     * Current metadata / runtime metadata / calculated metadata
     * This metadata can only be set when  {@link Syntax::render() rendering}
     * The data may be deleted
     * https://www.dokuwiki.org/devel:metadata#metadata_persistence
     *
     * This is generally where the default data is located
     * if not found in the persistent
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


    const CANONICAL = Metadata::CANONICAL;

    /**
     * When the value of a metadata has changed
     */
    public const PAGE_METADATA_MUTATION_EVENT = "PAGE_METADATA_MUTATION_EVENT";
    const NEW_VALUE_ATTRIBUTE = "new_value";


    /**
     * @return MetadataDokuWikiStore
     * We don't use a global static variable
     * because we are working with php as cgi script
     * and there is no notion of request
     * to be able to flush the data on the disk
     *
     * The scope of the data will be then the store
     */
    public static function getOrCreateFromResource(ResourceCombo $resourceCombo): MetadataStore
    {

        $context = ExecutionContext::getActualOrCreateFromEnv();

        try {
            $executionCachedStores = $context->getRuntimeObject(MetadataDokuWikiStore::class);
        } catch (ExceptionNotFound $e) {
            $executionCachedStores = [];
            $context->setRuntimeObject(self::CANONICAL, $stores);
        }
        $path = $resourceCombo->getPathObject()->toQualifiedId();
        if (isset($executionCachedStores[$path])) {
            return $executionCachedStores[$path];
        }

        if (!($resourceCombo instanceof MarkupPath)) {
            LogUtility::msg("The resource is not a page. File System store supports only page resources");
            $data = null;
        } else {
            $data = p_read_metadata($resourceCombo->getWikiId());
        }

        $metadataStore = new MetadataDokuWikiStore($resourceCombo, $data);
        $executionCachedStores[$path] = $metadataStore;
        return $metadataStore;

    }


    /**
     * @throws ExceptionBadState - if for any reason, it's not possible to store the data
     */
    public function set(Metadata $metadata)
    {

        $name = $metadata->getName();
        try {
            $persistentValue = $metadata->toStoreValue();
        } catch (ExceptionNotFound $e) {
            throw new ExceptionBadState("There is no value to store", self::CANONICAL);
        }
        try {
            $defaultValue = $metadata->toStoreDefaultValue();
        } catch (ExceptionNotFound $e) {
            $defaultValue = null;
        }
        $resource = $metadata->getResource();
        $this->checkResource($resource);
        if ($resource === null) {
            throw new ExceptionBadState("A resource is mandatory", self::CANONICAL);
        }
        if (!($resource instanceof MarkupPath)) {
            throw new ExceptionBadState("The DokuWiki metadata store is only for page resource", self::CANONICAL);
        }
        $dokuwikiId = $resource->getWikiId();
        $this->setFromWikiId($dokuwikiId, $name, $persistentValue, $defaultValue);
    }

    /**
     * @param Metadata $metadata
     * @param null $default
     * @return mixed|null
     */
    public function get(Metadata $metadata, $default = null)
    {

        $resource = $metadata->getResource();
        $this->checkResource($resource);
        if ($resource === null) {
            throw new ExceptionRuntime("A resource is mandatory", self::CANONICAL);
        }
        if (!($resource instanceof MarkupPath)) {
            throw new ExceptionRuntime("The DokuWiki metadata store is only for page resource", self::CANONICAL);
        }
        try {
            $dokuwikiId = $resource->getWikiId();
        } catch (ExceptionBadArgument $e) {
            LogUtility::error("Error", self::CANONICAL, $e);
            return null;
        }
        return $this->getFromWikiId($dokuwikiId, $metadata->getName(), $default);


    }

    /**
     * Getting a metadata for a resource via its name
     * when we don't want to create a class
     *
     * This function is used primarily by derived / process metadata
     *
     * @param string $name
     * @param null $default
     * @return mixed
     */
    public function getFromPersistentName(string $name, $default = null)
    {
        $wikiId = $this->getResource()->getWikiId();
        return $this->getFromWikiId($wikiId, $name, $default);
    }


    public function persist()
    {

        /**
         * Done on set via the dokuwiki function
         */

    }

    /**
     * @param string $name
     * @param string|array $value
     * @return MetadataDokuWikiStore
     */
    public function setFromPersistentName(string $name, $value): MetadataDokuWikiStore
    {
        $this->setFromWikiId($this->getResource()->getWikiId(), $name, $value);
        return $this;
    }

    public function getData(): array
    {
        if (
            $this->data === null
            || sizeof($this->data[self::PERSISTENT_METADATA]) === 0 // move
        ) {
            $this->data = p_read_metadata($this->getResource()->getWikiId());
        }
        return parent::getData();
    }


    /**
     * @param $name
     * @return mixed|null
     */
    private function getPersistentMetadata($name)
    {
        $value = $this->getData()[self::PERSISTENT_METADATA][$name];
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
     */
    public
    function getCurrentFromName($name)
    {
        $value = $this->getData()[self::CURRENT_METADATA][$name];
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
     * @return MetadataDokuWikiStore
     * @throws ExceptionBadArgument
     */
    public function renderAndPersist(): MetadataDokuWikiStore
    {
        /**
         * Read/render the metadata from the file
         * with parsing
         */
        $dokuwikiId = $this->getResource()->getWikiId();
        $actualMeta = $this->getData();
        $wikiRequest = ExecutionContext::getActualOrCreateFromEnv()
            ->startSubExecutionEnv(MetadataDokuWikiStore::class, $dokuwikiId);
        try {
            $newMetadata = p_render_metadata($dokuwikiId, $actualMeta);
            p_save_metadata($dokuwikiId, $newMetadata);
            $this->data = $newMetadata;
        } finally {
            $wikiRequest->closeSubExecutionEnv();
        }
        return $this;
    }


    /**
     * Change a meta on file
     * and triggers the {@link self::PAGE_METADATA_MUTATION_EVENT} event
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
                $oldValue = DataType::toBoolean($oldValue);
            }
        }
        if ($oldValue !== $value) {


            $this->data[self::PERSISTENT_METADATA][$key] = $value;
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
                self::NEW_VALUE_ATTRIBUTE => $value,
                "old_value" => $oldValue,
                PagePath::getPersistentName() => ":$wikiId"
            ];
            Event::createAndTrigger(self::PAGE_METADATA_MUTATION_EVENT, $data);
        }

    }


    public function isHierarchicalTextBased(): bool
    {
        return true;
    }

    /**
     *
     * @return Path - the full path to the meta file
     */
    public
    function getMetaFilePath(): ?Path
    {
        $resource = $this->getResource();
        if (!($resource instanceof MarkupPath)) {
            LogUtility::msg("The resource type ({$resource->getType()}) meta file is unknown and can't be retrieved.");
            return null;
        }
        $dokuwikiId = $resource->getPathObject()->getWikiId();
        return LocalPath::create(metaFN($dokuwikiId, '.meta'));
    }

    public function __toString()
    {
        return "DokuMeta";
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
         *
         * This variable is unset at the end function of {@link p_render_metadata()}
         */
        if ($dokuwikiId === null) {
            /**
             * On edit page, we got null
             * We don't send the error on this quick fix to the page
             * This error will fail a test
             */
            LogUtility::log2file("MetadataDokuwWikiStore: dokuwikiId should not be null");
            return null;
        }
        global $METADATA_RENDERERS;
        $metadataRendererForWikiId = $METADATA_RENDERERS[$dokuwikiId];
        if ($metadataRendererForWikiId !== null) {
            $value = $metadataRendererForWikiId[MetadataDokuWikiStore::PERSISTENT_METADATA][$name];
            if ($value !== null) {
                return $value;
            }
        }

        /**
         * {@link p_get_metadata} flat out the metadata array and we loose the
         * persistent and current information
         * Because there may be already a metadata in current for instance title
         * It will be returned, but we want only the persistent
         */
        $value = $this->getPersistentMetadata($name);

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

    public function deleteAndFlush()
    {
        $emptyMeta = [MetadataDokuWikiStore::CURRENT_METADATA => [], MetadataDokuWikiStore::PERSISTENT_METADATA => []];
        $dokuwikiId = $this->getResource()->getWikiId();
        p_save_metadata($dokuwikiId, $emptyMeta);
        $this->data = $emptyMeta;

    }


}
