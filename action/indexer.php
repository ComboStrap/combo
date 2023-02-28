<?php
/**
 * Copyright (c) 2021. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 *
 */

use ComboStrap\Console;
use ComboStrap\Event;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionRuntimeInternal;
use ComboStrap\ExecutionContext;
use ComboStrap\FileSystems;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\MetadataDbStore;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\PluginUtility;
use ComboStrap\Reference;
use ComboStrap\References;


/**
 * Process metadata to put them in the sqlite database
 * (ie create derived index)
 *
 * This is the equivalent of the dokuwiki {@link \dokuwiki\Search\Indexer}
 * (textual search engine, plus metadata index)
 *
 * Note that you can disable a page to go into the index
 * via the `internal index` metadata. See {@link idx_addPage()}
 * ```
 * $indexenabled = p_get_metadata($page, 'internal index', METADATA_RENDER_UNLIMITED);
 * ```
 */
class action_plugin_combo_indexer extends DokuWiki_Action_Plugin
{

    /**
     * Bad canonical for now as we will add the
     * {@link \ComboStrap\ComboFs} system
     * but it's still a valid page
     */
    const CANONICAL = "replication";

    public function register(Doku_Event_Handler $controller)
    {

        /**
         * We do it after because if there is an error
         * We will not stop the Dokuwiki Processing
         *
         * We could do it after
         * https://www.dokuwiki.org/devel:event:parser_metadata_render
         * but it would then not be async
         *
         * Note: We support other extension for markup
         * but dokuwiki does not index other extension
         * in {@link idx_addPage} (page is the id)
         */
        $controller->register_hook('INDEXER_PAGE_ADD', 'AFTER', $this, 'indexViaIndexerAdd', array());

        /**
         * Process the event table
         *
         * We do it after because if there is an error
         * We will not stop the Dokuwiki Processing
         */
        $controller->register_hook('INDEXER_TASKS_RUN', 'AFTER', $this, 'handle_async_event', array());


    }

    /**
     * @throws ExceptionCompile
     */
    public function indexViaIndexerAdd(Doku_Event $event, $param)
    {

        /**
         * Check that the actual page has analytics data
         * (if there is a cache, it's pretty quick)
         */
        $id = $event->data['page'];
        $page = MarkupPath::createMarkupFromId($id);

        /**
         * From {@link idx_addPage}
         * They receive even the deleted page
         */
        $databasePage = $page->getDatabasePage();
        if (!FileSystems::exists($page)) {
            $databasePage->delete();
            return;
        }

        if ($databasePage->shouldReplicate()) {
            try {
                $databasePage->replicate();
            } catch (ExceptionCompile $e) {
                if (PluginUtility::isDevOrTest()) {
                    // to get the stack trace
                    throw $e;
                }
                $message = "Error with the database replication for the page ($page). " . $e->getMessage();
                if (Console::isConsoleRun()) {
                    throw new ExceptionCompile($message);
                } else {
                    LogUtility::error($message);
                }
            }
        }


    }

    /**
     */
    public function handle_async_event(Doku_Event $event, $param)
    {


        /**
         * Process the async event
         */
        Event::dispatchEvent();


    }


    function indexViaMetadataRendering(Doku_Event $event, $params)
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
        foreach ($internalIdReferences as $internalIdReferenceValue => $internalIdReferenceExist) {
            $ref = Reference::createFromResource($page)
                ->setReadStore(MetadataDokuWikiStore::class)
                ->buildFromStoreValue($internalIdReferenceValue);
            try {
                $references->addRow([$ref]);
            } catch (ExceptionNotFound $e) {
                LogUtility::internalError("The identifier and the value identifier should be known at this stage", self::CANONICAL, $e);
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



