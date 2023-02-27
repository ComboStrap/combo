<?php

use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExecutionContext;
use ComboStrap\FileSystems;
use ComboStrap\LinkMarkup;
use ComboStrap\LogUtility;
use ComboStrap\MarkupRef;
use ComboStrap\MetadataDbStore;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\MarkupPath;
use ComboStrap\PluginUtility;
use ComboStrap\Reference;
use ComboStrap\References;

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


/**
 *
 * This code is part of the backlink mutation features.
 *
 * It stores the references for a pages at once
 * (and not one by one as Dokuwiki does)
 *
 * If the data mutate, the {@link action_plugin_combo_backlinkmutation}
 * will trigger, to recalculate the backlink analytics
 *
 */
class action_plugin_combo_reference extends DokuWiki_Action_Plugin
{
    const CANONICAL = "reference";


    /**
     * @param Doku_Event_Handler $controller
     */
    function register(Doku_Event_Handler $controller)
    {


        /**
         * https://www.dokuwiki.org/devel:event:parser_metadata_render
         */
        $controller->register_hook('PARSER_METADATA_RENDER', 'AFTER', $this, 'storeReference', array());


    }


    /**
     * Store the references set for the whole page
     * The datastore will then see a mutation
     * processed by {@link action_plugin_combo_backlinkmutation}
     */
    function storeReference(Doku_Event $event, $params)
    {

        try {
            $wikiPath = ExecutionContext::getActualOrCreateFromEnv()
                ->getExecutingWikiPath();
        } catch (ExceptionNotFound $e) {
            // markup string run
            return;
        }

        $page = MarkupPath::createPageFromPathObject($wikiPath);

        $references = References::createFromResource($page)
            ->setReadStore(MetadataDokuWikiStore::class);

        $internalIdReferences = $event->data['current']['relation']['references'];
        foreach($internalIdReferences as $internalIdReferenceValue => $internalIdReferenceExist){
            $ref = Reference::createFromResource($page)
                ->setReadStore(MetadataDokuWikiStore::class)
                ->buildFromStoreValue($internalIdReferenceValue);
            try {
                $references->addRow([$ref]);
            } catch (ExceptionNotFound $e) {
                LogUtility::internalError("The identifier and the value identifier should be known at this stage",self::CANONICAL, $e);
            }
        }

        try {
            // persist to database
            $references
                ->setWriteStore(MetadataDbStore::class)
                ->persist();
        } catch (ExceptionCompile $e) {
            LogUtility::warning("Reference error when persisting to the file system store: " . $e->getMessage(), self::CANONICAL, $e);
        }

    }


}
