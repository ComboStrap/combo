<?php


namespace ComboStrap\Meta\Store;

use ComboStrap\DataType;
use ComboStrap\ExceptionBadState;
use ComboStrap\ExceptionCast;
use ComboStrap\ExceptionNotExists;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionRuntime;
use ComboStrap\ExceptionRuntimeInternal;
use ComboStrap\ExecutionContext;
use ComboStrap\FetcherMarkup;
use ComboStrap\LocalPath;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataStore;
use ComboStrap\Meta\Api\MetadataStoreAbs;
use ComboStrap\Path;
use ComboStrap\ResourceCombo;
use ComboStrap\WikiPath;

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


    const CANONICAL = Metadata::CANONICAL;
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
            $executionCachedStores = &$context->getRuntimeObject(MetadataDokuWikiStore::class);
        } catch (ExceptionNotFound $e) {
            $executionCachedStores = [];
            $context->setRuntimeObject(MetadataDokuWikiStore::class, $executionCachedStores);
        }
        $path = $resourceCombo->getPathObject()->toAbsoluteString();
        if (isset($executionCachedStores[$path])) {
            return $executionCachedStores[$path];
        }

        $metadataStore = new MetadataDokuWikiStore($resourceCombo);
        $executionCachedStores[$path] = $metadataStore;
        return $metadataStore;

    }

    /**
     *
     * In a rendering, you should not use the {@link p_set_metadata()}
     * but use {@link \Doku_Renderer_metadata::meta} and {@link \Doku_Renderer_metadata::$persistent}
     * to set the metadata
     *
     * Why ?
     * The metadata are set in $METADATA_RENDERERS (A global cache variable where the persistent data is set/exist
     * only during metadata rendering with the function {@link p_render_metadata()}) and then
     * saved at the end
     *
     */
    private static function isRendering(string $wikiId): bool
    {
        global $METADATA_RENDERERS;
        if (isset($METADATA_RENDERERS[$wikiId])) {
            return true;
        }
        return false;
    }


    /**
     * Delete the globals
     */
    public static function unsetGlobalVariables()
    {
        /**
         * {@link p_read_metadata() global cache}
         */
        unset($GLOBALS['cache_metadata']);

        /**
         * {@link p_render_metadata()} temporary render cache
         * global $METADATA_RENDERERS;
         */
        unset($GLOBALS['METADATA_RENDERERS']);

    }


    /**
     * @throws ExceptionBadState - if for any reason, it's not possible to store the data
     */
    public function set(Metadata $metadata)
    {

        $name = $metadata->getName();
        $persistentValue = $metadata->toStoreValue();
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
        /**
         * We don't use {@link p_get_metadata()}
         * because it will trigger a {@link p_render_metadata()}
         * But we may just want to check if there is a {@link PageId}
         * before rendering
         */
        $data = $this->getData();
        $value = $data[$name];

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
            if (self::isRendering($wikiId)) {

                /**
                 * It seems that {@link p_set_metadata()} uses it also
                 * but we show it here
                 */
                global $METADATA_RENDERERS;
                $METADATA_RENDERERS[$wikiId][self::CURRENT_METADATA][$name] = $value;
                $METADATA_RENDERERS[$wikiId][self::PERSISTENT_METADATA][$name] = $value;

            } else {

                p_set_metadata($wikiId,
                    [
                        $name => $value
                    ]
                );

            }
            $this->setGlobalCacheIfAny($name, $value);
        }
        return $this;
    }

    public function getData(): array
    {
        /**
         * We return only the current data.
         *
         * WHy ?
         * To be consistent with {@link p_get_metadata()} that retrieves only from the `current` array
         * Therefore the `persistent` array values should always be duplicated in the `current` array
         *
         * (the only diff is that the persistent value are still available during a {@link p_render_metadata() metadata render})
         *
         * Note that Dokuwiki load them also for the requested path
         * at `global $INFO, $info['meta']` with {@link pageinfo()}
         * and is synced in {@link p_save_metadata()}
         *
         */
        return $this->getDataCurrentAndPersistent()[self::CURRENT_METADATA];
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
            FetcherMarkup::confRoot()
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
        $emptyMeta = [MetadataDokuWikiStore::CURRENT_METADATA => [], self::PERSISTENT_METADATA => []];
        $dokuwikiId = $this->getWikiId();
        p_save_metadata($dokuwikiId, $emptyMeta);
    }


    public function reset()
    {
        self::unsetGlobalVariables();
    }

    /**
     * In {@link p_read_metadata()}, there is a global cache
     * @param string $name
     * @param mixed $value
     */
    private function setGlobalCacheIfAny(string $name, $value)
    {
        global $cache_metadata;

        $id = $this->getWikiId();
        if (isset($cache_metadata[$id])) {
            $cache_metadata[$id]['persistent'][$name] = $value;
            $cache_metadata[$id]['current'][$name] = $value;
        }

    }

    /**
     * @return array -the full array only needed by the rendering process
     * You should use {@link self::getData()} otherwise
     */
    public function getDataCurrentAndPersistent(): array
    {

        $id = $this->getWikiId();
        $data = p_read_metadata($id, true);
        if (empty($data)) {
            LogUtility::internalError("The metadata cache was empty");
            $data = p_read_metadata($id);
        }
        return $data;
    }
}
