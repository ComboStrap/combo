<?php

use ComboStrap\ExceptionNotExists;
use ComboStrap\MarkupCacheDependencies;
use ComboStrap\CacheLog;
use ComboStrap\CacheManager;
use ComboStrap\Event;
use ComboStrap\ExceptionCompile;
use ComboStrap\FileSystems;
use ComboStrap\LogUtility;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\MarkupPath;
use ComboStrap\MetadataMutation;
use ComboStrap\PagePath;
use ComboStrap\Reference;
use ComboStrap\References;


/**
 * Refresh the analytics when a backlink mutation occurs for a page
 */
class action_plugin_combo_backlinkmutation extends DokuWiki_Action_Plugin
{


    public const BACKLINK_MUTATION_EVENT_NAME = 'backlink_mutation';


    public function register(Doku_Event_Handler $controller)
    {


        /**
         * create the async event
         */
        $controller->register_hook(MetadataMutation::PAGE_METADATA_MUTATION_EVENT, 'AFTER', $this, 'create_backlink_mutation', array());

        /**
         * process the Async event
         */
        $controller->register_hook(self::BACKLINK_MUTATION_EVENT_NAME, 'AFTER', $this, 'handle_backlink_mutation');


    }

    /**
     * @param Doku_Event $event
     * @param $param
     * @return void
     */
    public function handle_backlink_mutation(Doku_Event $event, $param)
    {


        $data = $event->data;
        $pagePath = $data[PagePath::getPersistentName()];
        $reference = MarkupPath::createPageFromQualifiedId($pagePath);

        if ($reference->isKnownFragmentMarkup()) {
            return;
        }

        /**
         * Delete and recompute analytics
         */
        try {
            $analyticsDocument = $reference->fetchAnalyticsDocument();
        } catch (ExceptionNotExists $e) {
            return;
        }
        CacheLog::deleteCacheIfExistsAndLog(
            $analyticsDocument,
            self::BACKLINK_MUTATION_EVENT_NAME,
            "Backlink mutation"
        );

        try {
            /**
             * This is only to recompute the {@link \ComboStrap\BacklinkCount backlinks metric} and
             * {@link \ComboStrap\LowQualityPage low quality page metrics}
             * TODO: when the derived meta are in the meta array and not in the {@link renderer_plugin_combo_analytics document},
             *   we could just compute them there and modify it with a plus 1
             */
            $reference->getDatabasePage()->replicateAnalytics();
        } catch (ExceptionCompile $e) {
            LogUtility::msg("Backlink Mutation: Error while trying to replicate the analytics. Error: {$e->getMessage()}");
        }

        /**
         * Render the (footer slot) if it has a backlink dependency
         */
        MarkupCacheDependencies::reRenderSideSlotIfNeeded(
            $pagePath,
            MarkupCacheDependencies::BACKLINKS_DEPENDENCY,
            self::BACKLINK_MUTATION_EVENT_NAME
        );


    }

    /**
     */
    function create_backlink_mutation(Doku_Event $event, $params)
    {


        $data = $event->data;

        /**
         * If this is not a mutation on references we return.
         */
        if ($data[MetadataMutation::NAME_ATTRIBUTE] !== References::getPersistentName()) {
            return;
        };

        $actualReferenceDatas = $data[MetadataMutation::NEW_VALUE_ATTRIBUTE];
        $oldReferenceDatas = $data[MetadataMutation::OLD_VALUE_ATTRIBUTE];

        /**
         * Create an array of the actual reference with the key as path
         */
        $actualReferences = [];
        if ($actualReferenceDatas !== null) {
            foreach ($actualReferenceDatas as $actualReferenceData) {
                $actualReferenceWikiPathString = $actualReferenceData[Reference::getPersistentName()];
                $actualReferences[$actualReferenceWikiPathString] = $actualReferenceWikiPathString;
            }
        }

        if ($oldReferenceDatas !== null) {
            foreach ($oldReferenceDatas as $oldReferenceData) {

                $oldReferenceWikiPathString = $oldReferenceData[Reference::getPersistentName()];

                if (isset($actualReferences[$oldReferenceWikiPathString])) {
                    unset($actualReferences[$oldReferenceWikiPathString]);
                    continue;
                }

                /**
                 * Deleted reference
                 */
                Event::createEvent(
                    action_plugin_combo_backlinkmutation::BACKLINK_MUTATION_EVENT_NAME,
                    [
                        PagePath::getPersistentName() => $oldReferenceWikiPathString
                    ]
                );

            }
        }

        /**
         * The new references
         */
        foreach ($actualReferences as $newReference) {
            Event::createEvent(
                action_plugin_combo_backlinkmutation::BACKLINK_MUTATION_EVENT_NAME,
                [PagePath::getPersistentName() => $newReference]);
        }


    }


}



