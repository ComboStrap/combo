<?php

use ComboStrap\File;
use ComboStrap\Site;


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * When a file is added or deleted
 */
class action_plugin_combo_filesystemmutation extends DokuWiki_Action_Plugin
{



    public const FILE_SYSTEM_MUTATION_EVENT_NAME = 'FILE_SYSTEM_MUTATION';

    public function register(Doku_Event_Handler $controller)
    {


        /**
         * To delete sidebar (cache) cache when a page was modified in a namespace
         * https://combostrap.com/sideslots
         */
        $controller->register_hook('IO_WIKIPAGE_WRITE', 'BEFORE', $this, 'sideSlotsCacheBurstingForPageCreationAndDeletion', array());



    }

    /**
     * @param $event
     * @throws Exception
     * @link https://www.dokuwiki.org/devel:event:io_wikipage_write
     */
    function sideSlotsCacheBurstingForPageCreationAndDeletion($event)
    {

        $data = $event->data;
        $pageName = $data[2];

        /**
         * Modification to the side slot is not processed further
         */
        if (in_array($pageName, Site::getSecondarySlotNames())) return;

        /**
         * Pointer to see if we need to delete the cache
         */
        $doWeNeedToDeleteTheSideSlotCache = false;

        /**
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
        $file = File::createFromPath($filePath);
        if (!$file->exists() && $rev === false) {
            $doWeNeedToDeleteTheSideSlotCache = true;
        }

        /**
         * File deletion
         * (No content)
         *
         * ```
         * Page deletion may be detected by checking for empty page content.
         * On update to an existing page this event is called twice, once for the transfer of the old version to the attic (rev will have a value)
         * and once to write the new version of the page into the wiki (rev is false)
         * ```
         * From https://www.dokuwiki.org/devel:event:io_wikipage_write
         */
        $append = $data[0][2];
        if (!$append) {

            $content = $data[0][1];
            if (empty($content) && $rev === false) {
                // Deletion
                $doWeNeedToDeleteTheSideSlotCache = true;
            }

        }

        if ($doWeNeedToDeleteTheSideSlotCache) action_plugin_combo_cache::removeSecondarySlotCache();

    }



}



