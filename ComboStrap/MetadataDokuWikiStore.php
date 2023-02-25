<?php


namespace ComboStrap;

use dokuwiki\Extension\Event;

/**
 * Class MetadataFileSystemStore
 * @package ComboStrap
 *
 * A wrapper around the dokuwiki meta file system store.
 *
 */
class MetadataDokuWikiStore extends MetadataStoreAbs
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
            $context->setRuntimeObject(MetadataDokuWikiStore::class, $stores);
        }
        $path = $resourceCombo->getPathObject()->toQualifiedId();
        if (isset($executionCachedStores[$path])) {
            return $executionCachedStores[$path];
        }

        if (!($resourceCombo instanceof MarkupPath)) {
            LogUtility::msg("The resource is not a page. File System store supports only page resources");
            $data = null;
        } else {
            /**
             * Note that {@link p_get_metadata()} can trigger a rendering of the meta again
             * and it has a fucking cache
             *
             * Due to the cache in {@link p_get_metadata()} we can't use {@link p_read_metadata}
             * when testing a {@link \action_plugin_combo_imgmove move} otherwise
             * the move meta is not seen and the tests are failing.
             *
             *
             */
            $wikiId = $resourceCombo->toQualifiedId();
            self::noRenderingCheck($wikiId);
            $data = p_read_metadata($wikiId);
        }

        $metadataStore = new MetadataDokuWikiStore($resourceCombo, $data);
        $executionCachedStores[$path] = $metadataStore;
        return $metadataStore;

    }

    /**
     *
     * $METADATA_RENDERERS: A global cache variable where the persistent data is set/exist
     * only during metadata rendering with the function {@link p_render_metadata()}
     *
     * Setting a metadata does not immediately flushed the value on disk when there is a
     * rendering. They are going into the global $METADATA_RENDERERS
     *
     * The function {@link p_set_metadata()} and {@link p_get_metadata()} use it.
     *
     * The data are rendererd and stored in {@link p_get_metadata()} via {@link p_save_metadata()}
     *
     */
    private static function noRenderingCheck(string $wikiId)
    {
        global $METADATA_RENDERERS;
        if (isset($METADATA_RENDERERS[$wikiId])) {
            $message = "There is a rendering going on, the setting will not flush";
            //Console::log($message);
            throw new ExceptionRuntime($message);
        }
    }



    /**
     * Delete the globals
     */
    public static function unsetGlobalVariables()
    {
        /**
         * {@link p_read_metadata() global cache}
         */
        global $cache_metadata;
        unset($cache_metadata);

        /**
         * {@link p_render_metadata()} temporary render cache
         */
        global $METADATA_RENDERERS;
        unset($METADATA_RENDERERS);
    }


    /**
     * @throws ExceptionBadState - if for any reason, it's not possible to store the data
     * @throws ExceptionNoValueToStore - if there
     */
    public function set(Metadata $metadata)
    {

        $name = $metadata->getName();
        try {
            $persistentValue = $metadata->toStoreValue();
        } catch (ExceptionNotFound $e) {
            $persistentValue = null;
        }
        $defaultValue = $metadata->toStoreDefaultValue();
        $resource = $metadata->getResource();
        $this->checkResource($resource);
        if ($resource === null) {
            throw new ExceptionBadState("A resource is mandatory", self::CANONICAL);
        }
        if (!($resource instanceof MarkupPath)) {
            throw new ExceptionBadState("The DokuWiki metadata store is only for page resource", self::CANONICAL);
        }
        $this->setFromPersistentName($name, $persistentValue, $defaultValue);
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
        return $this->getFromPersistentName($metadata->getName(), $default);


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
        $value = p_get_metadata($this->getWikiId(), $name);
        /**
         * Empty string return null
         * because Dokuwiki does not allow to delete keys
         * {@link p_set_metadata()}
         */
        if ($value !== null && $value !== "") {
            return $value;
        }
        return $default;
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
     * @param null $default
     * @return MetadataDokuWikiStore
     */
    public function setFromPersistentName(string $name, $value, $default = null): MetadataDokuWikiStore
    {
        $oldValue = $this->getFromPersistentName($name);
        if (is_bool($value)) {
            if ($oldValue === null) {
                $oldValue = $default;
            } else {
                $oldValue = DataType::toBoolean($oldValue);
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
             * By default, the value is copied in the current and persistent array
             * and there is no render
             */
            $wikiId = $this->getWikiId();
            p_set_metadata($wikiId,
                [
                    $name => $value
                ]
            );
            /**
             * Event
             */
            $data = [
                "name" => $name,
                self::NEW_VALUE_ATTRIBUTE => $value,
                "old_value" => $oldValue,
                PagePath::getPersistentName() => ":$wikiId"
            ];
            Event::createAndTrigger(self::PAGE_METADATA_MUTATION_EVENT, $data);
        }
        return $this;
    }

    public function getData(): array
    {
        /**
         * persistent array should have duplicate values
         * (the only diff is that the persistent value are always
         * available during a {@link p_render_metadata() metadata render})
         */
        return p_read_metadata($this->getWikiId(), true)['current'];
    }

    private function getWikiId(): string
    {
        try {
            return $this->getResource()->getPathObject()->toWikiPath()->getWikiId();
        } catch (ExceptionCast $e) {
            throw new ExceptionRuntimeInternal("Should not happen", $e);
        }
    }


    /**
     *
     * @param $name
     * @return mixed|null
     * @deprecated use {@link self::getFromPersistentName()}
     */
    public
    function getCurrentFromName($name)
    {
        return $this->getFromPersistentName($name);
    }

    /**
     * @return MetadataDokuWikiStore
     * @deprecated should use a fetcher markup ?
     */
    public function renderAndPersist(): MetadataDokuWikiStore
    {
        /**
         * Read/render the metadata from the file
         * with parsing
         */
        $wikiPage = $this->getResource();
        if (!$wikiPage instanceof WikiPath) {
            LogUtility::errorIfDevOrTest("The resource is not a wiki path");
            return $this;
        }
        try {
            FetcherMarkup::getBuilder()
                ->setRequestedExecutingPath($wikiPage)
                ->setRequestedContextPath($wikiPage)
                ->setRequestedMimeToMetadata()
                ->build()
                ->processMetadataIfNotYetDone();
        } catch (ExceptionNotExists $e) {
            LogUtility::error("Metadata Build Error", self::CANONICAL, $e);
        }

        return $this;
    }



    public function isHierarchicalTextBased(): bool
    {
        return true;
    }

    /**
     * @return Path - the full path to the meta file
     */
    public
    function getMetaFilePath(): ?Path
    {
        $dokuwikiId = $this->getWikiId();
        return LocalPath::createFromPathString(metaFN($dokuwikiId, '.meta'));
    }

    public function __toString()
    {
        return "DokuMeta ({$this->getWikiId()}";
    }




    public function deleteAndFlush()
    {
        $emptyMeta = [MetadataDokuWikiStore::CURRENT_METADATA => [], MetadataDokuWikiStore::PERSISTENT_METADATA => []];
        $dokuwikiId = $this->getWikiId();
        p_save_metadata($dokuwikiId, $emptyMeta);
    }


    public function reset()
    {
        self::unsetGlobalVariables();
    }
}
