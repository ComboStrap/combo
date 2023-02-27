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


/**
 * Process metadata to put them in the database
 * (ie create index)
 *
 * This is the equivalent of the dokuwiki {@link \dokuwiki\Search\Indexer}
 * (textual search engine, plus metadata index)
 */
class action_plugin_combo_index extends DokuWiki_Action_Plugin
{

    const CANONICAL = "index";


    /**
     * @param Doku_Event_Handler $controller
     */
    function register(Doku_Event_Handler $controller)
    {


        /**
         * https://www.dokuwiki.org/devel:event:parser_metadata_render
         */
        $controller->register_hook('PARSER_METADATA_RENDER', 'AFTER', $this, 'indexMetadata', array());


    }


    function indexMetadata(Doku_Event $event, $params)
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
