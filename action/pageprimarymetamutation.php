<?php

use ComboStrap\CacheDependencies;
use ComboStrap\Event;
use ComboStrap\ExceptionCombo;
use ComboStrap\FileSystems;
use ComboStrap\LogUtility;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\Page;
use ComboStrap\PageDescription;
use ComboStrap\PageH1;
use ComboStrap\PagePath;
use ComboStrap\PageTitle;
use ComboStrap\Reference;
use ComboStrap\References;
use ComboStrap\ResourceName;


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * Delete the cache
 */
class action_plugin_combo_pageprimarymetamutation extends DokuWiki_Action_Plugin
{



    public const PRIMARY_META_MUTATION_EVENT_NAME = 'PAGE_PRIMARY_META_MUTATION';

    public function register(Doku_Event_Handler $controller)
    {


        /**
         * create the async event
         */
        $controller->register_hook(MetadataDokuWikiStore::PAGE_METADATA_MUTATION_EVENT, 'AFTER', $this, 'handlePrimaryMetaMutation', array());



    }

    function handlePrimaryMetaMutation($event)
    {

        $data = $event->data;
        /**
         * The side slot cache is deleted only when the
         * below property are updated
         */
        $descriptionProperties = [PageTitle::PROPERTY_NAME, ResourceName::PROPERTY_NAME, PageH1::PROPERTY_NAME, PageDescription::DESCRIPTION_PROPERTY];
        if (!in_array($data["name"], $descriptionProperties)) return;

        Event::createEvent(
            self::PRIMARY_META_MUTATION_EVENT_NAME,
            [
                PagePath::getPersistentName() => $beforeReference
            ]
        );


    }


}



