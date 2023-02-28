<?php


use ComboStrap\ExceptionInternal;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionRuntimeInternal;
use ComboStrap\FileSystems;
use ComboStrap\LogUtility;
use ComboStrap\Metadata;
use ComboStrap\MetadataDokuWikiArrayStore;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\MarkupPath;
use ComboStrap\MetadataFrontmatterStore;
use ComboStrap\PageImages;
use ComboStrap\PagePath;
use ComboStrap\References;
use dokuwiki\Extension\Event;

/**
 *
 * Handle meta rendering processing
 * * notifiy of changes
 * * and other
 *
 *
 * The changes notification takes place at the document level
 * because we want to notify modication on array level (such as references, images)
 * and not only on scalar.
 */
class action_plugin_combo_metaprocessing
{


    /**
     * When the value of a metadata has changed, an event is created
     */
    public const PAGE_METADATA_MUTATION_EVENT = "PAGE_METADATA_MUTATION_EVENT";
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
    public const NEW_VALUE_ATTRIBUTE = "new_value";
    private array $beforeMetaArray;

    public function register(Doku_Event_Handler $controller)
    {
        /**
         * https://www.dokuwiki.org/devel:event:parser_metadata_render
         */
        $controller->register_hook('PARSER_METADATA_RENDER', 'BEFORE', $this, 'metadataProcessingBefore', array());
        $controller->register_hook('PARSER_METADATA_RENDER', 'AFTER', $this, 'metadataProcessingAfter', array());


    }


    function metadataProcessingBefore($event)
    {

        /**
         * Capture the before state
         */
        if (isset($this->beforeMetaArray)) {
            throw new ExceptionRuntimeInternal("The before variable should be unset in the after method");
        }
        $this->beforeMetaArray = $event->data;
    }

    function metadataProcessingAfter($event)
    {

        $afterMetaArray = &$event->data;
        $beforeMetaArray = $this->beforeMetaArray;
        unset($this->beforeMetaArray);

        $afterId = $afterMetaArray["page"];
        $beforeId = $beforeMetaArray["page"];
        if ($afterId !== $beforeId) {
            LogUtility::internalError("The before ($beforeId) and after id ($afterId) are not the same", get_class($this));
            return;
        }

        $page = MarkupPath::createMarkupFromId($afterId);

        $attributes = [References::getPersistentName()];
        $beforeStore = MetadataDokuWikiArrayStore::getOrCreateFromResource($page, $beforeMetaArray);
        $afterStore = MetadataDokuWikiArrayStore::getOrCreateFromResource($page, $afterMetaArray);
        /**
         * The data should be formatted as if it was for the frontmatter
         * TODO: make it a default for the mutation system ??
         */
        $targetStoreFormat = MetadataFrontmatterStore::class;
        foreach ($attributes as $attribute) {

            try {
                $beforeMeta = Metadata::getForName($attribute)
                    ->setReadStore($beforeStore)
                    ->setWriteStore($targetStoreFormat)
                    ->setResource($page);
                $afterMeta = Metadata::getForName($attribute)
                    ->setReadStore($afterStore)
                    ->setWriteStore($targetStoreFormat)
                    ->setResource($page);
            } catch (ExceptionNotFound $e) {
                LogUtility::internalError("The metadata was not found for the attribute ($attribute)");
                continue;
            }

            $valueBefore = $beforeMeta->toStoreValue();
            $valueAfter = $afterMeta->toStoreValue();

            if ($valueAfter !== $valueBefore) {
                /**
                 * Event
                 */
                $eventData = [
                    "name" => $attribute,
                    self::NEW_VALUE_ATTRIBUTE => $valueAfter,
                    "old_value" => $valueBefore,
                    PagePath::getPersistentName() => ":$afterId"
                ];
                Event::createAndTrigger(self::PAGE_METADATA_MUTATION_EVENT, $eventData);
            }
        }

    /**
     * Trick, don't know if this is always true
     */
PageImages::createForPage($page)->modifyMetaDokuWikiArray($event->data);

}

}