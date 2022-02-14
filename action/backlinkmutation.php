<?php

use ComboStrap\CacheDependencies;
use ComboStrap\Event;
use ComboStrap\ExceptionCombo;
use ComboStrap\FileSystems;
use ComboStrap\LogUtility;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\Page;
use ComboStrap\PagePath;
use ComboStrap\Reference;
use ComboStrap\References;


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * Refresh the analytics when a backlink mutation occurs for a page
 */
class action_plugin_combo_backlinkmutation extends DokuWiki_Action_Plugin
{


    public const BACKLINK_MUTATION_EVENT_NAME = 'BACKLINK_MUTATION';


    public function register(Doku_Event_Handler $controller)
    {


        /**
         * create the async event
         */
        $controller->register_hook(MetadataDokuWikiStore::PAGE_METADATA_MUTATION_EVENT, 'AFTER', $this, 'create_backlink_mutation', array());

        /**
         * process the Async event
         */
        $controller->register_hook(self::BACKLINK_MUTATION_EVENT_NAME, 'AFTER', $this, 'handle_backlink_mutation');


    }


    public function handle_backlink_mutation(Doku_Event $event, $param)
    {


        $data = $event->data;
        $pagePath = $data[PagePath::getPersistentName()];
        $reference = Page::createPageFromQualifiedPath($pagePath);

        if ($reference->isSlot()) {
            return;
        }

        /**
         * Delete and recompute analytics
         */
        FileSystems::deleteIfExists($reference->getAnalyticsDocument()->getCachePath());
        try {
            $reference->getDatabasePage()->replicateAnalytics();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Backlink Mutation: Error while trying to replicate the analytics. Error: {$e->getMessage()}");
        }

        /**
         * Render the footer slot if it has a backlink dependency
         */
        $footerSlot = $reference->getFooterSlot();
        if ($footerSlot === null) {
            return;
        }
        $dependencies = $footerSlot
            ->getHtmlDocument()
            ->getDependencies();
        if ($dependencies->hasDependency(CacheDependencies::BACKLINKS_DEPENDENCY)) {
            global $ID;
            $keep = $ID;
            $ID = $reference->getDokuwikiId();
            try {
                $footerSlot->toXhtml();
            } catch (ExceptionCombo $e) {
                LogUtility::log2file("Error while rendering the footer slot after backlink mutation. Error: {$e->getMessage()} ");
            } finally {
                $ID = $keep;
            }

        }


    }

    /**
     */
    function create_backlink_mutation(Doku_Event $event, $params)
    {


        $data = $event->data;

        /**
         * If this is not a mutation on references we return.
         */
        if ($data["name"] !== References::getPersistentName()) {
            return;
        };

        $newRows = $data["new_value"];
        $oldRows = $data["old_value"];

        $afterReferences = [];
        if ($newRows !== null) {
            foreach ($newRows as $rowNewValue) {
                $reference = $rowNewValue[Reference::getPersistentName()];
                $afterReferences[$reference] = $reference;
            }
        }

        if ($oldRows !== null) {
            foreach ($oldRows as $oldRow) {
                $beforeReference = $oldRow[Reference::getPersistentName()];
                if (isset($afterReferences[$beforeReference])) {
                    unset($afterReferences[$beforeReference]);
                } else {
                    Event::createEvent(
                        action_plugin_combo_backlinkmutation::BACKLINK_MUTATION_EVENT_NAME,
                        [
                            PagePath::getPersistentName() => $beforeReference
                        ]
                    );
                }
            }
        }
        foreach ($afterReferences as $newReference) {
            Event::createEvent(
                action_plugin_combo_backlinkmutation::BACKLINK_MUTATION_EVENT_NAME,
                [PagePath::getPersistentName() => $newReference]);
        }


    }


}



