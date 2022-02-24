<?php /** @noinspection SpellCheckingInspection */

use ComboStrap\CacheDependencies;
use ComboStrap\DokuwikiId;
use ComboStrap\Event;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\PageDescription;
use ComboStrap\PageH1;
use ComboStrap\PagePath;
use ComboStrap\PageTitle;
use ComboStrap\ResourceName;


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * Delete the cache
 */
class action_plugin_combo_pageprimarymetamutation extends DokuWiki_Action_Plugin
{


    public const PRIMARY_META_MUTATION_EVENT_NAME = 'page_primary_meta_mutation';

    public const PRIMARY_METAS = [PageTitle::PROPERTY_NAME, ResourceName::PROPERTY_NAME, PageH1::PROPERTY_NAME, PageDescription::DESCRIPTION_PROPERTY];


    public function register(Doku_Event_Handler $controller)
    {

        /**
         * create the async event
         */
        $controller->register_hook(MetadataDokuWikiStore::PAGE_METADATA_MUTATION_EVENT, 'AFTER', $this, 'createPrimaryMetaMutation', array());

        /**
         * process the Async event
         */
        $controller->register_hook(self::PRIMARY_META_MUTATION_EVENT_NAME, 'AFTER', $this, 'handlePrimaryMetaMutation');

    }

    function createPrimaryMetaMutation($event)
    {

        $data = $event->data;

        /**
         * The slot cache are re-rendered only when the
         * below property are updated
         */
        if (!in_array($data["name"], self::PRIMARY_METAS)) return;

        Event::createEvent(
            self::PRIMARY_META_MUTATION_EVENT_NAME,
            [
                PagePath::getPersistentName() => $data[PagePath::getPersistentName()]
            ]
        );


    }

    function handlePrimaryMetaMutation($event)
    {

        /**
         * We need to re-render the slot
         * that are {@link \ComboStrap\CacheDependencies::PAGE_PRIMARY_META_DEPENDENCY}
         * dependent
         */
        $data = $event->data;

        /**
         * Build the context back before getting the slots
         */
        $path = $data[PagePath::getPersistentName()];
        CacheDependencies::reRenderSecondarySlotsIfNeeded(
            $path,
            CacheDependencies::PAGE_PRIMARY_META_DEPENDENCY,
            self::PRIMARY_META_MUTATION_EVENT_NAME
        );

    }


}



