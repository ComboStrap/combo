<?php


use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionRuntimeInternal;
use ComboStrap\ExecutionContext;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\Meta\Api\MetadataSystem;
use ComboStrap\Meta\Field\PageH1;
use ComboStrap\Meta\Field\PageImages;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;
use ComboStrap\MetadataDokuWikiArrayStore;
use ComboStrap\MetadataFrontmatterStore;
use ComboStrap\MetadataMutation;
use ComboStrap\PluginUtility;
use ComboStrap\References;

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
class action_plugin_combo_metaprocessing extends DokuWiki_Action_Plugin
{


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

        /**
         * To avoid
         * Console Info: slot_footer.xhtml: Cache (1681473480)
         * is older than dependent C:\Users\GERARD~1\AppData\Local\Temp\dwtests-1681473476.2836\data\meta\cache_manager_slot_test.meta (1681473480), cache is not usable
         * See {@link \ComboStrap\Test\TestUtility::WaitToCreateCacheFile1SecLater()}
         */
        if (PluginUtility::isDevOrTest()) {
            sleep(1);
        }

        $page = MarkupPath::createMarkupFromId($afterId);

        $primaryMetas = action_plugin_combo_pageprimarymetamutation::PRIMARY_METAS;
        $referencesAttributes = [References::getPersistentName()];
        $qualityMetadata = action_plugin_combo_qualitymutation::getQualityMetas();
        $attributes = array_merge($primaryMetas, $referencesAttributes, $qualityMetadata);

        $beforeStore = MetadataDokuWikiArrayStore::getOrCreateFromResource($page, $beforeMetaArray);
        $afterStore = MetadataDokuWikiArrayStore::getOrCreateFromResource($page, $afterMetaArray);
        /**
         * The data should be formatted as if it was for the frontmatter
         * TODO: make it a default for the mutation system ??
         */
        $targetStoreFormat = MetadataFrontmatterStore::class;
        foreach ($attributes as $attribute) {

            try {
                $beforeMeta = MetadataSystem::getForName($attribute)
                    ->setReadStore($beforeStore)
                    ->setWriteStore($targetStoreFormat)
                    ->setResource($page);
                $afterMeta = MetadataSystem::getForName($attribute)
                    ->setReadStore($afterStore)
                    ->setWriteStore($targetStoreFormat)
                    ->setResource($page);
            } catch (ExceptionNotFound $e) {
                LogUtility::internalError("The metadata was not found for the attribute ($attribute)");
                continue;
            }

            try {
                $beforeMeta->getValue();
                $valueBefore = $beforeMeta->toStoreValue();
            } catch (Exception $e) {
                // first value
                $valueBefore = null;
            }

            $valueAfter = $afterMeta->toStoreValue();
            MetadataMutation::notifyMetadataMutation($attribute, $valueBefore, $valueAfter, $page);

        }


        /**
         * We got a conflict Dokuwiki stores a `title` meta in the current
         * Because we may delete the first heading, the stored title is the second
         * heading, we update it
         * See first line of {@link \Doku_Renderer_metadata::header()}
         */
        $isWikiDisabled = ExecutionContext::getActualOrCreateFromEnv()
            ->getConfig()
            ->isHeadingWikiComponentDisabled();
        if ($isWikiDisabled) {
            $event->data[MetadataDokuWikiStore::CURRENT_METADATA]['title'] = $event->data[MetadataDokuWikiStore::CURRENT_METADATA][PageH1::H1_PARSED];
        }

        /**
         * Trick, don't know if this is always true
         */
        PageImages::createForPage($page)->modifyMetaDokuWikiArray($event->data);

    }

}
