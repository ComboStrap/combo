<?php

use ComboStrap\ComboFs;
use ComboStrap\DatabasePageRow;
use ComboStrap\Event;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExecutionContext;
use ComboStrap\FileSystems;
use ComboStrap\LocalPath;
use ComboStrap\LogUtility;
use ComboStrap\MarkupCacheDependencies;
use ComboStrap\MarkupPath;
use ComboStrap\PageId;
use ComboStrap\PagePath;
use ComboStrap\Site;


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * When a page is added or deleted from the file system
 */
class action_plugin_combo_pagesystemmutation extends DokuWiki_Action_Plugin
{


    public const PAGE_SYSTEM_MUTATION_EVENT_NAME = 'page_system_mutation';

    const TYPE_ATTRIBUTE = "type";
    const TYPE_CREATION = "creation";
    const TYPE_DELETION = "deletion";
    const CANONICAL = "combo-file-system";


    public function register(Doku_Event_Handler $controller)
    {


        /**
         *
         * And To delete sidebar (cache) cache when a page was modified in a namespace
         * https://combostrap.com/sideslots
         */
        $controller->register_hook('IO_WIKIPAGE_WRITE', 'BEFORE', $this, 'createFileSystemMutation', array());

        /**
         * Synchronization with the combo file system
         */
        $controller->register_hook('COMMON_WIKIPAGE_SAVE', 'AFTER', $this, 'comboFsSynchronization', array());

        /**
         * process the Async event
         */
        $controller->register_hook(self::PAGE_SYSTEM_MUTATION_EVENT_NAME, 'AFTER', $this, 'handleFileSystemMutation');

    }

    /**
     * @param $event
     * @throws Exception
     * @link https://www.dokuwiki.org/devel:event:io_wikipage_write
     *
     * On update to an existing page this event is called twice, once for the transfer of the old version to the attic
     * (rev will have a value)
     * and once to write the new version of the page into the wiki (rev is false)
     */
    function createFileSystemMutation($event)
    {

        $data = $event->data;
        $pageName = $data[2];

        /**
         * Modification to the secondary slot are not processed
         */
        if (in_array($pageName, Site::getFragmentNames())) return;


        /**
         * TODO ?: the common uses the  common_wikipage_save instead ?
         *   https://www.dokuwiki.org/devel:event:common_wikipage_save
         * File creation
         *
         * ```
         * Page creation may be detected by checking if the file already exists and the revision is false.
         * ```
         * From https://www.dokuwiki.org/devel:event:io_wikipage_write
         *
         */
        $rev = $data[3];
        $filePath = $data[0][0];
        $file = LocalPath::createFromPathString($filePath);
        if (!FileSystems::exists($file) && $rev === false) {
            Event::createEvent(
                action_plugin_combo_pagesystemmutation::PAGE_SYSTEM_MUTATION_EVENT_NAME,
                [
                    self::TYPE_ATTRIBUTE => self::TYPE_CREATION,
                    PagePath::getPersistentName() => $file->toWikiPath()->toAbsoluteString()
                ]
            );
            return;
        }

        /**
         * File deletion
         * (No content)
         *
         * ```
         * Page deletion may be detected by checking for empty page content.
         * On update to an existing page this event is called twice, once for the transfer
         * of the old version to the attic (rev will have a value)
         * and once to write the new version of the page into the wiki (rev is false)
         * ```
         * From https://www.dokuwiki.org/devel:event:io_wikipage_write
         */
        $append = $data[0][2];
        if (!$append) {

            $content = $data[0][1];
            if (empty($content) && $rev === false) {
                // Deletion
                Event::createEvent(
                    action_plugin_combo_pagesystemmutation::PAGE_SYSTEM_MUTATION_EVENT_NAME,
                    [
                        self::TYPE_ATTRIBUTE => self::TYPE_DELETION,
                        PagePath::getPersistentName() => $file->toWikiPath()->toAbsoluteString()
                    ]
                );
            }
        }

    }

    public function handleFileSystemMutation($event)
    {

        /**
         * We need to re-render the slot
         * that are {@link \ComboStrap\MarkupCacheDependencies::PAGE_SYSTEM_DEPENDENCY}
         * dependent
         */
        $data = $event->data;

        /**
         * Re-render
         */
        $pathAddedOrDeleted = $data[PagePath::getPersistentName()];
        MarkupCacheDependencies::reRenderSideSlotIfNeeded(
            $pathAddedOrDeleted,
            MarkupCacheDependencies::PAGE_SYSTEM_DEPENDENCY,
            self::PAGE_SYSTEM_MUTATION_EVENT_NAME
        );

    }

    /**
     * Store into the Combo file system
     * @param $event
     * @return void
     */
    public function comboFsSynchronization($event)
    {

        $id = $event->data['id'];
        $markup = MarkupPath::createMarkupFromId($id);

        $changedType = $event->data['changeType'];
        switch ($changedType) {
            // creation
            case DOKU_CHANGE_TYPE_CREATE:
                /**
                 * For now, we just sync the page id in the index tables (pages)
                 *
                 * This is mandatory to allow permanent url redirection {@link PageUrlType})
                 */
                try {
                    PageId::createForPage($markup)
                        ->getValue();
                } catch (ExceptionNotFound $e) {
                    ComboFs::createIfNotExists($markup);
                }
                return;
            case DOKU_CHANGE_TYPE_DELETE:
                /**
                 * Is this a delete from a move ?
                 */
                try {
                    $executionContext = ExecutionContext::getActualOrCreateFromEnv();
                    $sourceId = $executionContext
                        ->getRuntimeObject(action_plugin_combo_linkmove::FILE_MOVE_OPERATION);
                    if (":$sourceId" === $markup->toAbsoluteString()) {
                        return;
                    }
                } catch (ExceptionNotFound $e) {
                    // not a move
                }
                ComboFs::delete($markup);
                return;
        }


    }

}



